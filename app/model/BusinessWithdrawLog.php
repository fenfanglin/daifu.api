<?php
namespace app\model;

use app\extend\common\BaseModel;

class BusinessWithdrawLog extends BaseModel
{
	protected $table = 'pay_business_withdraw_log';

	protected $createTime = 'create_time';

	// 自动生成no参数
	protected $generate_no = false;

	// 检查参数是否重复
	protected $unique_field = [];


	//类型：1订单费用 2订单金额 3代理操作
	const TYPE = [
		1 => '订单费用',
		2 => '订单金额',
		3 => '代理操作',
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

	public function businessWithdraw()
	{
		return $this->belongsTo(BusinessWithdraw::class, 'item_id', 'id');
	}

	public function order()
	{
		return $this->belongsTo(Order::class, 'item_id', 'id');
	}
}
