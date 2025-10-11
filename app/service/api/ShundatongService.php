<?php
namespace app\service\api;

use app\extend\common\Common;

class ShundatongService
{
	protected $host = 'https://pay.rongyanyu.top';

	protected $create_url = '/api/transferOrder';
	protected $query_url = '/api/transfer/query';
	protected $notify_url = 'api_notify_shundatong/index';
	protected $white_ip = [
		// '121.43.131.43',
	];

	protected $mchid;
	protected $appid;
	protected $key_secret;

	/**
	 * 构造方法
	 */
	public function __construct($config)
	{
		$this->mchid = $config['mchid'] ?? '';
		$this->appid = $config['appid'] ?? '';
		$this->key_secret = $config['key_secret'] ?? '';

		if (!$this->mchid)
		{
			Common::error('缺少mchid');
		}

		if (!$this->appid)
		{
			Common::error('缺少appid');
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
		], "ShundatongService_{$file_log}");

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
		], "ShundatongService_{$file_log}");

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

		$str .= 'key=' . $secret_key;

		return strtoupper(md5($str));
	}

	/**
	 * 生成订单
	 */
	public function create($data)
	{
		$file_log = 'create';

		$url = $this->host . $this->create_url;

		$ifCode = 'aliaqfpay'; //接口代码

		if (!isset($data['account_type']) || !in_array($data['account_type'], [1, 3]))
		{
			return $this->error($file_log, '错误101: account_type只允许银行卡和支付宝', $data);
		}

		$param = [
			'mchNo' => $this->mchid,
			'appId' => $this->appid,
			'mchOrderNo' => $data['out_trade_no'],
			'ifCode' => $ifCode,
			'amount' => $data['amount'] * 100,
			'transferDesc' => '转账',
			'currency' => 'cny',
			'reqTime' => milliseconds(),
			'notifyUrl' => config('app.api_url') . $this->notify_url,
			'version' => '1.0',
			'signType' => 'MD5',
		];

		// 收款账号类型 1银行卡 2usdt 3支付宝 4数字人民币
		if ($data['account_type'] == 1)
		{
			$param['entryType'] = 'BANK_CARD';
			$param['accountNo'] = $data['account'];
			$param['accountName'] = $data['account_name'];
			$param['bankName'] = $data['bank'];
		}
		elseif ($data['account_type'] == 3)
		{
			$param['entryType'] = 'ALIPAY_CASH';
			$param['accountNo'] = $data['account'];
			$param['accountName'] = $data['account_name'];
		}

		$param['sign'] = $this->createSign($param, $this->key_secret);

		// 发送HTTP POST请求
		$res = $this->httpPost($url, $param);
		$res = json_decode($res, true);

		Common::writeLog(['url' => $url, 'params' => $param, 'res' => $res], 'ShundatongService_create');

		if (!isset($res['code']) || $res['code'] != 0)
		{
			return $this->error($file_log, '错误102: ' . ($res['msg'] ?? '返回code不正确'), $res);
		}

		if (!isset($res['data']) || !is_array($res['data']))
		{
			return $this->error($file_log, '错误103: 返回data不正确', $res);
		}

		if (isset($res['data']['errMsg']))
		{
			return $this->error($file_log, '错误104: ' . $res['data']['errMsg'], $res);
		}

		return $this->success($file_log, '成功', $res);
	}

	/**
	 * 查单订单
	 */
	public function query($trans_id, $order_no)
	{
		$file_log = 'query';

		$url = $this->host . $this->query_url;

		$params = [];
		$params['mchNo'] = $this->mchid;
		$params['appId'] = $this->appid;
		$params['transferId'] = $trans_id;
		$params['mchOrderNo'] = $order_no;
		$params['version'] = '1.0';
		$params['signType'] = 'MD5';
		$params['reqTime'] = milliseconds();
		$params['sign'] = $this->createSign($params, $this->key_secret);

		$res = $this->httpPost($url, $params);
		$res = json_decode($res, true);

		Common::writeLog(['url' => $url, 'params' => $params, 'res' => $res], 'ShundatongService_query');

		if (!isset($res['code']) || $res['code'] != 0)
		{
			return $this->error($file_log, '错误201: ' . ($res['msg'] ?? '返回code不正确'), $res);
		}

		if (!isset($res['data']['amount']))
		{
			return $this->error($file_log, '错误202: 接口没返回amount', $res);
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

		Common::writeLog([
			'url' => $url,
			'data' => $data,
			'res' => json_decode($res, true) ?? $res,
		], 'ShundatongService_httpPost');

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
			return $this->error("ip不在白名单: {$ip}");
		}

		if (!isset($data['sign']) || !$data['sign'])
		{
			return $this->error($file_log, '缺少sign签名', $data);
		}

		if ($data['sign'] != $this->createSign($data, $this->key_secret))
		{
			return $this->error($file_log, 'sign签名不正确', $data);
		}

		return $this->success($file_log, '成功');
	}
}
