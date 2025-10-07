<?php
namespace app\cli\controller;

use app\extend\common\Common;
use app\service\OrderService;
use app\model\Order;

class NotifyController
{
	protected $max_num = 10; //重发次数
	protected $after_time = 10; //间隔时间（秒）

	/**
	 * 重发回调
	 */
	public function resend()
	{
		$global_redis = Common::global_redis();
		$redis_key = 'CliExecuteTime_Notify_resend';

		while (true)
		{
			mysqlKeepAlive();

			$begin = microtime(true);

			$where = [];
			$where[] = ['status', '=', 1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2生成订单失败
			$where[] = ['notify_num', '<', $this->max_num];
			$where[] = ['last_notify_time', '<', date('Y-m-d H:i:s', time() - $this->after_time)];

			$list = Order::field('id')->where($where)->select()->toArray();
			foreach ($list as $order)
			{
				OrderService::sendNotify($order['id']);
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
				Common::writeLogLine($data, 'cli_notify_resend');
			}

			// echo "回调数量: {$num}, 时间: {$time}\n";

			$global_redis->set($redis_key, date('Y-m-d H:i:s'));

			sleep(5);
		}

		exit;
	}
}