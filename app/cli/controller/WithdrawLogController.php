<?php
namespace app\cli\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\BusinessWithdrawLog;

class WithdrawLogController
{
	/**
	 * 处理商户可提现金额变化记录
	 */
	public function handle()
	{
		$global_redis = Common::global_redis();
		$redis_key = 'CliExecuteTime_WithdrawLog_handle';
		
		while (true)
		{
			\think\facade\Db::startTrans();
			try {
				
				$begin = microtime(true);
				
				$arr_business_money = [];
				
				$where = [];
				$where[] = ['status', '=', -1]; //状态：-1未处理 1已处理
				
				$list = BusinessWithdrawLog::where($where)->order('id asc')->select()->toArray();
				foreach ($list as $log)
				{
					if (!isset($arr_business_money[$log['sub_business_id']]))
					{
						$_business = Business::field('allow_withdraw')->where('id', $log['sub_business_id'])->find()->toArray();
						$arr_business_money[$log['sub_business_id']] = $_business['allow_withdraw'];
					}
					
					$money_before = $arr_business_money[$log['sub_business_id']];
					$money_after = $arr_business_money[$log['sub_business_id']] + $log['money'];
					
					BusinessWithdrawLog::where('id', $log['id'])->save(['money_before' => $money_before, 'money_after' => $money_after, 'status' => 1]);
					
					$arr_business_money[$log['sub_business_id']] = $money_after;
				}
				
				foreach ($arr_business_money as $sub_business_id => $business_money)
				{
					Business::where('id', $sub_business_id)->save(['allow_withdraw' => $business_money]);
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
					Common::writeLogLine($data, 'cli_WithdrawLog_handle');
				}
				
				// echo "处理资金数量: {$num}, 更新商家数量: {$num2}, 时间: {$time}\n";
				
				\think\facade\Db::commit();
				
			} catch (\Exception $e) {
				
				\think\facade\Db::rollback();
				
				$data = [
					'error' => 'ERROR',
					'msg' => $e->getMessage(),
				];
				Common::writeLogLine($data, 'cli_WithdrawLog_handle_error');
				
				// echo "处理失败: {$e->getMessage()}\n";
				
			}
			
			$global_redis->set($redis_key, date('Y-m-d H:i:s'));
			
			sleep(1);
		}
		
		exit;
	}
}