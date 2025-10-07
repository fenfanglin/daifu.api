<?php
namespace app\cli\controller;

use app\extend\common\Common;
use app\model\Channel;
use app\model\Business;
use app\model\Order;
use app\model\FinanceStatistics;
use Common as GlobalCommon;

class FinanceController
{
	/**
	 * 统计
	 */
	public function statistics()
	{
		if (in_array(date('H'), ['21', '22', '23', '00', '01']))
		{
			echo "高峰期不处理\n";
			exit;
		}

		$redis = Common::redis();
		$redis_key = 'cli_finance_statistics_time';

		// $redis->delete($redis_key);exit;

		$next_time = $redis->get($redis_key);
		if (!$next_time)
		{
			$last_model = FinanceStatistics::field('date')->order('date desc')->find();
			if ($last_model)
			{
				$next_time = date('Y-m-d H:00:00', strtotime("{$last_model->date} +1 hour"));
			}
			else
			{
				$next_time = date('Y-m-d 00:00:00');
			}
		}

		if ($next_time >= date('Y-m-d H:00:00'))
		{
			// 时间未到
			echo "{$next_time}，时间未到\n";
			exit;
		}

		$date = $next_time;
		$next_time = date('Y-m-d H:00:00', strtotime("{$next_time} +1 hour"));
		$redis->set($redis_key, $next_time);

		$begin = microtime(true);

		// $channel_list = Channel::field('id')->where(['status' => 1])->order('id asc')->select();
		$channel_list = Channel::field('id')->order('sort asc')->select();

		$where = [];
		$where[] = ['status', '=', 1]; //状态：1启用 -1禁用
		$where[] = ['type', 'in', [1, 3, 4]]; //类型：1商户 2卡商 3四方 4四方商户
		$business_list = Business::field('id, username, type, parent_id')->where($where)->order('id asc')->select();


		$date_begin = date('Y-m-d H:00:00', strtotime($date));
		$date_end = date('Y-m-d H:59:59', strtotime($date));

		foreach ($business_list as $business)
		{
			if (in_array($business->type, [1, 3]))
			{
				$_where_total = [];
				$_where_total[] = ['business_id', '=', $business->id];
				$_where_total[] = ['create_time', '>=', $date_begin];
				$_where_total[] = ['create_time', '<=', $date_end];

				$_where_success = [];
				$_where_success[] = ['business_id', '=', $business->id];
				$_where_success[] = ['success_time', '>=', $date_begin];
				$_where_success[] = ['success_time', '<=', $date_end];
			}
			else
			{
				$_where_total = [];
				$_where_total[] = ['sub_business_id', '=', $business->id];
				$_where_total[] = ['create_time', '>=', $date_begin];
				$_where_total[] = ['create_time', '<=', $date_end];

				$_where_success = [];
				$_where_success[] = ['sub_business_id', '=', $business->id];
				$_where_success[] = ['success_time', '>=', $date_begin];
				$_where_success[] = ['success_time', '<=', $date_end];
			}

			foreach ($channel_list as $channel)
			{
				if (in_array($business->type, [1, 3]))
				{
					$check = FinanceStatistics::where(['business_id' => $business->id, 'sub_business_id' => 0, 'channel_id' => $channel->id, 'date' => $date])->count('id');
					if ($check)
					{
						continue;
					}

					//状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
					$where_total = $_where_total;
					$where_total[] = ['channel_id', '=', $channel->id];

					$where_success = $_where_success;
					$where_success[] = ['channel_id', '=', $channel->id];

					$model = new FinanceStatistics;
					$model->business_id = $business->id;
					$model->channel_id = $channel->id;
					$model->date = $date;
					$model->total_order = Order::where($where_total)->count('id');
					$model->success_order = Order::where($where_success)->where('status', '>', 0)->count('id');
					$model->success_amount = Order::where($where_success)->where('status', '>', 0)->sum('pay_amount');
					$model->total_fee = Order::where($where_success)->where('status', '>', 0)->sum('fee');

					Common::writeLog(['sql' => Order::where($where_success)->where('status', '>', 0)->fetchSql(1)->count('id')], 'test');

					if ($model->total_order > 0 || $model->success_order > 0)
					{
						$model->save();
					}
				}
				else
				{
					$check = FinanceStatistics::where(['sub_business_id' => $business->id, 'channel_id' => $channel->id, 'date' => $date])->count('id');
					if ($check)
					{
						continue;
					}

					//状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
					$where_total = $_where_total;
					$where_total[] = ['channel_id', '=', $channel->id];

					$where_success = $_where_success;
					$where_success[] = ['channel_id', '=', $channel->id];

					$model = new FinanceStatistics;
					$model->business_id = $business->parent_id;
					$model->sub_business_id = $business->id;
					$model->channel_id = $channel->id;
					$model->date = $date;
					$model->total_order = Order::where($where_total)->count('id');
					$model->success_order = Order::where($where_success)->where('status', '>', 0)->count('id');
					$model->success_amount = Order::where($where_success)->where('status', '>', 0)->sum('pay_amount');
					$model->allow_withdraw = Order::where($where_success)->where('status', '>', 0)->sum('allow_withdraw');
					$model->total_fee = Order::where($where_success)->where('status', '>', 0)->sum('fee');

					if ($model->total_order > 0 || $model->success_order > 0)
					{
						$model->save();
					}
				}
			}
		}

		$end = microtime(true);
		$time = round($end - $begin, 2);

		echo "{$date}，时长: {$time}s\n";

		exit;
	}
}