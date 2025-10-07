<?php
namespace app\model;

use app\extend\common\Common;
use app\extend\common\BaseModel;

class Order extends BaseModel
{
	protected $table = 'pay_order';

	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	// 检查参数是否重复
	protected $unique_field = [
		'order_no' => '系统订单号',
		'out_trade_no' => '商户订单号',
	];


	// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
	const STATUS = [
		-1 => '未支付',
		1 => '成功，未回调',
		2 => '成功，已回调',
		-2 => '支付失败',
	];
	const STATUS_CLASS = [
		-1 => '',
		1 => 'text-warning',
		2 => 'text-success',
		-2 => 'text-danger',
	];

	// 回调类型：1自动回调 2手动回调
	const SUCCESS_TYPE = [
		1 => '自动回调',
		2 => '手动回调',
	];
	const SUCCESS_TYPE_CLASS = [
		1 => 'text-success',
		2 => 'text-warning',
	];


	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}

	public function subBusiness()
	{
		return $this->belongsTo(Business::class, 'sub_business_id', 'id');
	}

	public function cardBusiness()
	{
		return $this->belongsTo(Business::class, 'card_business_id', 'id');
	}

	public function channel()
	{
		return $this->belongsTo(Channel::class, 'channel_id', 'id');
	}

	public function channelAccount()
	{
		return $this->belongsTo(ChannelAccount::class, 'channel_account_id', 'id');
	}

	public function notifyLog()
	{
		return $this->belongsTo(NotifyLog::class, 'notify_log_id', 'id');
	}

	/**
	 * 新增前
	 */
	public static function onBeforeInsert($model)
	{
		parent::onBeforeInsert($model);

		// 自动生成order_no参数
		$check = true;
		while ($check)
		{
			$model->order_no = date('YmdHis') . rand(100000, 999999);
			$check = self::where(['order_no' => $model->order_no])->count('id');
		}
	}

	/**
	 * 检查商户订单号是否重复
	 */
	public static function checkOutTradeNo($out_trade_no)
	{
		$check = self::where(['out_trade_no' => $out_trade_no])->count('id');
		if ($check)
		{
			return false;
		}

		return true;
	}
}