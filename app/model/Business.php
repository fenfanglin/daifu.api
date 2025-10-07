<?php
namespace app\model;

use app\extend\common\Common;
use app\extend\common\BaseModel;

class Business extends BaseModel
{
	protected $table = 'pay_business';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	// 检查参数是否重复
	protected $unique_field = [
		'username' => '商户账号',
		'username_api' => '商户监控账号',
		// 'phone' => '电话',
	];

	public $is_google_auth; //是否需要谷歌验证
	public $is_auth_when_edit_account; //修改收款账号需要谷歌验证码

	// 状态：1启用 -1禁用
	const STATUS = [
		1 => '启用',
		-1 => '禁用',
	];
	const STATUS_CLASS = [
		1 => '',
		-1 => 'text-danger',
	];

	//认证状态：-1待认证 1已认证 2不通过
	const VERIFY_STATUS = [
		-1 => '待认证',
		1 => '已认证',
		2 => '不通过',
	];
	const VERIFY_STATUS_CLASS = [
		-1 => '',
		1 => '',
		2 => 'text-danger',
	];

	//类型：1商户 2卡商 3四方 4四方商户
	const TYPE = [
		1 => '代理',
		2 => '工作室',
		3 => '商户'
	];
	const TYPE_CLASS = [
		1 => '',
		2 => '',
		3 => '',
		4 => '',
	];


	public function role()
	{
		return $this->belongsTo(Role::class, 'role_id', 'id');
	}

	public function parent()
	{
		return $this->belongsTo(Business::class, 'parent_id', 'id');
	}

	/**
	 * 新增前
	 */
	public static function onBeforeInsert($model)
	{
		parent::onBeforeInsert($model);

		// 自动生成secret_key参数
		$check = true;
		while ($check)
		{
			$model->secret_key = Common::randomStr(32);
			$check = self::where(['secret_key' => $model->secret_key])->count('id');
		}
	}
}
