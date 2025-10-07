<?php
namespace app\model;

use app\extend\common\BaseModel;

class BusinessWithdraw extends BaseModel
{
	protected $table = 'pay_business_withdraw';
	
	protected $createTime = 'create_time';
	protected $updateTime = false;
	
	// 检查参数是否重复
	protected $unique_field = [];
	
	
	// 状态：-1未审核 1成功 2审核失败
	const STATUS = [
		-1 => '未审核',
		1 => '成功',
		2 => '审核失败',
	];
	const STATUS_CLASS = [
		-1 => 'text-info',
		1 => 'text-success',
		2 => 'text-danger',
	];
	
	//提现方式：-1四方操作 1银行卡 2Usdt 3支付宝
	const TYPE = [
		-1 => '四方操作',
		1 => '银行卡',
		2 => 'Usdt',
		3 => '支付宝',
	];
	const TYPE_CLASS = [
		-1 => '',
		1 => '',
		2 => '',
		3 => '',
	];
	
	
	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}
	
	public function subBusiness()
	{
		return $this->belongsTo(Business::class, 'sub_business_id', 'id');
	}
}