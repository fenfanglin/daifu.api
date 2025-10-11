<?php
namespace app\model;

use app\extend\common\BaseModel;

class Channel extends BaseModel
{
	protected $table = 'pay_channel';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	// 检查参数是否重复
	protected $unique_field = [];


	// 状态：1开启 -1关闭
	const STATUS = [
		1 => '开启',
		-1 => '关闭',
	];
	const STATUS_CLASS = [
		1 => '',
		-1 => 'text-danger',
	];

	// 通道类型：1三方通道
	const TYPE = [
		1 => '三方通道',
	];
	const TYPE_CLASS = [
		1 => '',
	];


}