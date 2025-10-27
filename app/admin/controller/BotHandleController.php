<?php
namespace app\admin\controller;

use app\extend\common\BaseController;
use app\extend\common\Common;
use app\model\BusinessChannel;
use app\model\BusinessWithdrawLog;
use app\model\DB2Order;
use app\model\Business;
use app\model\Channel;
use app\model\Order;
use app\model\BotGroup;
use app\model\BusinessMoneyLog;
use app\service\bot\BotGetMessageService;
use app\service\BusinessService;
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS, DELETE'); //请求方法
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Control-Type, Content-Type, token, Accept, x-access-sign, x-access-time');

class BotHandleController extends BaseController
{

	//检查余额是否充值  所有的商户
	public function checkMoneyAll()
	{
		$money = new BotGroup();
		$list = $money->where('status', '=', 1)->where('parent_id', '<>', 30318)->where('quota_status', 1)->field('id,business_id,chat_id,quota')->select()->toArray();
		if ($list)
		{

			$message_model = new BotGetMessageService(['bot_token' => '8487258468:AAGfB1orYOR8Xu2lRNvQqDWuF9AiriXG87E'], []);
			$global_redis = \app\extend\common\Common::global_redis();
			$business_model = new Business();
			foreach ($list as $key)
			{
				//查出商户最近半小时有无下单
				$where = [];
				$where[] = ['sub_business_id', '=', $key['business_id']];
				$where[] = ['type', 'in', [1, 2]];  //类型为订单
				$where[] = ['create_time', '>', date('Y-m-d H:i:s', time() - (60 * 30))];  //最近半小时的数据
				$is_order = BusinessWithdrawLog::where($where)->find();
				if (!$is_order)
				{
					continue;
				}

				$keys = 'money_negative_warning_open' . $key['business_id'];
				$check = $global_redis->get($keys);
				if ($check == 2)
				{
					continue;
				}
				$business = $business_model->where('id', $key['business_id'])->find();
				if ($business && (int) $business->allow_withdraw < $key['quota'])
				{

					if ($global_redis->get("money_negative_warning_{$key['business_id']}"))
					{
						continue;
					}
					$global_redis->set("money_negative_warning_{$key['business_id']}", $key['business_id'], (30 * 60));
					$data = [
						'chat_id' => $key['chat_id'],
						'text' => "尊敬的【" . $business->realname . "】vip客户\n您现在的可用余额【" . $business->allow_withdraw . "】\n已经低于【" . $key['quota'] . "】\n为了不影响您的正常代付,请尽快充值!",
						// 	'reply_to_message_id' => $this->message_data['message']['message_id']
					];
					Common::writeLog(['msg' => '余额提醒', 'text' => $data['text']], 'bot_yetx');
					$message_model->sendForwardTextMessage($data);
				}
			}
		}
	}

	//检查余额是否充值  30318下的商户
	public function checkMoney()
	{
		$money = new BotGroup();
		$list = $money->where('status', '=', 1)->where('parent_id', 30318)->field('id,business_id,chat_id')->select()->toArray();
		if ($list)
		{

			$message_model = new BotGetMessageService(['bot_token' => '8487258468:AAGfB1orYOR8Xu2lRNvQqDWuF9AiriXG87E'], []);
			$global_redis = \app\extend\common\Common::global_redis();
			$business_model = new Business();
			foreach ($list as $key)
			{
				//查出商户最近半小时有无下单
				$where = [];
				$where[] = ['sub_business_id', '=', $key['business_id']];
				$where[] = ['type', 'in', [1, 2]];  //类型为订单
				$where[] = ['create_time', '>', date('Y-m-d H:i:s', time() - (60 * 30))];  //最近半小时的数据
				$is_order = BusinessWithdrawLog::where($where)->find();
				if (!$is_order)
				{
					continue;
				}

				$keys = 'money_negative_warning_open' . $key['business_id'];
				$check = $global_redis->get($keys);
				if ($check == 2)
				{
					continue;
				}
				$business = $business_model->where('id', $key['business_id'])->find();
				if ($business && (int) $business->allow_withdraw < 5000)
				{

					if ($global_redis->get("money_negative_warning_{$key['business_id']}"))
					{
						continue;
					}
					$global_redis->set("money_negative_warning_{$key['business_id']}", $key['business_id'], (10 * 60));
					$data = [
						'chat_id' => $key['chat_id'],
						'text' => "尊敬的【" . $business->realname . "】vip客户\n您现在的可用余额【" . $business->allow_withdraw . "】\n已经低于【5000】\n为了不影响您的正常代付,请尽快充值!",
						// 	'reply_to_message_id' => $this->message_data['message']['message_id']
					];
					Common::writeLog(['msg' => '余额提醒', 'text' => $data['text']], 'bot_yetx');
					$message_model->sendForwardTextMessage($data);
				}
			}
		}
	}

	public function notice()
	{
		$business_model = new Business();
		$list = $business_model->where('money', '<', 300)
			->where('status', '=', 1)
			->whereIn('type', [1, 3])->field('id,money,notice_time')->select()->toArray();
		return json_encode($list);
	}

	public function editNotice()
	{
		$business_id = input('get.business_id');
		$notice_time = input('get.notice_time');
		$business_model = new Business();
		$business_model->where('id', $business_id)->update(['notice_time' => $notice_time]);
	}

	public function getOrder($out_trade_no)
	{
		//        $business_id = input('get.business_id');
//        $out_trade_no = input('get.out_trade_no');
		$order_model = new Order();
		$info = $order_model
			->where('out_trade_no', $out_trade_no)->find();
		return $info;
	}

	public function getChannelRate()
	{
		$business_id = input('get.business_id');
		$business_channel_model = new BusinessChannel();
		$info = $business_channel_model->alias('bc')->join('pay_channel pc', 'bc.channel_id=pc.id')
			->where('bc.business_id', $business_id)->field('bc.rate,pc.name')->select()->toArray();
		$rate = [];
		if (!empty($info))
		{
			foreach ($info as $key => $item)
			{
				$rate[] = $item['name'] . ':' . ($item['rate'] * 100) . '%';
			}
		}
		return json_encode($rate);
	}

	public function getChannel()
	{
		$channel_model = new Channel();
		$info = $channel_model->where('status', 1)->select()->toArray();
		return $info;
	}

	//获取码商跑量
	public function getCardNum($car_business_id, $type)
	{
		$order_model = new DB2Order();
		$where = [];
		$where[] = ['card_business_id', '=', $car_business_id];
		$where[] = ['status', '=', 2];
		if ($type == 1)
		{
			$where[] = ['success_time', '>=', date('Y-m-d 00:00:00')];
		}
		elseif ($type == 2)
		{
			$where[] = ['success_time', '>=', date('Y-m-d 00:00:00', strtotime('-1 day'))];
			$where[] = ['success_time', '<', date('Y-m-d 00:00:00')];
		}
		$info = [];
		$info['pay_amount'] = $order_model->where($where)->sum('pay_amount');
		$info['rate_amount'] = $order_model->where($where)->sum('rate_amount');
		return $info;
	}

	//获取商户跑量
	public function getBusinessNum($business_id, $type)
	{
		$order_model = new DB2Order();
		$where = [];
		$business_model = new Business();
		$business = $business_model->where('id', $business_id)->find();
		if ($business['type'] == 4)
		{
			$where[] = ['sub_business_id', '=', $business_id];
		}
		else
		{
			$where[] = ['business_id', '=', $business_id];
		}
		$where[] = ['status', '=', 2];
		if ($type == 1)
		{
			$where[] = ['success_time', '>=', date('Y-m-d 00:00:00')];
		}
		elseif ($type == 2)
		{
			$where[] = ['success_time', '>=', date('Y-m-d 00:00:00', strtotime('-1 day'))];
			$where[] = ['success_time', '<', date('Y-m-d 00:00:00')];
		}
		$info = [];
		$pay_amount = $order_model->where($where)->sum('pay_amount');
		$info['pay_amount'] = $pay_amount ? $pay_amount : 0;
		$rate_amount = $order_model->where($where)->sum('rate_amount');
		$info['rate_amount'] = $rate_amount ? $rate_amount : 0;
		$info['realname'] = $business['realname'];
		return $info;
	}

	//给商户增加额度
	public function cardBusinessQuota($business_id, $money, $type, $business_type)
	{
		//        $business_id = input('get.business_id');
//        $money = input('get.money');
//        $type = input('get.type');
//        $business_type = input('get.business_type');
		$business_model = new Business();
		//商户
		$where = [];
		$where[] = ['id', '=', $business_id];

		$card_business = $business_model->where($where)->find();
		if (!$card_business)
		{
			return ['code' => 0, 'msg' => '商户不存在'];
		}
		$t = '+';
		if ($type == 1)
		{
			$quota = $card_business['allow_withdraw'] + $money;
		}
		else
		{
			$quota = $card_business['allow_withdraw'] - $money;
			$t = '-';
		}
		// $result = $business_model->where($where)->save(['allow_withdraw'=> $quota]);
		// if(!$result){
		//     return ['code'=>0,'msg'=>'更新失败请联系管理员'];
		// }
		BusinessService::changeAllowWithdraw($business_id, $t . $money, 3, $item_id = 0, $remark = '机器人上分');
		return ['code' => 1, 'msg' => '更新成功', 'last_quota' => $quota];
	}

	//获取卡商的监控额度 或者商户的可提现金额
	public function getQuotaAllow($business_id, $business_type)
	{
		//        $business_id = input('get.business_id');
//        $business_type = input('get.business_type');
		$business_model = new Business();
		//卡商
		if ($business_type == 1)
		{
			$where = [];
			$where[] = ['id', '=', $business_id];
			$where[] = ['type', '=', 2];
			$card_business = $business_model->where($where)->find();
			if (!$card_business)
			{
				return ['code' => 0, 'msg' => '卡商不存在'];
			}
			return ['code' => 1, 'msg' => '码商监控额度', 'last_quota' => $card_business['quota']];
		}
		else
		{
			//商户
			$where = [];
			$where[] = ['id', '=', $business_id];
			$where[] = ['type', '=', 4];
			$card_business = $business_model->where($where)->find();
			if (!$card_business)
			{
				return ['code' => 0, 'msg' => '商户不存在'];
			}
			return ['code' => 1, 'msg' => '商户可提现金额', 'last_quota' => $card_business['allow_withdraw']];
		}
	}

	//获取卡商跑量 或者商户跑量
	public function getRj($business_id, $business_type)
	{
		//        $business_id = input('get.business_id');
//        $business_type = input('get.business_type');
		$order_model = new DB2Order();
		//卡商
		if ($business_type == 1)
		{
			$where = [];
			$where[] = ['card_business_id', '=', $business_id];
			//            $where[] = ['business_id', 'in', [10301,11266]];
			$where[] = ['success_time', '>=', date('Y-m-d 00:00:00', strtotime('-1 day'))];
			$where[] = ['success_time', '<', date('Y-m-d 00:00:00')];
			$total_amount = $order_model->where($where)->sum('pay_amount');
			$channel_list = $order_model->where($where)->group('channel_id')->select();
			$channel_info = Channel::where('status', 1)->select();
			$data = [];
			if ($channel_list)
			{
				foreach ($channel_list as $key => $value)
				{
					$where = [];
					$where[] = ['card_business_id', '=', $business_id];
					//                    $where[] = ['business_id', 'in', [10301,11266]];
					$where[] = ['success_time', '>=', date('Y-m-d 00:00:00', strtotime('-1 day'))];
					$where[] = ['success_time', '<', date('Y-m-d 00:00:00')];
					$where[] = ['channel_id', '=', $value['channel_id']];
					$data[$key]['pay_amount'] = $order_model->where($where)->sum('pay_amount');
					$data[$key]['name'] = '';
					foreach ($channel_info as $item)
					{
						if ($value['channel_id'] == $item['id'])
						{
							$data[$key]['name'] = $item['name'];
						}
					}
				}
			}
			return ['code' => 1, 'msg' => '码商跑量日结', 'total_amount' => $total_amount, 'data' => $data];
		}
		else
		{
			$where = [];
			$where[] = ['sub_business_id', '=', $business_id];
			//            $where[] = ['business_id', 'in', [10301,11266]];
			$where[] = ['success_time', '>=', date('Y-m-d 00:00:00', strtotime('-1 day'))];
			$where[] = ['success_time', '<', date('Y-m-d 00:00:00')];
			$total_amount = $order_model->where($where)->sum('pay_amount');
			$channel_list = $order_model->where($where)->group('channel_id')->select();
			$channel_info = Channel::where('status', 1)->select();
			$data = [];
			if ($channel_list)
			{
				foreach ($channel_list as $key => $value)
				{
					$where = [];
					$where[] = ['sub_business_id', '=', $business_id];
					//                    $where[] = ['business_id', 'in', [10301,11266]];
					$where[] = ['success_time', '>=', date('Y-m-d 00:00:00', strtotime('-1 day'))];
					$where[] = ['success_time', '<', date('Y-m-d 00:00:00')];
					$where[] = ['channel_id', '=', $value['channel_id']];
					$data[$key]['pay_amount'] = $order_model->where($where)->sum('pay_amount');
					$data[$key]['name'] = '';
					foreach ($channel_info as $item)
					{
						if ($value['channel_id'] == $item['id'])
						{
							$data[$key]['name'] = $item['name'];
						}
					}
				}
			}
			return ['code' => 1, 'msg' => '商户跑量日结', 'total_amount' => $total_amount, 'data' => $data];
		}
	}

	public function today_fee()
	{
		$key = 'admin_data_today_fee';

		if ($data = $this->redis->get($key))
		{
			return $this->returnData($data);
		}


		$data = [];

		// --------------------------------------------------------------------------
		// 今日平台总费用
		$where = [];
		$where[] = ['status', '>', 0];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
		$data['today_fee'] = DB2Order::where($where)->sum('fee');
		// echo DB2Order::where($where)->fetchSql(1)->sum('fee');

		$this->redis->set($key, $data, getDataCacheTime());

		return $this->returnData($data);
	}

	public function system_fee()
	{
		$key = 'admin_data_yesterday_fee_' . date('Ymd');

		if ($this->redis->get($key))
		{
			$yesterday_data = $this->redis->get($key);
		}
		else
		{
			$yesterday_data = [];
			// --------------------------------------------------------------------------
			// 昨日平台总费用
			$where = [];
			$where[] = ['status', '>', 0];
			$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
			$where[] = ['success_time', '<', date('Y-m-d')];
			$yesterday_data['yesterday_fee'] = DB2Order::where($where)->sum('fee');
		}

		$key = 'admin_data_today_fee';

		if ($this->redis->get($key))
		{
			$today_data = $this->redis->get($key);
		}
		else
		{
			$today_data = [];
			// --------------------------------------------------------------------------
			// 今日平台总费用
			$where = [];
			$where[] = ['status', '>', 0];
			$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
			$today_data['today_fee'] = DB2Order::where($where)->sum('fee');
		}

		$es_finance = new ESFinance;

		$begin_date = date('Y-m-d', strtotime('-30 day'));

		$title = [];
		for ($i = 0; $i < 29; $i++)
		{
			$title[] = date('Y-m-d', strtotime("{$begin_date} +{$i} day"));
		}

		$total_order = [];
		$total_fee = [];
		foreach ($title as $value)
		{
			$where = [];
			$where[] = ['date', '=', $value];

			$total_order[] = \app\model\FinanceBusiness::where($where)->sum('success_order');
			$total_fee[] = \app\model\FinanceBusiness::where($where)->sum('success_fee');
		}

		$title[] = date('Y-m-d');

		$where = [];
		$where[] = ['status', '>', 0];
		$where[] = ['success_time', '>=', date('Y-m-d')];

		// $total_order[] = $es_finance->count($where, 'id');
		// $total_fee[] = $es_finance->sum($where, 'fee');
		$total_order[] = Order::where($where)->count('id');
		$total_fee[] = Order::where($where)->sum('fee');


		$this_month_begin = date('Y-m-01 00:00:00');
		$this_month_end = date('Y-m-d H:i:s');
		$last_month_begin = date('Y-m-01 00:00:00', strtotime($this_month_begin) - 1);
		$last_month_end = date('Y-m-d H:i:s', strtotime($this_month_begin) - 1);

		$where = [];
		$where[] = ['status', '>', 0];
		$where[] = ['success_time', '>=', $last_month_begin];
		$where[] = ['success_time', '<=', $last_month_end];

		$last_month_order = $es_finance->count($where, 'id');
		$last_month_fee = $es_finance->sum($where, 'fee');
		// $last_month_order = Order::where($where)->count('id');
		// $last_month_fee = Order::where($where)->sum('fee');

		$where = [];
		$where[] = ['status', '>', 0];
		$where[] = ['success_time', '>=', $this_month_begin];
		$where[] = ['success_time', '<=', $this_month_end];

		$month_order = $es_finance->count($where, 'id');
		$month_fee = $es_finance->sum($where, 'fee');
		// $month_order = Order::where($where)->count('id');
		// $month_fee = Order::where($where)->sum('fee');

		$data = [
			'title' => $title,
			'total_order' => $total_order,
			'total_fee' => $total_fee,
			'last_month_order' => $last_month_order,
			'last_month_fee' => $last_month_fee,
			'month_order' => $month_order,
			'month_fee' => $month_fee,
		];

		return json_encode(['code' => 1, 'msg' => '平台费用', 'today_fee' => $today_data['today_fee'], 'yesterday_fee' => $yesterday_data['yesterday_fee'], 'month_fee' => $data['month_fee'], 'last_month_fee' => $data['last_month_fee']]);
	}
}
