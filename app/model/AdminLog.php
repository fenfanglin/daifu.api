<?php
namespace app\model;

use app\extend\common\BaseModel;

class AdminLog extends BaseModel
{
	protected $table = 'pay_admin_log';
	
	protected $createTime = 'create_time';
	
	// 自动生成no参数
	protected $generate_no = false;
	
	// 检查参数是否重复
	protected $unique_field = [];
	
	
	public function admin()
	{
		return $this->belongsTo(Admin::class, 'admin_id', 'id');
	}
}