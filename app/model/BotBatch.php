<?php
namespace app\model;

use app\extend\common\BaseModel;

class BotBatch extends BaseModel
{
	protected $table = 'pay_bot_batch';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	const TYPE = [
		'1' => '系统后台',
		'2' => '商户'
	];
}
