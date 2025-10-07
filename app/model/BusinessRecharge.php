<?php
namespace app\model;

use app\extend\common\BaseModel;

class BusinessRecharge extends BaseModel
{
	protected $table = 'pay_business_recharge';
	
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	
	// 检查参数是否重复
	protected $unique_field = [];
	
	
	// 状态：-1未支付 1已支付
	const STATUS = [
		-1 => '未支付',
		1 => '已支付',
	];
	const STATUS_CLASS = [
		-1 => '',
		1 => 'text-success',
	];
	
	//充值方式：-1后台充值 1Usdt
	const RECHARGE_TYPE = [
		-1 => '总后台操作',
		1 => 'Usdt',
	];
	const RECHARGE_TYPE_CLASS = [
		-1 => '',
		1 => '',
	];
	
	
	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}
	
	/**
	 * 新增前
	 */
	public static function onBeforeInsert($model)
	{
		parent::onBeforeInsert($model);
		
		// 自动生成order_no参数
		$check = true;
		while ($check)
		{
			$model->order_no = 'RC' . date('YmdHis') . rand(1000, 9999);
			$check = self::where(['order_no' => $model->order_no])->count('id');
		}
	}
}