<?php
namespace app\model;

use app\extend\common\BaseModel;

class ChannelAccount extends BaseModel
{
	// protected $connection = 'mysql_base';

	protected $table = 'pay_channel_account';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	// 检查参数是否重复
	protected $unique_field = [
		// 'account' => '收款账号',
		'qrcode' => '二维码',
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

	// 数字RMB：1钱包编号 2手机号
	const RMB_TYPE = [
		1 => '钱包编号',
		2 => '手机号',
	];
	const RMB_TYPE_CLASS = [
		1 => '',
		2 => '',
	];

	const OTHER_INFO = [
		'HZ' => '杭州',
		'HK' => '香港',
		'SZ' => '深圳',
		'BJ' => '北京',
		'SH' => '上海',
		'NJ' => '南京',
		'WLCB' => '乌兰察布',
	];

	// 聚合码：1支付宝监控 2微信监控
	const JUHEMA_TYPE = [
		1 => '支付宝监控',
		2 => '微信监控',
	];
	const JUHEMA_TYPE_CLASS = [
		1 => '',
		2 => '',
	];

	// 支付宝服务商：1下单回调 2只监控订单
	const ALIPAY_P_TYPE = [
		1 => '下单回调',
		2 => '只监控订单',
	];
	const ALIPAY_P_TYPE_CLASS = [
		1 => '',
		2 => '',
	];

	// NiuShop支付方式：1支付宝 2微信
	const NIUSHOP_PAY_TYPE = [
		1 => '支付宝',
		// 2 => '微信',
	];
	const NIUSHOP_PAY_TYPE_CLASS = [
		1 => '',
		// 2 => '',
	];

	// APP监控
	const APP_TYPE = [
		1 => '元破',
		2 => '拉卡拉',
		3 => '银盛小Y管家',
	];
	const APP_TYPE_CLASS = [
		1 => 'com.yp.businese',
		2 => 'com.lakala.shqb',
		3 => 'com.ysepay.merchant',
	];

	// 是否在线：1在线 -1离线
	const IS_ONLINE = [
		1 => '在线',
		-1 => '离线',
	];
	const IS_ONLINE_CLASS = [
		1 => 'text-success',
		-1 => 'text-danger',
	];

	// e卡信息
	const EKA_INFO = [
		1 => '100044806159',
		50 => '1107851',
		100 => '1107845',
		200 => '1107847',
		300 => '1107846',
		500 => '1107843',
		600 => '1962859',
		800 => '1107833',
		1000 => '1107842',
		2000 => '3348254',
		3000 => '3522645',
		4000 => '3348256',
		5000 => '3020581',
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

	public function subCardBusiness()
	{
		return $this->belongsTo(Business::class, 'sub_card_business_id', 'id');
	}

	public function systemBank()
	{
		return $this->belongsTo(SystemBank::class, 'system_bank_id', 'id');
	}

	public function device()
	{
		return $this->belongsTo(Device::class, 'device_id', 'id');
	}

	public function wechat()
	{
		return $this->belongsTo(Wechat::class, 'wechat_id', 'id');
	}

	public function monitor()
	{
		return $this->belongsTo(ChannelMonitor::class, 'monitor_id', 'id');
	}


	/**
	 * 查看后
	 */
	public static function onAfterRead($model)
	{
		if (in_array($model->channel_id, [101, 102, 109]))
		{
			$model->account_crypt = $model->account;
			$model->account = decryptData($model->account);
		}
	}

	/**
	 * 新增前
	 */
	public static function onBeforeInsert($model)
	{
		parent::onBeforeInsert($model);

		$mchid = $model->mchid;
		$appid = $model->appid;

		// if (in_array($model->channel_id, [101, 102, 109]) && $model->account)
		// {
		// 	$model->account_sub = substr($model->account, -6);
		// 	$model->account = encryptData($model->account);
		// }

		if ($model->mchid && $model->appid)
		{
			$check = self::where('mchid', $model->mchid)
						->where('appid', $model->appid)
						->find();
			if ($check)
			{
				\app\extend\common\Common::error('商户id和appid已经重复: ' . $mchid . ',' . $appid);
			}
		}
	}

	/**
	 * 更新前
	 */
	public static function onBeforeUpdate($model)
	{
		parent::onBeforeUpdate($model);

		$mchid = $model->mchid;

		// if (in_array($model->channel_id, [1]) && $model->account)
		// {
		// 	$model->account_sub = substr($model->account, -6);
		// 	$model->account = encryptData($model->account);
		// }

		// if ($model->mchid && $model->appid)
		// {
		// 	$check = self::where('mchid', $model->mchid)
		// 				->where('appid', $model->appid)
		// 				->find();
		// 	if ($check)
		// 	{
		// 		\app\extend\common\Common::error('商户id和appid已经重复: ' . $mchid . ',' . $appid);
		// 	}
		// }
	}

	// /**
	//  * 新增后
	//  */
	// public static function onAfterInsert($model)
	// {
	// 	if (in_array($model->channel_id, [101, 102, 109]) && $model->account)
	// 	{
	// 		$model->account = decryptData($model->account);
	// 	}

	// 	self::updateTmp($model);
	// }

	// /**
	//  * 更新后
	//  */
	// public static function onAfterUpdate($model)
	// {
	// 	if (in_array($model->channel_id, [101, 102, 109]) && $model->account)
	// 	{
	// 		$model->account = decryptData($model->account);
	// 	}

	// 	self::updateTmp($model);
	// }

	// /**
	//  * 新增备用
	//  */
	// public static function updateTmp($model)
	// {
	// 	$tmp = ChannelAccountTmp::where('id', $model->id)->find();
	// 	if (!$tmp)
	// 	{
	// 		$tmp = new ChannelAccountTmp;

	// 		$tmp->id = $model->id;
	// 		$tmp->business_id = $model->business_id;
	// 		$tmp->channel_id = $model->channel_id;
	// 		$tmp->create_time = $model->create_time;
	// 	}

	// 	$tmp->card_business_id = $model->card_business_id;
	// 	$tmp->sub_card_business_id = $model->sub_card_business_id ?? 0;
	// 	$tmp->update_time = $model->update_time;
	// 	$tmp->status = $model->status;

	// 	$tmp->save();
	// }
}