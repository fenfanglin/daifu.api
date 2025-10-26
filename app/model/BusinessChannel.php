<?php
namespace app\model;

use app\extend\common\BaseModel;

class BusinessChannel extends BaseModel
{
	// protected $connection = 'mysql_base';

	protected $table = 'pay_business_channel';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	// 检查参数是否重复
	protected $unique_field = [];


	// 状态：1开启 -1关闭
	const STATUS = [
		-1 => '关闭',
		1 => '开启',
	];
	const STATUS_CLASS = [
		-1 => 'text-warning',
		1 => '',
	];

	// 随机金额 -1关闭 1加随机金额 2减随机金额
	const RANDOM_AMOUNT = [
		-1 => '',
		1 => '加随机金额',
		2 => '减随机金额',
	];
	const RANDOM_AMOUNT_CLASS = [
		-1 => '',
		1 => 'text-success',
		2 => 'text-warning',
	];

	// 回调金额 1实付金额 2提交金额
	const NOTIFY_AMOUNT = [
		1 => '实付金额',
		2 => '提交金额',
	];
	const NOTIFY_AMOUNT_CLASS = [
		1 => 'text-success',
		2 => 'text-warning',
	];



	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}

	public function channel()
	{
		return $this->belongsTo(Channel::class, 'channel_id', 'id');
	}
}