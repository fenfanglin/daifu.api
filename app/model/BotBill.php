<?php
namespace app\model;

use app\extend\common\BaseModel;

class BotBill extends BaseModel
{
	protected $table = 'pay_bot_bill';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
}
