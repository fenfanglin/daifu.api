<?php
namespace app\pay\controller;

use app\extend\common\Common;
use app\model\ChannelAccount;
use app\model\ChannelProduct;
use app\service\api\Gelute4Service;
use app\service\api\Jinqianbao;
use app\service\api\YiYunHuiService;
use app\service\OrderService;
use app\service\api\Gelute3Service;
use app\model\Order;

class ApiNotifyJinqianbaoController extends AuthController
{

	/**
	 * 监听（只能通过Notify控制器请求，直接请求报错‘拒绝访问’）
	 */
	public function index()
	{
		// 记录开头
		Common::writeLog(input('post.'), 'ApiNotifyJinqianbao');
		$orderid = input('post.merchantOrderNo'); //商户订单号
		$where = [];
		$where[] = ['out_trade_no', '=', $orderid];
		$order = Order::where($where)->find();
		if (!$order)
		{
			$this->error('无匹配订单');
		}
		$service = new Jinqianbao();
		// 验证回调信息
		$service->checkNotifyData(input('post.'));
		$res = $service->query($orderid);
		if ($res['data']['merchantOrderNo'] != $order->out_trade_no)
		{
			$this->error('orderid不正确：' . $res['orderid'], ['$order->out_trade_no' => $order->out_trade_no]);
		}

		if ($res['data']['status'] != 3)
		{
			$this->error('trade_state不正确：' . $res['trade_state']);
		}

		if ($res['data']['amount'] != $order->amount)
		{
			$this->error('amount不正确：' . $res['amount'], ['$order->pay_amount' => $order->pay_amount]);
		}

		// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
		if ($order->status == -1)
		{
			OrderService::completeOrder($order->id);
		}
		echo 'success';
		exit();
	}


	// --------------------------------------------------------------------------------------------------------------
	/**
	 * 报错记录
	 */
	private function error($msg, $data = [])
	{
		$this->addLogError($msg, $data);

		Common::writeLogLine($this->log_str, 'ApiNotifyGelute4', $content_time = false);

		Common::error($msg);
	}

	/**
	 * 成功记录
	 */
	private function success($msg)
	{
		$this->addLogBreak($msg);

		Common::writeLogLine($this->log_str, 'ApiNotifyGelute4', $content_time = false);
	}

	/**
	 * 加记录
	 */
	private function addLog($msg)
	{
		$this->log_str .= $msg;
	}

	/**
	 * 加记录（换行）
	 */
	private function addLogBreak($msg)
	{
		$this->log_str .= $msg . "\n";
	}

	/**
	 * 加记录报错
	 */
	private function addLogError($msg, $data = [])
	{
		$arr_data = [];
		$arr_data[] = 'ERROR: ' . $msg;

		foreach ($data as $key => $value)
		{
			$arr_data[] = "{$key} = {$value}";
		}

		$data = implode(', ', $arr_data);

		$this->log_str .= "\n" . $data . "\n";
	}
}
