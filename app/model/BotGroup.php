<?php
namespace app\model;

use app\extend\common\BaseModel;

class BotGroup extends BaseModel
{
	protected $table = 'pay_bot_group';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';


	// 状态：1启用 -1禁用
	const STATUS = [
		1 => '启用',
		-1 => '禁用',
	];
	const STATUS_CLASS = [
		1 => '',
		0 => 'text-danger',
	];

	const QUOTA_STATUS = [
		1 => '启用',
		-1 => '禁用',
	];

	const OPERATOR_STATUS = [
		1 => '操作员可操作',
		-1 => '所有人可操作'
	];

	const TYPE = [
		1 => '四方',
		2 => '商户',
		3 => '卡商',
		4 => '四方商户',
	];

	const BUSINESS_TYPE = [
		3 => '卡商',
		4 => '商户',
	];

	public function operator_status()
	{
		return $this->belongsTo(Role::class, 'role_id', 'id');
	}
}
