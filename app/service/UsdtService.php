<?php
namespace app\service;

use app\extend\common\Common;

class UsdtService
{
	public static $coin_type = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; //trc20 usdt
	public static $trongrid_API_KEY = 'TRON_PRO_API_KEY: b0226d6b-2455-4e05-9c89-fb585030d9e0';
	public static $timeout = 60 * 20; //检查20分钟内的交易记录
	public static $usdt_address_cache_time = 60 * 60; //一个Usdt地址缓存60分钟，超过60分钟没下单就删除缓存（避免频率生成删除缓存）
	public static $usdt_address_timeout = 5; //请求交易记录缓存5秒，超时重新请求接口

	/**
	 * 获取usdt钱包交易记录
	 */
	public static function getTransactionByTrc($usdt_address)
	{
		$redis = Common::redis();

		// 显示开头
		// Common::writeLog('', 'UsdtService_getTransactionByTrc');

		$res = $redis->get('usdt_address_' . $usdt_address);

		// 有缓存，还在缓存时间内，直接返回
		if (isset($res['data']) && isset($res['request_time']) && $res['request_time'] >= time() - self::$usdt_address_timeout)
		{
			// echo "获取缓存, ";
			// Common::writeLog('获取缓存: ' . $usdt_address, 'UsdtService_getTransactionByTrc', $show_header = false);
			return $res['data'];
		}

		$params = [];
		$params['only_confirmed'] = 'true';
		$params['only_to'] = 'true';
		$params['limit'] = '200';
		$params['min_timestamp'] = '200';
		$params['min_timestamp'] = (time() - self::$timeout) * 1000; //几分钟之前的链上交易
		$params['contract_address'] = self::$coin_type;

		$url = "https://api.trongrid.io/v1/accounts/{$usdt_address}/transactions/trc20";
		$res = self::http_get($url);
		$res = json_decode($res, true);

		// echo "请求, ";
		// Common::writeLog('请求: ' . $usdt_address, 'UsdtService_getTransactionByTrc', $show_header = false);
		Common::writeLog($res, 'UsdtService_getTransactionByTrc');

		$res['request_time'] = time(); //记录请求时间

		$redis->set('usdt_address_' . $usdt_address, $res, self::$usdt_address_cache_time);

		if (!isset($res['success']) || !$res['success'])
		{
			Common::writeLog($res, 'UsdtService_getTransactionByTrc_error');

			return false;
		}

		return $res['data'];
	}

	/**
	 * 对比订单金额
	 */
	public static function diffTrans($trans_list, $order)
	{
		Common::writeLog($order, 'UsdtService_order', $show_header = false);
		if (!is_array($trans_list) || !$trans_list || !$order)
		{
			return false;
		}

		$redis = Common::redis();

		foreach ($trans_list as $trans)
		{
			// 如果transaction_id已经处理过就不处理
			if ($redis->get('usdt_trans_' . $trans['transaction_id']))
			{
				$data = "已处理过, usdt_amount: {$order['usdt_amount']}, transaction_id: {$trans['transaction_id']}";
				// Common::writeLog($data, 'UsdtService_diffTrans', $show_header = false);
				continue;
			}

			// $order['pay_code'] = number_format($order['pay_code'], 3, '.', '');

			$transaction_id = $trans['transaction_id'];
			$timestamp = $trans['block_timestamp'] / 1000;
			$timestamp = date('Y-m-d H:i:s', $timestamp);
			$form_usdt_address = $trans['from'];

			$decimalPlaces = strlen(substr(strrchr($order['usdt_amount'], '.'), 1));

			$chushu = pow(10, 6); //USDT要除以6位
			$pay_amount = round($trans['value'] / $chushu, $decimalPlaces);

			$order['usdt_amount'] = round($order['usdt_amount'], $decimalPlaces);

			// 金额匹配，类型是Transfer
			if ($order['usdt_amount'] == $pay_amount && $trans['type'] == 'Transfer')
			{
				// // 显示开头
				// Common::writeLog('', 'UsdtService_diffTrans');

				// 支付时间在下单时间之前
				if ($timestamp <= $order['create_time'])
				{
					// $data = [
					// 	'error' => '金额匹配但时间不符合',
					// 	'pay_amount' => $pay_amount,
					// 	'timestamp' => $timestamp,
					// 	'create_time' => $order['create_time'],
					// 	'transaction_id' => $trans['transaction_id'],
					// ];
					// Common::writeLog($data, 'UsdtService_diffTrans', $show_header = false);

					// echo "时间不符合, ";

					continue;
				}

				$data = [
					// 'error' => '时间通过',
					'order_usdt_amount' => $order['usdt_amount'],
					'pay_amount' => $pay_amount,
					'timestamp' => $timestamp,
					'create_time' => $order['create_time'],
					'transaction_id' => $trans['transaction_id'],
				];
				Common::writeLog($data, 'UsdtService_diffTrans');

				Common::writeLog('请求transaction_id: ' . $trans['transaction_id'], 'UsdtService_diffTrans', $show_header = false);

				$url = 'https://apilist.tronscanapi.com/api/transaction-info?hash=' . $trans['transaction_id'];
				$res = self::http_get($url);

				Common::writeLog([
					'transaction_id' => $trans['transaction_id'],
					'res' => $res,
				], 'UsdtService_diffTrans_detail');

				$res = json_decode($res, true);
				if (!isset($res['toAddress']) || !isset($res['confirmations']) || !isset($res['confirmed']))
				{
					Common::writeLog('ERROR', 'UsdtService_diffTrans_detail', $show_header = false);

					Common::writeLog(['error' => 'json error', 'res' => $res], 'UsdtService_diffTrans_error');
					continue;
				}

				Common::writeLog('SUCCESS', 'UsdtService_diffTrans_detail', $show_header = false);

				// echo "请求trans, ";

				Common::writeLog($trans, 'UsdtService_diffTrans', $show_header = false);

				$_tmp = [
					'toAddress' => $res['toAddress'],
					'confirmations' => $res['confirmations'],
					'confirmed' => $res['confirmed'],
				];
				Common::writeLog($_tmp, 'UsdtService_diffTrans', $show_header = false);

				if ($res['toAddress'] == self::$coin_type && $res['confirmations'] > 9 && $res['confirmed'] == true)
				{
					$data = "成功, usdt_amount: {$order['usdt_amount']}, transaction_id: {$trans['transaction_id']}";
					Common::writeLog($data, 'UsdtService_diffTrans', $show_header = false);

					return ['transaction_id' => $transaction_id, 'pay_amount' => $pay_amount, 'timestamp' => $timestamp, 'form_usdt_address' => $form_usdt_address];
				}
			}
		}

		return false;
	}

	/**
	 * 获取Usdt汇率
	 */
	public static function getUsdtRate()
	{
		$url = 'https://www.okx.com/priapi/v3/b2c/deposit/quotedPrice?side=buy&quoteCurrency=CNY&baseCurrency=USDT&t=' . Common::getMilliSecond();
		$res = self::http_get($url);

		$res = json_decode($res, true);

		if (!isset($res['code']) || !isset($res['data'][0]['price']))
		{
			return false;
		}

		if ($res['code'] == 0 && $res['data'][0]['price'] > 0)
		{
			$new_rate = $res['data'][0]['price'];

			$redis = Common::redis();

			$usdt_rate = $redis->get('usdt_rate');
			if (!$usdt_rate)
			{
				$usdt_rate = $new_rate;
			}

			// 汇率涨幅
			$range = abs($new_rate - $usdt_rate);

			// 如果汇率涨幅 > 0.5，不执行
			if ($range > 0.5)
			{
				$data = [
					'code' => 0,
					'msg' => '汇率涨幅过高',
					'last_rate' => $usdt_rate,
					'new_rate' => $new_rate,
					'range' => $range,
				];
			}
			else
			{
				$data = [
					'code' => 1,
					'msg' => '成功',
					'last_rate' => $usdt_rate,
					'new_rate' => $new_rate,
				];

			}

			$redis->set('usdt_rate', $new_rate);
		}
		else
		{
			$data = [
				'code' => 0,
				'msg' => '请求接口失败',
			];
		}

		Common::writeLog(['res' => $res, 'data' => $data], 'UsdtService_getUsdtRate');
		return $data;
	}

	/**
	 * 请求get
	 */
	public static function http_get($url)
	{
		if (config('app.curl_get_url'))
		{
			$url = config('app.curl_get_url') . 'curl/get?url=' . urlencode($url);
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		$result = curl_exec($ch);

		if (curl_errno($ch))
		{
			return curl_error($ch);
		}
		else
		{
			return $result;
		}
	}
}
