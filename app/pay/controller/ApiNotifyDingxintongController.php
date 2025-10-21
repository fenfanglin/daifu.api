<?php
namespace app\pay\controller;

use app\extend\common\Common;
use app\service\OrderService;
use app\service\api\DingxintongService;
use app\model\ChannelAccount;
use app\model\Order;

class ApiNotifyDingxintongController extends AuthController
{

	/**
	 * 监听（只能通过Notify控制器请求，直接请求报错‘拒绝访问’）
	 */
	public function index()
	{
		$channelMchId = input('post.msgBody')['channelMchId'] ?? ''; //商户订单号

		$where = [];
		$where[] = ['out_trade_no', '=', $channelMchId];
		$order = Order::where($where)->find();
		if (!$order)
		{
			$this->error('无匹配订单');
		}

		$config = [
			'mchid' => $order->cardBusiness->channelAccount->mchid ?? '',
			'appid' => $order->cardBusiness->channelAccount->appid ?? '',
			'key_id' => $order->cardBusiness->channelAccount->key_id ?? '',
			'key_secret' => $order->cardBusiness->channelAccount->key_secret ?? '',
		];

		if (!$config['mchid'] || !$config['appid'] || !$config['key_id'] || !$config['key_secret'])
		{
			$this->error('工作室参数不正确');
		}

		$service = new DingxintongService($config);

		// 验证回调信息
		$res = $service->checkNotifyData(input('post.'));

		if (!isset($res['status']) || $res['status'] != 'SUCCESS')
		{
			$this->error($res['msg'] ?? '验证信息未通过');
		}


		// 查询订单
		$res = $service->query($channelMchId);

		if (!isset($res['status']) || $res['status'] != 'SUCCESS')
		{
			$this->error($res['msg'] ?? '查询订单失败');
		}

		if (!isset($res['data']['data'][0]['transferAmount']) || $res['data']['data'][0]['transferAmount'] != $order->amount * 100)
		{
			$this->error('transferAmount不正确：' . ($res['data']['data'][0]['transferAmount'] ?? 0), ['order_amount' => $order->amount]);
		}


		if (!isset($res['data']['data'][0]['transferStatus']))
		{
			$this->error('缺少state参数');
		}

		// 转账状态（0=待转账、1=转账成功、2=已终止/已拒绝、3=转账失败、4=转账中、5=失效）
		if ($res['data']['data'][0]['transferStatus'] == 1)
		{
			// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
			if ($order->status == -1) //订单状态未支付才处理订单
			{
				OrderService::completeOrder($order->id);
			}

			// 保存订单ID
			$info = json_decode($order->info, true);
			$info['order_id'] = $res['data']['data'][0]['id'] ?? '';

			$order->info = json_encode($info, JSON_UNESCAPED_UNICODE);
			$order->save();
		}

		// 转账状态（0=待转账、1=转账成功、2=已终止/已拒绝、3=转账失败、4=转账中、5=失效）
		if (in_array($res['data']['data'][0]['transferStatus'], [2, 3]))
		{
			// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
			if ($order->status == -1) //订单状态未支付才处理订单
			{
				$error_msg = $res['data']['data'][0]['description'] ?? '';

				OrderService::failOrder($order->id, $error_msg);
			}
		}

		// 转账状态（0=待转账、1=转账成功、2=已终止/已拒绝、3=转账失败、4=转账中、5=失效）
		if ($res['data']['data'][0]['transferStatus'] == 5)
		{
			// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
			if ($order->status == -1) //订单状态未支付才处理订单
			{
				OrderService::notPayOrder($order->id);
			}
		}

		$this->success();
	}

	// --------------------------------------------------------------------------------------------------------------
	/**
	 * 报错记录
	 */
	private function error($msg, $data = [])
	{
		Common::writeLog([
			'params' => input('post.'),
			'error' => $msg,
			'data' => $data,
		], 'ApiNotifyDingxintong');

		echo 'fail';
		exit();
	}

	/**
	 * 成功记录
	 */
	private function success()
	{
		Common::writeLog([
			'params' => input('post.'),
			'msg' => 'SUCCESS',
		], 'ApiNotifyDingxintong');

		echo 'success';
		exit();
	}
}
