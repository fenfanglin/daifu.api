<?php
namespace app\pay\controller;

use app\extend\common\Common;
use app\model\Order;
use app\model\ChannelAccount;

class OrderUsdtController extends AuthController
{
	private $channel_id = 102;

	/**
	 * 下单（只能通过order/create请求，直接请求报错‘拒绝访问’）
	 */
	public function pay($data)
	{
		// 安全验证（只能通过order/create请求）
		$this->checkSecurity($data);

		$params = $data['params'];
		$timeout = $data['timeout'];
		$random_amount = $data['random_amount'];
		$random_amount_min = $data['random_amount_min'];
		$random_amount_max = $data['random_amount_max'];
		$usdt_rate = $data['usdt_rate'];
		$business_channel_rate = $data['business_channel_rate'];
		$sub_business_id = $data['sub_business_id'];
		$card_business_ids = $data['card_business_ids'];
		$sub_business_channel_rate = $data['sub_business_channel_rate'];

		if ($usdt_rate <= 0)
		{
			return $this->returnError('Usdt汇率不正确');
		}

		$mchid = $params['mchid'];

		// 检查商户订单号是否重复
		$check = Order::checkOutTradeNo($params['out_trade_no']);
		if (!$check)
		{
			return $this->returnError('重复订单号');
		}

		// 获取收款账号
		$account = $this->getChannelAccount($mchid, $params['amount'], $card_business_ids);

		// 兑换出usdt金额
		$usdt_amount = $params['amount'] / $usdt_rate;
		$usdt_amount = number_format($usdt_amount, 2, '.', '');

		// 获取可下单金额
		$usdt_amount = $this->getAmount($mchid, $account, $usdt_amount, $random_amount, $random_amount_min, $random_amount_max);

		// 如果关闭随机金额，提交金额就是实付金额，不能用usdt兑换出来，会有差数
		if ($random_amount == -1) //随机金额关闭
		{
			// 实付金额就是提交金额
			$pay_amount = $params['amount'];
		}
		else
		{
			// 兑换出人民币金额
			$pay_amount = $usdt_amount * $usdt_rate;
			$pay_amount = number_format($pay_amount, 2, '.', '');
		}

		// 费用
		$fee = $pay_amount * $business_channel_rate;
		$fee = number_format($fee, 4, '.', '');

		// 过期时间
		$expire_time = date('Y-m-d H:i:s', time() + $timeout * 60);

		$order = new Order();
		$order->business_id = $mchid;
		$order->sub_business_id = $sub_business_id;
		$order->card_business_id = $account->card_business_id;
		$order->channel_id = $this->channel_id;
		$order->channel_account_id = $account->id;
		$order->account_title = $account->channel->title;
		$order->account_name = $account->name;
		$order->account = $account->account;
		$order->account_sub = substr($account->account, -4);
		$order->post_amount = $params['amount'];
		$order->pay_amount = $pay_amount;
		$order->usdt_rate = $usdt_rate;
		$order->usdt_amount = $usdt_amount;
		$order->fee = $fee;
		$order->out_trade_no = $params['out_trade_no'];
		$order->ip = Common::getClientIp();
		$order->notify_url = $params['notify_url'];
		$order->attach = isset($params['attach']) ? $params['attach'] : '';
		$order->expire_time = $expire_time;
		$order->status = -1;

		if ($sub_business_id > 0)
		{
			// 下级商户费率金额
			$rate_amount = $pay_amount * $sub_business_channel_rate;
			$rate_amount = number_format($rate_amount, 2, '.', '');

			// 下级商户可提现金额
			$allow_withdraw = $pay_amount - $rate_amount;
			$allow_withdraw = number_format($allow_withdraw, 2, '.', '');

			$order->sub_business_channel_rate = $sub_business_channel_rate;
			$order->rate_amount = $rate_amount;
			$order->allow_withdraw = $allow_withdraw;
		}

		if (!$order->save())
		{
			return $this->returnError('生成订单失败');
		}


		$data = [];
		$data['order_no'] = $order->order_no;
		$data['out_trade_no'] = $order->out_trade_no;
		$data['pay_amount'] = $order->pay_amount;
		$data['usdt_amount'] = $order->usdt_amount;
		$data['usdt_rate'] = $order->usdt_rate;
		$data['expire_time'] = strtotime($order->expire_time);
		$data['pay_url'] = config('app.order_url') . 'pay/index?id=' . $order->order_no;

		return $this->returnData($data);
	}

	// /**
	// * 获取收款账号
	// * 返回收款账号对象
	// */
	// private function getChannelAccount($mchid, $amount, $card_business_ids)
	// {
	// 	// 获取商户所有匹配金额的收款账号
	// 	// 按权重数量生成列表
	// 	// 随机顺序排列
	// 	// 返回第一个收款账号

	// 	// 获取商户所有匹配金额的收款账号
	// 	$where = [];
	// 	$where[] = ['channel_id', '=', $this->channel_id];
	// 	$where[] = ['business_id', '=', $mchid];
	// 	$where[] = ['min_amount', '<=', $amount];
	// 	$where[] = ['max_amount', '>=', $amount];
	// 	$where[] = ['status', '=', 1];

	// 	$list = ChannelAccount::where($where)->select();
	// 	if (!$list)
	// 	{
	// 		Common::error('无可用收款账号1');
	// 	}

	// 	// 按权重数量生成列表
	// 	$account_list = [];
	// 	foreach ($list as $value)
	// 	{
	// 		for ($i = 0; $i < $value['weight']; $i++)
	// 		{
	// 			$account_list[] = $value;
	// 		}
	// 	}

	// 	// 随机顺序排列
	// 	shuffle($account_list);

	// 	// 返回第一个收款账号
	// 	return $account_list[0];
	// }

	/**
	 * 获取收款账号
	 * 返回收款账号对象
	 */
	private function getChannelAccount($mchid, $amount, $card_business_ids)
	{
		// 获取商户所有匹配金额的收款账号，未下单状态
		// 如果未找到匹配，复原下单状态，再次获取

		\think\facade\Db::startTrans();
		try
		{

			// 获取商户所有匹配金额的收款账号，未下单状态
			$where = [];
			$where[] = ['channel_id', '=', $this->channel_id];
			$where[] = ['business_id', '=', $mchid];
			$where[] = ['min_amount', '<=', $amount];
			$where[] = ['max_amount', '>=', $amount];
			$where[] = ['status', '=', 1];

			if ($card_business_ids !== false)
			{
				if (is_array($card_business_ids) && count($card_business_ids) > 0)
				{
					$where[] = ['card_business_id', 'in', $card_business_ids];
				}
				else
				{
					$where[] = ['id', '=', 0]; //不显示
				}
			}

			// is_use是否已下单 1是 0否（用于轮询下单）
			$account = ChannelAccount::where($where)->where('is_use', 0)->find();
			// $this->orderError(ChannelAccount::where($where)->where('is_use', 0)->fetchSql(1)->find());
			if (!$account) //如果未找到匹配，复原下单状态
			{
				ChannelAccount::where($where)->save(['is_use' => 0]);

				// 再次获取商户所有匹配金额的收款账号，未下单状态
				$account = ChannelAccount::where($where)->where('is_use', 0)->find();
			}

			if (!$account)
			{
				throw new \Exception('无可用收款账号');
			}

			$account->is_use = 1;
			if (!$account->save())
			{
				throw new \Exception('无可用收款账号2');
			}

			\think\facade\Db::commit();

			return $account;

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			$this->orderError($e->getMessage());

		}
	}

	/**
	 * 获取可下单金额
	 */
	private function getAmount($mchid, $account, $post_amount, $random_amount, $random_amount_min, $random_amount_max)
	{
		// 订单金额检查重复
		// 商家通道，查看商家订单属于此通道是否有对应金额未支付
		// 商家收款账号，查看商家订单属于此收款账号是否有对应金额未支付

		// 检查金额逻辑
		// 如果没开启随机金额：只检查此金额的订单是否存在
		// 如果开始随机金额：
		// 	- 按加随机或者减随机，获取出订单未支付未过期，金额在随机金额范围内
		// 	- 按加随机或者减随机，生成随机金额数组[0.01, 0.02, ... , 0.98, 0.99]，随机顺序排列
		// 	- 循环，如果金额加随机未存在订单数组，返回金额

		$duplicate_check = $this->setting->order_amount_duplicate_check; //订单金额检查重复 1商家通道 2商家收款账号

		// 随机金额 -1关闭 1加随机金额 2减随机金额
		if ($random_amount == -1) //随机金额关闭
		{
			// 获取商户所有收款账号id，按权重数量写入缓存
			$where = [];
			$where[] = ['business_id', '=', $mchid];
			// $where[] = ['pay_amount', '=', $post_amount];
			$where[] = ['usdt_amount', '=', $post_amount];
			$where[] = ['expire_time', '>=', date('Y-m-d H:i:s')];
			$where[] = ['status', '=', -1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败

			// // 订单金额检查重复 1商家通道 2商家收款账号
			// if ($duplicate_check == 1)
			// {
			// 	$where[] = ['channel_id', '=', $this->channel_id];
			// }
			// else
			// {
			// 	$where[] = ['channel_account_id', '=', $account->id];
			// }

			// U直接按商家收款账号检查重复
			$where[] = ['channel_account_id', '=', $account->id];

			$check = Order::where($where)->count('id');
			if ($check)
			{
				Common::error('金额被占用，请输入其他金额或可稍等几分钟');
			}

			return $post_amount;
		}
		else
		{
			if ($random_amount == 1) //1加随机金额
			{
				$rand_min = min($random_amount_min, $random_amount_max);
				$rand_max = max($random_amount_min, $random_amount_max);
			}
			elseif ($random_amount == 2) //2减随机金额
			{
				$rand_min = min(-$random_amount_min, -$random_amount_max);
				$rand_max = max(-$random_amount_min, -$random_amount_max);
			}

			// ------------------------------------------------------------------------------------------
			// 获取出订单未支付未过期，金额在随机金额范围内 => 被占用金额
			$where = [];
			$where[] = ['business_id', '=', $mchid];
			// $where[] = ['pay_amount', '>=', $post_amount + $rand_min];
			// $where[] = ['pay_amount', '<=', $post_amount + $rand_max];
			$where[] = ['usdt_amount', '>=', $post_amount + $rand_min];
			$where[] = ['usdt_amount', '<=', $post_amount + $rand_max];
			$where[] = ['expire_time', '>=', date('Y-m-d H:i:s')];
			$where[] = ['status', '=', -1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败

			// // 订单金额检查重复 1商家通道 2商家收款账号
			// if ($duplicate_check == 1)
			// {
			// 	$where[] = ['channel_id', '=', $this->channel_id];
			// }
			// else
			// {
			// 	$where[] = ['channel_account_id', '=', $account->id];
			// }

			// U直接按商家收款账号检查重复
			$where[] = ['channel_account_id', '=', $account->id];

			// $list = Order::field('pay_amount')->where($where)->select()->toArray();
			$list = Order::field('usdt_amount')->where($where)->select()->toArray();

			// 被占用金额
			// $arr_order_amount = array_column($list, 'pay_amount');
			$arr_order_amount = array_column($list, 'usdt_amount');

			// ------------------------------------------------------------------------------------------
			// 生成随机金额数组
			$arr_random_amount = range($rand_min * 100, $rand_max * 100);

			// 随机顺序排列
			// shuffle($arr_random_amount);

			// 如果是减随机金额，把随机顺序翻转过来，目的是获取出金额最靠近提交金额
			if ($random_amount == 2) //2减随机金额
			{
				$arr_random_amount = array_reverse($arr_random_amount);
			}

			// ------------------------------------------------------------------------------------------
			// 找出可用的金额
			$pay_amount = 0;
			foreach ($arr_random_amount as $random_amount)
			{
				$tmp_amount = $post_amount + ($random_amount / 100);
				$tmp_amount = number_format($tmp_amount, 2, '.', '');

				if (!in_array($tmp_amount, $arr_order_amount))
				{
					$pay_amount = $tmp_amount;
					break;
				}
			}

			if (!$pay_amount)
			{
				Common::error('金额被占用，请输入其他金额或可稍等1分钟');
			}

			return $pay_amount;
		}
	}
}