<?php
namespace app\model;

use app\extend\common\BaseModel;

class ChannelAccount extends BaseModel
{
	protected $table = 'pay_channel_account';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	// 检查参数是否重复
	protected $unique_field = [
		// 'account' => '收款账号',
		// 'qrcode' => '二维码',
	];


	// 状态：1开启 -1关闭
	const STATUS = [
		1 => '开启',
		-1 => '关闭',
	];
	const STATUS_CLASS = [
		1 => '',
		-1 => 'text-danger',
	];


	public function channel()
	{
		return $this->belongsTo(Channel::class, 'channel_id', 'id');
	}

	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}

	public function cardBusiness()
	{
		return $this->belongsTo(Business::class, 'card_business_id', 'id');
	}


	/**
	 * 新增前
	 */
	public static function onBeforeInsert($model)
	{
		parent::onBeforeInsert($model);

		if ($model->mchid && $model->appid)
		{
			$check = self::where('mchid', $model->mchid)->where('appid', $model->appid)->find();
			if ($check)
			{
				\app\extend\common\Common::error("商户ID和APPID已经重复: {$model->mchid}, {$model->appid}");
			}
		}
	}
}