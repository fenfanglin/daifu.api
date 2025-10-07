<?php
namespace app\service;

use app\extend\common\Common;
use app\model\Business;
use app\model\BusinessMoneyLog;
use app\model\BusinessWithdrawLog;

class BusinessService
{
	/**
	 * 更新商户余额（只添加记录不处理）
	 * type = 类型：1充值 2订单费用 3总后台操作
	 * item_id = 对象id（可以是充值id，订单id，后台员工账号id）
	 * 返回说明：
	 *		- code: 1成功 其他是失败
	 *		- msg: 错误信息
	 */
	public static function changeMoney($business_id, $fee, $type, $item_id = 0, $remark = '')
	{
		if ($fee == 0)
		{
			return false;
		}

		$business = Business::where('id', $business_id)->find();
		if (!$business)
		{
			return self::error('商户不存在', ['business_id' => $business_id]);
		}

		\think\facade\Db::startTrans();
		try
		{

			// -----------------------------------------------------------------------------
			// 添加记录
			$log = new BusinessMoneyLog;
			$log->business_id = $business_id;
			$log->type = $type; //类型：1充值 2订单费用 3总后台操作
			$log->money = $fee;
			// $log->money_before = $business->money;
			// $log->money_after = $business->money + $fee;
			$log->item_id = $item_id;
			$log->remark = $remark;
			$log->status = -1; //状态：-1未处理 1已处理
			if (!$log->save())
			{
				throw new \Exception('更新商户余额添加记录失败');
			}

			// // -----------------------------------------------------------------------------
			// // 更新商户余额
			// $business->money += $fee;
			// if (!$business->save())
			// {
			// 	throw new \Exception('更新商户余额失败');
			// }

			\think\facade\Db::commit();

			return self::success();

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			$type_str = isset(BusinessMoneyLog::TYPE[$type]) ? BusinessMoneyLog::TYPE[$type] : '';
			return self::error($e->getMessage(), ['business_id' => $business_id, 'fee' => $fee, 'type' => $type_str, 'item_id' => $item_id]);

		}
	}

	/**
	 * 更新商户可提现金额（只添加记录不处理）
	 * type = 类型：1订单金额 2商户提现 3四方操作
	 * item_id = 对象id（可以是提现id，订单id）
	 * 返回说明：
	 *		- code: 1成功 其他是失败
	 *		- msg: 错误信息
	 */
	public static function changeAllowWithdraw($sub_business_id, $money, $type, $item_id = 0, $remark = '')
	{
		if (!$sub_business_id)
		{
			return false;
		}

		if ($money == 0)
		{
			return false;
		}

		$business = Business::where('id', $sub_business_id)->find();
		if (!$business)
		{
			return self::error('商户不存在', ['sub_business_id' => $sub_business_id]);
		}

		\think\facade\Db::startTrans();
		try
		{

			// -----------------------------------------------------------------------------
			// 添加记录
			$log = new BusinessWithdrawLog;
			$log->business_id = $business->parent_id;
			$log->sub_business_id = $sub_business_id;
			$log->type = $type; //类型：1订单费用 2订单金额
			$log->money = $money;
			// $log->money_before = $business->money;
			// $log->money_after = $business->money + $money;
			$log->item_id = $item_id;
			$log->remark = $remark;
			$log->status = -1; //状态：-1未处理 1已处理
			if (!$log->save())
			{
				throw new \Exception('更新商户可提现金额添加记录失败');
			}

			\think\facade\Db::commit();

			return self::success();

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			$type_str = isset(BusinessWithdrawLog::TYPE[$type]) ? BusinessWithdrawLog::TYPE[$type] : '';
			return self::error($e->getMessage(), ['sub_business_id' => $sub_business_id, 'money' => $money, 'type' => $type_str, 'item_id' => $item_id]);

		}
	}

	/**
	 * 报错
	 */
	public static function error($msg, $data = [])
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg, 'data' => $data], 'orderService_error');

		// Common::error($msg);

		$data = [
			'code' => 0,
			'msg' => $msg,
		];

		return $data;
	}

	/**
	 * 成功
	 */
	public static function success()
	{
		$data = [
			'code' => 1,
			'msg' => '成功',
		];

		return $data;
	}
}
