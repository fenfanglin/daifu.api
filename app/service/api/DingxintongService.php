<?php
namespace app\service\api;

use app\extend\common\Common;

class DingxintongService
{
	protected $host = 'http://dingxintongcloud.com';

	protected $create_url_bank = '/api/openapi/alipay/transfer/bankcard';
	protected $create_url_alipay = '/api/openapi/alipay/transfer/account';
	protected $query_url = '/api/openapi/alipay/transfer/getTransferDetail';
	protected $bill_url = '/api/openapi/alipay/transfer/billEreceiptDownloadUrl';
	protected $account_info = '/api/openapi/account/info';
	protected $notify_url = 'api_notify_dingxintong/index';
	protected $white_ip = [
		// '34.92.166.185',
	];

	protected $mchid;
	protected $appid;
	protected $key_id;
	protected $key_secret;

	/**
	 * 构造方法
	 */
	public function __construct($config)
	{
		$this->mchid = $config['mchid'] ?? '';
		$this->appid = $config['appid'] ?? '';
		$this->key_id = $config['key_id'] ?? '';
		$this->key_secret = $config['key_secret'] ?? '';

		if (!$this->mchid)
		{
			Common::error('缺少mchid');
		}

		if (!$this->appid)
		{
			Common::error('缺少appid');
		}

		if (!$this->key_id)
		{
			Common::error('缺少key_id');
		}

		if (!$this->key_secret)
		{
			Common::error('缺少key_secret');
		}
	}

	/**
	 * 报错
	 */
	private function error($file_log, $msg, $data = [])
	{
		Common::writeLog([
			'data' => $data,
			'ERROR' => $msg,
		], "DingxintongService_{$file_log}");

		return [
			'status' => 'ERROR',
			'msg' => $msg,
			'data' => [],
		];
	}

	/**
	 * 成功
	 */
	private function success($file_log, $msg, $data = [])
	{
		Common::writeLog([
			'data' => $data,
			'SUCCESS' => $msg,
		], "DingxintongService_{$file_log}");

		return [
			'status' => 'SUCCESS',
			'msg' => $msg,
			'data' => $data,
		];
	}

	/**
	 * 生成签名
	 */
	private function createSign($params, $secret_key)
	{
		unset($params['sign']);

		ksort($params); //字典排序

		$str = '';
		foreach ($params as $key => $value)
		{
			if (is_array($value))
			{
				$value = json_encode($value, JSON_UNESCAPED_UNICODE);
			}

			if (strlen($key) && strlen($value))
			{
				$str .= $key . '=' . $value . '&';
			}
		}

		$str .= 'secret=' . $secret_key;

		return strtoupper(md5($str));
	}

	/**
	 * 生成订单
	 */
	public function create($data)
	{
		$file_log = 'create';

		if (!isset($data['account_type']) || !in_array($data['account_type'], [1, 3]))
		{
			return $this->error($file_log, '错误101: account_type只允许银行卡和支付宝', $data);
		}

		// 收款账号类型 1银行卡 2usdt 3支付宝 4数字人民币
		if ($data['account_type'] == 1)
		{
			$url = $this->host . $this->create_url_bank;

			$param = [
				'accountId' => $this->mchid,
				'appId' => $this->appid,
				'accessKey' => $this->key_id,
				'timestamp' => milliseconds(),
				'notifyUrl' => config('app.api_url') . $this->notify_url,
				'orderList' => [
					[
						'channelMchId' => $data['out_trade_no'],
						'identity' => $data['account'],
						'recipientName' => $data['account_name'],
						'instName' => $data['bank'],
						'transferAmount' => $data['amount'] * 100,
						// 'alipayRemark' => '转账',
					],
				],
			];
		}
		elseif ($data['account_type'] == 3)
		{
			$url = $this->host . $this->create_url_alipay;

			$param = [
				'accountId' => $this->mchid,
				'appId' => $this->appid,
				'accessKey' => $this->key_id,
				'timestamp' => milliseconds(),
				'notifyUrl' => config('app.api_url') . $this->notify_url,
				'orderList' => [
					[
						'channelMchId' => $data['out_trade_no'],
						'identity' => $data['account'],
						'recipientName' => $data['account_name'],
						'transferAmount' => $data['amount'] * 100,
						// 'alipayRemark' => '转账',
					],
				],
			];
		}

		$param['sign'] = $this->createSign($param, $this->key_secret);

		// 发送HTTP POST请求
		$res = $this->httpPost($url, $param);
		$res = json_decode($res, true);

		Common::writeLog(['url' => $url, 'params' => $param, 'res' => $res], 'DingxintongService_create');

		if (!isset($res['code']) || $res['code'] != 0)
		{
			return $this->error($file_log, '错误102: ' . ($res['msg'] ?? '返回code不正确'), $res);
		}

		if (!isset($res['data']) || !is_array($res['data']))
		{
			return $this->error($file_log, '错误103: 返回data不正确', $res);
		}

		return $this->success($file_log, '成功', $res);
	}

	/**
	 * 查单订单
	 */
	public function query($order_no)
	{
		$file_log = 'query';

		$url = $this->host . $this->query_url;

		$params = [];
		$params['accessKey'] = $this->key_id;
		$params['timestamp'] = milliseconds();
		$params['channelMchIds'] = [$order_no];
		$params['sign'] = $this->createSign($params, $this->key_secret);

		$res = $this->httpPost($url, $params);
		$res = json_decode($res, true);

		Common::writeLog(['url' => $url, 'params' => $params, 'res' => $res], 'DingxintongService_query');

		if (!isset($res['code']) || $res['code'] != 0)
		{
			return $this->error($file_log, '错误201: ' . ($res['msg'] ?? '返回code不正确'), $res);
		}

		if (!isset($res['data'][0]) || !is_array($res['data'][0]))
		{
			return $this->error($file_log, '错误202: 返回data不正确', $res);
		}

		if (!isset($res['data'][0]['transferAmount']))
		{
			return $this->error($file_log, '错误203: 接口没返回transferAmount', $res);
		}

		// 转账状态（0=待转账、1=转账成功、2=已终止/已拒绝、3=转账失败、4=转账中、5=失效）
		if (!isset($res['data'][0]['transferStatus']))
		{
			return $this->error($file_log, '错误204: 接口没返回transferStatus', $res);
		}

		return $this->success($file_log, '成功', $res);
	}

	/**
	 * 电子回单-获取下载链接
	 */
	public function bill_url($order_id, $order_no)
	{
		$file_log = 'bill_url';

		$url = $this->host . $this->bill_url;

		$params = [];
		$params['accessKey'] = $this->key_id;
		$params['timestamp'] = milliseconds();
		$params['id'] = $order_id;
		$params['channelMchId'] = $order_no;
		$params['sign'] = $this->createSign($params, $this->key_secret);

		$res = $this->httpPost($url, $params);
		$res = json_decode($res, true);

		Common::writeLog(['url' => $url, 'params' => $params, 'res' => $res], 'DingxintongService_bill_url');

		if (!isset($res['code']) || $res['code'] != 0)
		{
			return $this->error($file_log, '错误301: ' . ($res['msg'] ?? '返回code不正确'), $res);
		}

		if (!isset($res['data']))
		{
			return $this->error($file_log, '错误302: 返回data不正确', $res);
		}

		return $this->success($file_log, '成功', $res);
	}

	/**
	 * 获取账户信息
	 */
	public function account_info()
	{
		$file_log = 'account_info';

		$url = $this->host . $this->account_info;

		$params = [];
		$params['accessKey'] = $this->key_id;
		$params['timestamp'] = milliseconds();
		$params['sign'] = $this->createSign($params, $this->key_secret);

		$res = $this->httpPost($url, $params);
		$res = json_decode($res, true);

		Common::writeLog(['url' => $url, 'params' => $params, 'res' => $res], 'DingxintongService_account_info');

		if (!isset($res['code']) || $res['code'] != 0)
		{
			return $this->error($file_log, '错误401: ' . ($res['msg'] ?? '返回code不正确'), $res);
		}

		if (!isset($res['data']['balance']))
		{
			return $this->error($file_log, '错误402: 返回balance不正确', $res);
		}

		return $this->success($file_log, '成功', $res);
	}

	/**
	 * 发送HTTP POST请求
	 * @param string $url 请求的URL
	 * @param array $data POST的数据
	 * @return string 响应结果
	 */
	function httpPost($url, $data)
	{
		$begin = microtime(true);

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
			CURLOPT_HTTPHEADER => [
				'content-type: application/json',
			],
		]);

		$res = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		$end = microtime(true);
		$time = round($end - $begin, 4) . 's';

		Common::writeLog([
			'url' => $url,
			'data' => $data,
			'time' => $time,
			'res' => json_decode($res, true) ?? $res,
		], 'DingxintongService_httpPost');

		if ($err)
		{
			return $err;
		}

		return $res;
	}

	/**
	 * 验证回调信息
	 */
	public function checkNotifyData($data)
	{
		$file_log = 'checkNotifyData';

		$ip = Common::getClientIp();

		if (count($this->white_ip) > 0 && !in_array($ip, $this->white_ip))
		{
			return $this->error($file_log, "ip不在白名单: {$ip}");
		}

		if (!isset($data['sign']) || !$data['sign'])
		{
			return $this->error($file_log, '缺少sign签名', $data);
		}

		if ($data['sign'] != $this->createSign($data, $this->key_secret))
		{
			return $this->error($file_log, 'sign签名不正确', $data);
		}

		if (!isset($data['msgType']) || $data['msgType'] != 'ALIPAY_FUND_BATCH_UNI_TRANSFER')
		{
			return $this->error($file_log, 'msgType不正确', $data);
		}

		return $this->success($file_log, '成功');
	}
}
