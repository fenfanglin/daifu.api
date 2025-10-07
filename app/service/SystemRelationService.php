<?php
namespace app\service;

use app\extend\common\Common;
use app\model\Relation_Business;

class SystemRelationService
{
	// -------------------------------------------------------------------------------------------------------------
	/**
	 * 获取JQK绑定代付系统的商户id
	 * $business_id: JQK系统商户id
	 * return: 代付系统商户id
	 */
	public static function getJqkBindingDaifuBusinessId($business_id)
	{
		$where = [];
		$where[] = ['business_id1', '=', $business_id];
		$where[] = ['type1', '=', 1]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type2', '=', 2]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			return $model->business_id2;
		}

		$where = [];
		$where[] = ['business_id2', '=', $business_id];
		$where[] = ['type2', '=', 1]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type1', '=', 2]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			return $model->business_id1;
		}

		return false;
	}

	/**
	 * JQK商户绑定代付商户
	 * $jqk_business_id: JQK系统商户id
	 * $daifu_business_id: 代付系统商户id
	 */
	public static function jqkBusinessBindingDaifuBusiness($jqk_business_id, $daifu_business_id)
	{
		$check = self::getJqkBindingDaifuBusinessId($jqk_business_id);
		if ($check != false)
		{
			return false;
		}

		$model = new Relation_Business;
		$model->business_id1 = $jqk_business_id;
		$model->type1 = 1; //类型：1JQKPAY系统 2代付系统
		$model->business_id2 = $daifu_business_id;
		$model->type2 = 2; //类型：1JQKPAY系统 2代付系统
		$model->status = 1;
		$model->save();

		return true;
	}

	/**
	 * JQK商户解绑代付商户
	 * $business_id: JQK系统商户id
	 */
	public static function jqkBusinessUnbindingDaifuBusiness($business_id)
	{
		$where = [];
		$where[] = ['business_id1', '=', $business_id];
		$where[] = ['type1', '=', 1]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type2', '=', 2]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			$model->delete();
			return true;
		}

		$where = [];
		$where[] = ['business_id2', '=', $business_id];
		$where[] = ['type2', '=', 1]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type1', '=', 2]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			$model->delete();
			return true;
		}

		return false;
	}

	// -------------------------------------------------------------------------------------------------------------
	/**
	 * 获取代付绑定JQK系统的商户id
	 * $business_id: 代付系统商户id
	 * return: JQK系统商户id
	 */
	public static function getDaifuBindingJqkBusinessId($business_id)
	{
		$where = [];
		$where[] = ['business_id1', '=', $business_id];
		$where[] = ['type1', '=', 2]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type2', '=', 1]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			return $model->business_id2;
		}

		$where = [];
		$where[] = ['business_id2', '=', $business_id];
		$where[] = ['type2', '=', 2]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type1', '=', 1]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			return $model->business_id1;
		}

		return false;
	}

	/**
	 * 代付商户绑定JQK商户
	 * $daifu_business_id: 代付系统商户id
	 * $jqk_business_id: JQK系统商户id
	 */
	public static function daifuBusinessBindingJqkBusiness($daifu_business_id, $jqk_business_id)
	{
		$check = self::getDaifuBindingJqkBusinessId($daifu_business_id);
		if ($check != false)
		{
			return false;
		}

		$model = new Relation_Business;
		$model->business_id1 = $daifu_business_id;
		$model->type1 = 2; //类型：1JQKPAY系统 2代付系统
		$model->business_id2 = $jqk_business_id;
		$model->type2 = 1; //类型：1JQKPAY系统 2代付系统
		$model->status = 1;
		$model->save();

		return true;
	}

	/**
	 * 代付商户解绑JQK商户
	 * $business_id: 代付系统商户id
	 */
	public static function daifuBusinessUnbindingJqkBusiness($business_id)
	{
		$where = [];
		$where[] = ['business_id1', '=', $business_id];
		$where[] = ['type1', '=', 2]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type2', '=', 1]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			$model->delete();
			return true;
		}

		$where = [];
		$where[] = ['business_id2', '=', $business_id];
		$where[] = ['type2', '=', 2]; //类型：1JQKPAY系统 2代付系统
		$where[] = ['type1', '=', 1]; //类型：1JQKPAY系统 2代付系统

		$model = Relation_Business::where($where)->find();
		if ($model)
		{
			$model->delete();
			return true;
		}

		return false;
	}
}