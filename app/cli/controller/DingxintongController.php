<?php
namespace app\cli\controller;

use app\extend\common\Common;
use app\model\Order;
use app\model\Channel;
use app\model\ChannelAccount;
use app\model\BusinessChannel;
use app\service\OrderService;
use app\service\api\DingxintongService;

/**
 * 必须给每个商户执行一个进程，分配账号只针对一个商户不会出错
 */
class DingxintongController
{
	private $channel_id = [
		2, //鼎薪通
	];

	// public function handle_30300()
	// {
	// 	$this->_handle(30300);
	// }

	public function handle_other()
	{
		$business_not_in = [
			// 30300,
		];

		$this->_handle(0, $business_not_in);
	}

	/**
	 * 处理订单请求接口
	 */
	private function _handle($business_id = 0, $business_not_in = [])
	{
		while (true)
		{
			$begin = microtime(true);

			$all_account = [];
			$allow_account = [];
			$card_business_data = [];
			$api_num = 0;

			// 获取未下单的订单
			$where = [];
			$where[] = ['channel_id', 'in', $this->channel_id];
			$where[] = ['status', '=', -1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2生成订单失败
			$where[] = ['api_status', '=', -1]; //下单状态：-1未下单 1成功 -2失败

			if ($business_id)
			{
				$where[] = ['business_id', 'in', $business_id];
			}
			elseif ($business_not_in)
			{
				$where[] = ['business_id', 'not in', $business_not_in];
			}

			$list_order = Order::where($where)->order('id', 'asc')->select();

			foreach ($list_order as $order)
			{
				$_business_id = $order->business_id;

				Common::writeLog([
					'order_no' => $order->order_no,
					'out_trade_no' => $order->out_trade_no,
				], 'cli_Dingxintong_handle_' . $_business_id);

				if (!isset($all_account[$_business_id]))
				{
					// 获取所有可下单的三方账号
					$all_account[$_business_id] = $this->getAllAccount($_business_id);
				}

				if (!isset($allow_account[$_business_id]))
				{
					// 获取可下单的三方账号（正在处理订单数 < 并发数量）
					$allow_account[$_business_id] = $this->getAllowAccount($_business_id);
				}

				if (!isset($card_business_data[$_business_id]))
				{
					// 获取商户可下单的工作室
					$card_business_data[$_business_id] = array_filter(explode(',', trim($order->subBusiness->card_business_ids) ?? ''));
				}

				$_all_account = $all_account[$_business_id];
				$_allow_account = $allow_account[$_business_id];
				$_card_business_ids = $card_business_data[$_business_id];

				Common::writeLog([
					'_all_account' => $_all_account,
					'_allow_account' => $_allow_account,
					'_card_business_ids' => $_card_business_ids,
				], 'cli_Dingxintong_handle_' . $_business_id, false);

				if (count($_all_account) == 0)
				{
					Common::writeLogLine('$_all_account不正确', 'cli_Dingxintong_handle_' . $_business_id, false);

					OrderService::failOrder($order->id);
					continue;
				}

				if (count($_card_business_ids) == 0)
				{
					Common::writeLogLine('$_card_business_ids不正确', 'cli_Dingxintong_handle_' . $_business_id, false);

					OrderService::failOrder($order->id);
					continue;
				}

				// 获取下单账号
				$account = $this->getChannelAccount($_business_id, $_card_business_ids, $_allow_account);
				if ($account === false)
				{
					Common::writeLogLine('$account不正确', 'cli_Dingxintong_handle_' . $_business_id, false);
					continue;
				}

				// 检查三方账号（只保留还可下单的账号，正在处理订单数 < 并发数量）
				$allow_account[$_business_id] = $this->checkAllowAccount($allow_account[$_business_id], $account->id);

				// 接口下单
				$res = $this->apiCreateOrder($order, $account);

				$api_num++;

				if (!isset($res['status']) || $res['status'] != 'SUCCESS')
				{
					Common::writeLog([
						'error' => '下单不成功',
						'res' => $res,
					], 'cli_Dingxintong_handle_' . $_business_id, false);

					OrderService::failOrder($order->id);
					continue;
				}

				$order->api_status = 1; //下单状态：-1未下单 1成功 -2失败
				$order->save();
			}

			$end = microtime(true);
			$time = round($end - $begin, 2) . 's';

			if (count($list_order) > 0)
			{
				Common::writeLog([
					'time' => $time,
					'list_order_num' => count($list_order),
					'allow_account_num' => count($allow_account),
					'api_num' => $api_num,
				], 'cli_Dingxintong_handle');

				usleep(2000000);
			}
			else
			{
				sleep(1);
			}
		}

		exit;
	}

	/**
	 * 接口下单
	 */
	private function apiCreateOrder($order, $account)
	{
		$config = [
			'mchid' => $account->mchid ?? '',
			'appid' => $account->appid ?? '',
			'key_id' => $account->key_id ?? '',
			'key_secret' => $account->key_secret ?? '',
		];

		if (!$config['mchid'] || !$config['appid'] || !$config['key_id'] || !$config['key_secret'])
		{
			return [
				'status' => 'ERROR',
				'msg' => '账号缺少配置参数',
				'data' => [],
			];
		}

		$service = new DingxintongService($config);

		$data = [
			'out_trade_no' => $order->out_trade_no,
			'amount' => $order->amount,
			'account_type' => $order->account_type,
			'account' => $order->account,
			'account_name' => $order->account_name,
			'bank' => $order->bank,
		];

		$res = $service->create($data);

		return $res;
	}

	/**
	 * 获取所有可下单的三方账号
	 */
	private function getAllAccount($business_id)
	{
		$where = [];
		$where[] = ['channel_id', 'in', $this->channel_id];
		$where[] = ['business_id', '=', $business_id];
		$where[] = ['status', '=', 1]; //状态：1开启 -1关闭
		$all_account = ChannelAccount::field('id as account_id, order_num')->where($where)->select()->toArray();

		Common::writeLog([
			'all_account' => $all_account,
		], 'cli_Dingxintong_getAllAccount');

		return $all_account;
	}

	/**
	 * 获取可下单的三方账号（正在处理订单数 < 并发数量）
	 */
	private function getAllowAccount($business_id)
	{
		$allow_account = [];

		// 获取正在处理的订单，按每个收款账号统计
		$where = [];
		$where[] = ['channel_id', 'in', $this->channel_id];
		$where[] = ['business_id', '=', $business_id];
		$where[] = ['status', '=', -1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2生成订单失败	
		$where[] = ['api_status', '=', 1]; //下单状态：-1未下单 1成功 -2失败
		$processing_order = Order::field('`channel_account_id`, COUNT(`id`) AS `num`')->where($where)->group('channel_account_id')->select()->column('num', 'channel_account_id');

		$where = [];
		$where[] = ['channel_id', 'in', $this->channel_id];
		$where[] = ['business_id', '=', $business_id];
		$where[] = ['status', '=', 1]; //状态：1开启 -1关闭
		$_allow_account = ChannelAccount::field('id, order_num')->where($where)->select();

		foreach ($_allow_account as $account)
		{
			$num = $processing_order[$account->id] ?? 0;

			if ($num < $account->order_num)
			{
				$allow_account[] = [
					'account_id' => $account->id,
					'max_num' => $account->order_num,
					'num' => $num,
				];
			}
		}

		Common::writeLog([
			'processing_order' => $processing_order,
			'_allow_account' => $_allow_account,
			'allow_account' => $allow_account,
		], 'cli_Dingxintong_getAllowAccount');

		return $allow_account;
	}

	/**
	 * 检查三方账号（只保留还可下单的账号，正在处理订单数 < 并发数量）
	 */
	private function checkAllowAccount($allow_account, $account_id)
	{
		$_allow_account = [];
		foreach ($allow_account as $account)
		{
			if ($account['account_id'] == $account_id)
			{
				$account['num']++;
			}

			if ($account['num'] < $account['max_num'])
			{
				$_allow_account[] = $account;
			}
		}

		Common::writeLog([
			'allow_account' => $allow_account,
			'account_id' => $account_id,
			'_allow_account' => $_allow_account,
		], 'cli_Dingxintong_checkAllowAccount');

		return $_allow_account;
	}

	/**
	 * 获取三方转账账号（三方转账）
	 */
	private function getChannelAccount($business_id, $card_business_ids, $allow_account)
	{
		$account_ids = [];
		foreach ($allow_account as $_account)
		{
			$account_ids[] = $_account['account_id'];
		}

		$where = [];
		$where[] = ['business_id', '=', $business_id];
		$where[] = ['card_business_id', 'in', $card_business_ids];
		$where[] = ['id', 'in', $account_ids];
		$where[] = ['status', '=', 1];

		// is_use是否已下单 1是 0否（用于轮询下单）
		$account = ChannelAccount::where($where)->where('is_use', 0)->find();

		if (!$account) //如果未找到匹配，复原下单状态
		{
			ChannelAccount::where($where)->save(['is_use' => 0]);

			// 再次获取商户所有匹配金额的工作室，未下单状态
			$account = ChannelAccount::where($where)->where('is_use', 0)->find();
		}

		if (!$account)
		{
			return false;
		}

		$account->is_use = 1;
		$account->save();

		return $account;
	}
}