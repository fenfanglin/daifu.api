<?php
namespace app\model;

use app\extend\common\Common;
use app\extend\common\BaseModel;

class Relation_Business extends BaseModel
{
	protected $connection = 'mysql_relation';

	protected $table = 'pay_business';

	// 自动生成no参数
	protected $generate_no = false;

}