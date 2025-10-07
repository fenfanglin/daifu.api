<?php

namespace app\service;

use app\extend\common\Common;
use app\model\Order;
use app\model\ChannelAccount;

class OrderService
{
	/**
	 * 报错
	 */
	public static function error($msg, $data = [])
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg, 'data' => $data], 'orderService_error');

		// Common::error($msg);
	}

	/**
	 * 生成签名
	 */
	public static function createSign($params, $secret_key)
	{
		unset($params['sign']);

		ksort($params); //字典排序

		$str = '';
		foreach ($params as $key => $value)
		{
			$str .= strtolower($key) . '=' . $value . '&';
		}

		$str .= 'key=' . $secret_key;

		return strtoupper(md5($str));
	}

	/**
	 * 处理完成订单
	 */
	public static function completeOrder($order_id)
	{
		$order = Order::where('id', $order_id)->find();
		if (!$order)
		{
			self::error('订单不存在', ['order_id' => $order_id]);
		}

		if ($order->status > 0)
		{
			self::error('订单已处理过', ['order_id' => $order_id]);
		}

		$_order_status = $order->status;

		$order->success_time = date('Y-m-d H:i:s');
		$order->status = 1; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
		if (!$order->save())
		{
			self::error('完成订单保存失败', ['order_id' => $order_id]);
		}

		$info = json_decode($order->info, true);

		// - 扣代理和工作室费用
		// - 如果订单是支付失败状态（-2）就扣商户费用

		// - 扣代理和工作室费用
		// 扣代理余额
		$remark = "扣{$info['agent_commission']}固定费用，{$info['agent_order_fee']}订单费用";
		BusinessService::changeMoney($order->business_id, -($info['agent_commission'] + $info['agent_order_fee']), $type = 2, $order_id, $remark);

		// 返工作室可提现金额--订单金额
		$remark = "返{$order->amount}订单金额";
		BusinessService::changeAllowWithdraw($order->card_business_id, +$order->amount, $type = 2, $order_id, $remark);

		// 返工作室可提现金额--订单费用
		$remark = "返{$info['card_commission']}固定费用，{$info['card_order_fee']}订单费用";
		BusinessService::changeAllowWithdraw($order->card_business_id, +($info['card_commission'] + $info['card_order_fee']), $type = 1, $order_id, $remark);


		// - 如果订单是支付失败状态（-2）就扣商户费用
		if ($_order_status == -2)
		{
			// 扣商户余额--订单金额
			$remark = "扣{$order->amount}订单金额";
			BusinessService::changeAllowWithdraw($order->sub_business_id, -$order->amount, 2, $order->id, $remark);

			// 扣商户余额--订单费用
			$remark = "扣{$info['business_commission']}固定费用，{$info['business_order_fee']}订单费用";
			BusinessService::changeAllowWithdraw($order->sub_business_id, -($info['business_commission'] + $info['business_order_fee']), 1, $order->id, $remark);
		}


		// 回调订单
		self::sendNotify($order_id);
	}

	/**
	 * 订单设为失败
	 */
	public static function failOrder($order_id)
	{
		$order = Order::where('id', $order_id)->find();
		if (!$order)
		{
			self::error('订单不存在', ['order_id' => $order_id]);
		}

		if ($order->status == -2)
		{
			self::error('订单已处理过', ['order_id' => $order_id]);
		}

		$_order_status = $order->status;

		$order->success_time = NULL;
		$order->status = -2; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
		if (!$order->save())
		{
			self::error('完成订单保存失败', ['order_id' => $order_id]);
		}

		$info = json_decode($order->info, true);

		// - 返回商户费用
		// - 如果订单是成功就返回代理和工作室费用

		// - 返回商户费用
		// 返商户余额--订单金额
		$remark = "返{$order->amount}订单金额";
		BusinessService::changeAllowWithdraw($order->sub_business_id, +$order->amount, 2, $order->id, $remark);

		// 返商户余额--订单费用
		$remark = "返{$info['business_commission']}固定费用，{$info['business_order_fee']}订单费用";
		BusinessService::changeAllowWithdraw($order->sub_business_id, +($info['business_commission'] + $info['business_order_fee']), 1, $order->id, $remark);


		// - 如果订单是成功就返回代理和工作室费用
		if ($_order_status > 0)
		{
			// 返代理余额
			$remark = "返{$info['agent_commission']}固定费用，{$info['agent_order_fee']}订单费用";
			BusinessService::changeMoney($order->business_id, +($info['agent_commission'] + $info['agent_order_fee']), $type = 2, $order_id, $remark);

			// 扣工作室可提现金额--订单金额
			$remark = "扣{$order->amount}订单金额";
			BusinessService::changeAllowWithdraw($order->card_business_id, -$order->amount, $type = 2, $order_id, $remark);

			// 扣工作室可提现金额--订单费用
			$remark = "扣{$info['card_commission']}固定费用，{$info['card_order_fee']}订单费用";
			BusinessService::changeAllowWithdraw($order->card_business_id, -($info['card_commission'] + $info['card_order_fee']), $type = 1, $order_id, $remark);
		}

		// 回调订单
		self::sendNotify($order_id);
	}

	/**
	 * 订单设为未支付
	 */
	public static function notPayOrder($order_id)
	{
		$order = Order::where('id', $order_id)->find();
		if (!$order)
		{
			self::error('订单不存在', ['order_id' => $order_id]);
		}

		if ($order->status == -1)
		{
			self::error('订单已处理过', ['order_id' => $order_id]);
		}

		$_order_status = $order->status;

		$order->success_time = NULL;
		$order->status = -1; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
		if (!$order->save())
		{
			self::error('完成订单保存失败', ['order_id' => $order_id]);
		}

		$info = json_decode($order->info, true);

		// - 如果订单是支付失败状态（-2）就扣商户费用
		// - 如果订单是成功（>0）就退回代理和工作室费用

		// - 如果订单是支付失败状态（-2）就扣商户费用
		if ($_order_status == -2)
		{
			// 扣商户余额--订单金额
			$remark = "扣{$order->amount}订单金额";
			BusinessService::changeAllowWithdraw($order->sub_business_id, -$order->amount, 2, $order->id, $remark);

			// 扣商户余额--订单费用
			$remark = "扣{$info['business_commission']}固定费用，{$info['business_order_fee']}订单费用";
			BusinessService::changeAllowWithdraw($order->sub_business_id, -($info['business_commission'] + $info['business_order_fee']), 1, $order->id, $remark);
		}

		// - 如果订单是成功（>0）就退回代理和工作室费用
		if ($_order_status > 0)
		{
			// 返代理余额
			$remark = "返{$info['agent_commission']}固定费用，{$info['agent_order_fee']}订单费用";
			BusinessService::changeMoney($order->business_id, +($info['agent_commission'] + $info['agent_order_fee']), $type = 2, $order_id, $remark);

			// 扣工作室可提现金额--订单金额
			$remark = "扣{$order->amount}订单金额";
			BusinessService::changeAllowWithdraw($order->card_business_id, -$order->amount, $type = 2, $order_id, $remark);

			// 扣工作室可提现金额--订单费用
			$remark = "扣{$info['card_commission']}固定费用，{$info['card_order_fee']}订单费用";
			BusinessService::changeAllowWithdraw($order->card_business_id, -($info['card_commission'] + $info['card_order_fee']), $type = 1, $order_id, $remark);
		}

		// 回调订单
		self::sendNotify($order_id);
	}

	/**
	 * 回调订单
	 */
	public static function sendNotify($order_id, $return_data = 0)
	{
		$order = Order::where('id', $order_id)->find();
		if (!$order)
		{
			self::error('订单不存在2', ['order_id' => $order_id]);
		}

		$business_id = $order->sub_business_id;
		$secret_key = $order->subBusiness->secret_key;


		// -----------------------------------------------------------------------------
		// 回调信息
		$params = [];
		$params['mchid'] = $business_id;
		$params['order_no'] = $order->order_no;
		$params['out_trade_no'] = $order->out_trade_no;

		// 1银行卡 2usdt 3支付宝
		if (in_array($order['account_type'], [1, 3]))
		{
			$params['amount'] = $order->amount;
		}
		else
		{
			$params['amount'] = $order->amount;
			$params['usdt_amount'] = $order->usdt_amount;
			$params['usdt_rate'] = $order->usdt_rate;
		}

		$params['attach'] = $order->attach ?? '';
		$params['remark'] = $order->remark ?? '';
		$params['pay_remark'] = $order->pay_remark ?? '';
		$params['image_url'] = $order->image_url ?? '';

		if ($order->status > 0)
		{
			$params['success_time'] = strtotime($order->success_time);
			$params['status'] = 'SUCCESS';
		}
		elseif ($order->status == -2)
		{
			$params['success_time'] = 0;
			$params['status'] = 'FAIL';
		}
		else
		{
			$params['success_time'] = 0;
			$params['status'] = 'NOTPAY';
		}
		$params['sign'] = self::createSign($params, $secret_key);
		// -----------------------------------------------------------------------------

		$url = $order->notify_url;
		$res = Common::curl($url, $params, $second = 30, $is_debug = true);

		Common::writeLog(['params' => $params, 'res' => $res], 'notify_send');

		$order->notify_num++;
		$order->last_notify_time = date('Y-m-d H:i:s');
		$order->save();

		if ($order->status != -2)
		{
			if (strpos(strtolower($res['response']), 'success') !== false)
			{
				$order->status = 2; //状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
				if (!$order->save())
				{
					self::error('回调保存失败', ['order_id' => $order_id]);
				}
			}
		}

		if ($return_data)
		{
			$res['params'] = $params;

			return $res;
		}
	}
}
