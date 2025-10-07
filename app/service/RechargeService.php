<?php
namespace app\service;

use app\extend\common\Common;
use app\model\BusinessRecharge;

class RechargeService
{
	/**
	 * 报错
	 */
	public static function error($msg, $data = [])
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg, 'data' => $data], 'rechargeService_error');
		
		Common::error($msg);
	}
	
	/**
	 * 处理完成充值订单
	 */
	public static function completeRecharge($recharge_id, $usdt_transaction_id = '')
	{
		$recharge = BusinessRecharge::where('id', $recharge_id)->find();
		if (!$recharge)
		{
			self::error('充值订单不存在', ['recharge_id' => $recharge_id]);
		}
		
		if ($recharge->status != -1)
		{
			self::error('充值订单已处理过', ['recharge_id' => $recharge_id]);
		}
		
		$recharge->usdt_transaction_id = $usdt_transaction_id;
		$recharge->success_time = date('Y-m-d H:i:s');
		$recharge->status = 1; //状态：-1未支付 1成功，未回调 2成功，已回调 -2生成充值订单失败
		if (!$recharge->save())
		{
			self::error('完成充值订单保存失败', ['recharge_id' => $recharge_id]);
		}
		
		// 加商户余额
		BusinessService::changeMoney($recharge->business_id, $recharge->post_amount, $type = 1, $recharge_id);
	}
}
