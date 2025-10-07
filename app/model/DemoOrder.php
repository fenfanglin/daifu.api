<?php
namespace app\model;

use app\extend\common\Common;
use app\extend\common\BaseModel;

class DemoOrder extends BaseModel
{
	protected $table = 'pay_demo_order';

	protected $createTime = 'create_time';

	// 检查参数是否重复
	protected $unique_field = [
		'order_no' => '系统订单号',
	];


	// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
	const STATUS = [
		-1 => '未支付',
		1 => '成功，未回调',
		2 => '成功，已回调',
		-2 => '生成订单失败',
	];
	const STATUS_CLASS = [
		-1 => '',
		1 => 'text-warning',
		2 => 'text-success',
		-2 => 'text-danger',
	];


	public function channel()
	{
		return $this->belongsTo(Channel::class, 'channel_id', 'id');
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

		// 自动生成out_trade_no参数
		$check = true;
		while ($check)
		{
			$model->out_trade_no = date('YmdHis') . rand(10000000, 99999999);
			$check = self::where(['out_trade_no' => $model->out_trade_no])->count('id');
		}
	}
}