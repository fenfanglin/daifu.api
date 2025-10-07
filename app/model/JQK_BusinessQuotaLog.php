<?php
namespace app\model;

use app\extend\common\Common;
use app\extend\common\BaseModel;

class JQK_BusinessQuotaLog extends BaseModel
{
	protected $connection = 'mysql_jqk';

	protected $table = 'pay_business_quota_log';

	// 自动生成no参数
	protected $generate_no = false;


	// /**
	//  * 新增前
	//  */
	// public static function onBeforeInsert($model)
	// {
	// 	Common::error('不能修改代付信息');
	// }

	/**
	 * 更新前
	 */
	public static function onBeforeUpdate($model)
	{
		Common::error('不能修改代付信息');
	}

	// /**
	//  * 写入前
	//  */
	// public static function onBeforeWrite($model)
	// {
	// 	Common::error('不能修改代付信息');
	// }

	/**
	 * 删除前
	 */
	public static function onBeforeDelete($model)
	{
		Common::error('不能修改代付信息');
	}

	/**
	 * 恢复前
	 */
	public static function onBeforeRestore($model)
	{
		Common::error('不能修改代付信息');
	}
}