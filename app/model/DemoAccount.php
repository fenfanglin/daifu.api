<?php
namespace app\model;

use app\extend\common\Common;
use app\extend\common\BaseModel;

class DemoAccount extends BaseModel
{
	protected $table = 'pay_demo_account';
	
	// 检查参数是否重复
	protected $unique_field = [
		'account' => '收款账户',
	];
	
	
	// 状态：1开启 -1关闭
	const STATUS = [
		1 => '开启',
		-1 => '关闭',
	];
	const STATUS_CLASS = [
		1 => '',
		-1 => 'text-danger',
	];
	
	
	public function channel()
	{
		return $this->belongsTo(Channel::class, 'channel_id', 'id');
	}
	
	public function systemBank()
	{
		return $this->belongsTo(SystemBank::class, 'system_bank_id', 'id');
	}
}