<?php
namespace app\cli\controller;

use app\extend\common\Common;
use app\service\UsdtService;
use app\service\RechargeService;
use app\model\BusinessRecharge;
use app\model\Setting;

class UsdtController
{
	protected $transaction_cache_time = 60 * 60 * 24; //成功交易区块链缓存1天，避免重复请求接口

	/**
	 * 监控usdt充值
	 */
	public function handle_recharge()
	{
		$global_redis = Common::global_redis();
		$redis_key = 'CliExecuteTime_Usdt_handle_recharge';

		$redis = Common::redis();

		while (true)
		{
			$arr_trans = [];

			$begin = microtime(true);

			$where = [];
			$where[] = ['recharge_type', '=', 1]; //充值方式：-1后台充值 1Usdt
			$where[] = ['status', '=', -1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
			$where[] = ['expire_time', '>=', date('Y-m-d H:i:s')];

			$list = BusinessRecharge::where($where)->select()->toArray();
			foreach ($list as $recharge)
			{
				$usdt_address = $recharge['account'];

				if (!isset($arr_trans[$usdt_address]))
				{
					$arr_trans[$usdt_address] = UsdtService::getTransactionByTrc($usdt_address);
				}

				$res = UsdtService::diffTrans($arr_trans[$usdt_address], $recharge);

				if ($res !== false)
				{
					$check = BusinessRecharge::where(['usdt_transaction_id' => $res['transaction_id']])->count('id');
					if ($check)
					{
						continue;
					}

					$redis->set('usdt_trans_' . $res['transaction_id'], 1, $this->transaction_cache_time); //成功交易区块链缓存60分钟，避免重复请求接口
					Common::writeLog($res, 'cli_usdt_handle_recharge_success');

					// echo '收到USDT:' . $res['pay_amount'] . "\r\n";

					// 处理完成订单，并且回调
					RechargeService::completeRecharge($recharge['id'], $res['transaction_id']);
				}
			}

			$end = microtime(true);
			$time = round($end - $begin, 2) . 's';

			$num = count($list);

			if ($num > 0)
			{
				$data = [
					'num' => $num,
					'time' => $time,
				];
				Common::writeLogLine($data, 'cli_usdt_handle_recharge');
			}

			// echo "数量: {$num}, 时间: {$time}\n";

			$global_redis->set($redis_key, date('Y-m-d H:i:s'));

			sleep(1);
		}

		exit;
	}

	/**
	 * 自动更新汇率
	 * 考虑做成cron
	 */
	public function autorate()
	{
		// 获取Usdt汇率
		$res = UsdtService::getUsdtRate();
		if (!isset($res['code']) || $res['code'] != 1)
		{
			if (isset($res['msg']))
			{
				echo "失败: {$res['msg']}\n";
			}
			else
			{
				echo "失败: ERROR\n";
			}

			exit;
		}

		$usdt_rate = isset($res['new_rate']) ? $res['new_rate'] : 0;

		$result = Setting::where(['id' => 1])->save(['usdt_rate' => $usdt_rate, 'update_usdt_rate_time' => date('Y-m-d H:i:s')]);

		echo "汇率: {$usdt_rate}\n";

		exit;
	}
}
