<?php
namespace app\cli\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\BusinessMoneyLog;

class MoneyLogController
{
	/**
	 * 处理商户资金变化记录
	 */
	public function handle()
	{
		$global_redis = Common::global_redis();
		$redis_key = 'CliExecuteTime_MoneyLog_handle';

		while (true)
		{
			\think\facade\Db::startTrans();
			try
			{

				$begin = microtime(true);

				$arr_business_money = [];

				$where = [];
				$where[] = ['status', '=', -1]; //状态：-1未处理 1已处理

				$list = BusinessMoneyLog::where($where)->order('id asc')->select()->toArray();
				foreach ($list as $log)
				{
					if (!isset($arr_business_money[$log['business_id']]))
					{
						$arr_business_money[$log['business_id']] = 0;

						$_business = Business::field('money')->where('id', $log['business_id'])->find();
						if ($_business)
						{
							$arr_business_money[$log['business_id']] = $_business['money'];
						}
					}

					$money_before = $arr_business_money[$log['business_id']];
					$money_after = $arr_business_money[$log['business_id']] + $log['money'];

					BusinessMoneyLog::where('id', $log['id'])->save(['money_before' => $money_before, 'money_after' => $money_after, 'status' => 1]);

					$arr_business_money[$log['business_id']] = $money_after;
				}

				foreach ($arr_business_money as $business_id => $business_money)
				{
					Business::where('id', $business_id)->save(['money' => $business_money]);
				}

				$end = microtime(true);
				$time = round($end - $begin, 2) . 's';

				$num = count($list);
				$num2 = count($arr_business_money);

				if ($num > 0)
				{
					$data = [
						'money_log_num' => $num,
						'business_num' => $num2,
						'time' => $time,
					];
					Common::writeLogLine($data, 'cli_MoneyLog_handle');
				}

				// echo "处理资金数量: {$num}, 更新商家数量: {$num2}, 时间: {$time}\n";

				\think\facade\Db::commit();

			}
			catch (\Exception $e)
			{

				\think\facade\Db::rollback();

				$data = [
					'error' => 'ERROR',
					'msg' => $e->getMessage(),
				];
				Common::writeLogLine($data, 'cli_MoneyLog_handle_error');

				// echo "处理失败: {$e->getMessage()}\n";

			}

			$global_redis->set($redis_key, date('Y-m-d H:i:s'));

			sleep(1);
		}

		exit;
	}
}