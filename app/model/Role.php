<?php
namespace app\model;

use app\extend\common\BaseModel;

class Role extends BaseModel
{
	protected $table = 'pay_role';
	
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	
	// 检查参数是否重复
	protected $unique_field = [];
	
	
	// 状态：1启用 -1禁用
	const STATUS = [
		1 => '启用',
		-1 => '禁用',
	];
	const STATUS_CLASS = [
		1 => '',
		-1 => 'text-danger',
	];
	
	// 类型：1代理总权限 2管理员角色
	const TYPE = [
		1 => '代理总权限',
		2 => '管理员角色',
	];
	const TYPE_CLASS = [
		1 => 'text-warning',
		2 => '',
	];
	
	
	/**
	 * 获取菜单权限
	 * 	- 总部就获取全部
	 * 	- 经销商就获取经销商后台权限
	 */
	public static function getCenterPermission($center_id)
	{
		$model = self::where(['center_id' => $center_id, 'type' => 1])->field('permission')->find();
		
		$permission = $model ? json_decode($model->permission, true) : [];
		
		return $permission;
	}
	
	/**
	 * 获取用户菜单权限
	 * 	- 总部就获取全部
	 * 	- 经销商就获取经销商后台权限
	 */
	public static function getUserPermission($role_id)
	{
		$model = self::where(['id' => $role_id, 'type' => 2])->field('permission')->find();
		
		$permission = $model ? json_decode($model->permission, true) : [];
		
		return $permission;
	}
}