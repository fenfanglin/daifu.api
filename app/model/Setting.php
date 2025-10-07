<?php
namespace app\model;

use app\extend\common\BaseModel;

class Setting extends BaseModel
{
	protected $table = 'pay_setting';
	
	// 检查参数是否重复
	protected $unique_field = [];
	
}