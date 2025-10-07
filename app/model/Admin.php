<?php
namespace app\model;

use app\extend\common\BaseModel;

class Admin extends BaseModel
{
	protected $table = 'pay_admin';
	
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	
	// 检查参数是否重复
	protected $unique_field = [
		'username' => '登录账号',
		'phone' => '电话',
	];
	
	public $is_google_auth; //是否需要谷歌验证
	
	// 状态：1启用 -1禁用
	const STATUS = [
		1 => '启用',
		-1 => '禁用',
	];
	const STATUS_CLASS = [
		1 => '',
		-1 => 'text-danger',
	];
	
	
	public function role()
	{
		return $this->belongsTo(Role::class, 'role_id', 'id');
	}
}