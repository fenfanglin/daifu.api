<?php
namespace app\model;

use app\extend\common\BaseModel;

class BusinessMoneyLog extends BaseModel
{
	protected $table = 'pay_business_money_log';
	
	protected $createTime = 'create_time';
	
	// 自动生成no参数
	protected $generate_no = false;
	
	// 检查参数是否重复
	protected $unique_field = [];
	
	
	//类型：1充值 2订单费用 3总后台操作
	const TYPE = [
		1 => '充值',
		2 => '订单费用',
		3 => '总后台操作',
	];
	const TYPE_CLASS = [
		1 => '',
		2 => '',
		3 => '',
	];
	
	
	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}
	
	public function businessRecharge()
	{
		return $this->belongsTo(BusinessRecharge::class, 'item_id', 'id');
	}
	
	public function order()
	{
		return $this->belongsTo(Order::class, 'item_id', 'id');
	}
}