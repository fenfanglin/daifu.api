<?php
namespace app\service\api;

use app\extend\common\Common;

class Jinqianbao
{
	protected $host = 'https://pay.tddnn.top';

	protected $create_url = '/pay-api/transfer/order/create';
	protected $query_url = '/pay-api/transfer/order/query';
	protected $notify_url = 'api_notify_jinqianbao/index';
	protected $return_url = 'api_notify_jinqianbao/index';


	protected $mchid;
	protected $key;

    private static $encodingCharset = "UTF-8";
	/**
	 * 构造方法
	 */
	public function __construct()
	{
		$this->mchid = 60093;
        $this->key = '5f73cf0d4ea444cbba8a87bee404d12e';
	}

	/**
	 * 报错
	 */
	private function error($msg, $data = [])
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg, 'data' => $data], 'YiYunHuiService_error');

		Common::error($msg);
	}

    /**
     * 计算签名摘要
     * @param array $map 参数数组
     * @param string $key 商户秘钥
     * @return string
     */
    function paramArraySign($paramArray, $appKey){

        ksort($paramArray);  //字典排序
        reset($paramArray);
        $md5str = "";
        foreach ($paramArray as $key => $val) {
            if( strlen($key)  && strlen($val) ){
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }

        $sign = strtoupper(md5($md5str . "key=" . $appKey));  //签名

        return $sign;

    }
    /**
     * 生成订单
     */
    public function create($data)
    {
        $url = $this->host . $this->create_url;
        $param = array(
            "merchantNo" => $this->mchid, //商户ID
            "merchantOrderNo" => $data["out_trade_no"] ,  // 商户订单号
            "channelCode" => $data["channelCode"],  //支付方式
            "amount" => $data["amount"], // 支付金额
            "notifyUrl" => config('app.api_url') .$this->notify_url,	 //支付结果后台回调URL
            "version" => '1.0',	 //版本号, 固定参数1.0
            "payeeName" => $data['account_name'],
            "payeeAccount" => $data['account'],
            "payeeBankName"=> '',
        );
        if(isset($data['bank'])){
            $param['payeeBankName'] = $data['bank'];
        }
        $sign = self::paramArraySign($param, $this->key);  //签名
        $param["sign"] = $sign;
        // 发送HTTP POST请求
        $response = $this->httpPost($url, json_encode($param));
        $response = json_decode($response,true);
        Common::writeLog(['url' => $url, 'params' => $param, 'res' => $response], 'jinqianbao_create');
        if (!isset($response['code']) || $response['code'])
        {
            $this->error("{$this->create_url}接口没返回url: " . json_encode($response, JSON_UNESCAPED_UNICODE), $response);
        }
        return true;
    }
    /**
     * 发送HTTP POST请求
     * @param string $url 请求的URL
     * @param array $data POST的数据
     * @return string 响应结果
     */
    function httpPost($url, $paramStr){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paramStr,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "welcome: welcome-pay"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        }
        return $response;
    }
	/**
	 * 查单订单
	 */
	public function query($order_id)
	{
		$url = $this->host . $this->query_url;

		$params = [];
		$params['merchantNo'] = $this->mchid;
		$params['merchantOrderNo'] = $order_id;
		$params['version'] = '1.0';
        $sign = self::paramArraySign($params, $this->key);  //签名
        $params["sign"] = $sign;
		$res = $this->httpPost($url, json_encode($params));
		$res = json_decode($res, true);
		Common::writeLog(['url' => $url, 'params' => $params, 'res' => $res], 'jinqianbao_query');

		if (!isset($res['code']) || $res['code'])
		{
			$this->error("{$this->query_url}接口没返回retCode: " . json_encode($res, JSON_UNESCAPED_UNICODE), $res);
		}
		if (!isset($res['data']['amount']))
		{
			$this->error("{$this->query_url}接口没返回amount: " . json_encode($res, JSON_UNESCAPED_UNICODE), $res);
		}
		return $res;
	}

	/**
	 * 验证回调信息
	 */
	public function checkNotifyData($data)
	{
		if (!isset($data['sign']) || !$data['sign'])
		{
			Common::error('缺少sign签名');
            Common::writeLog(['params' => $data, 'res' => '缺少sign签名'], 'jinqianbaoError');
		}
        $_data = $data;
        unset($_data['sign']);
		if ($data['sign'] != $this->paramArraySign($_data, $this->key))
		{
			Common::error('sign签名不正确');
            Common::writeLog(['params' => $data, 'res' => 'sign签名不正确'], 'jinqianbaoError');
		}
	}

	private function curl($url, $post = [], $header = [], $is_debug = false, $cookie = [], $nobody = 0)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');

		if ($post)
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

			// curl_setopt($ch, CURLOPT_POST, TRUE);
			// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
		}

		if ($header)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}

		if ($cookie)
		{
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}

		if ($nobody)
		{
			curl_setopt($ch, CURLOPT_NOBODY, 1);
		}

		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);


		if ($is_debug == false)
		{
			if ($res)
			{
				curl_close($ch);
				return $res;
			}
			else
			{
				$error = curl_errno($ch);
				curl_close($ch);
				return $error;
			}
		}
		else
		{
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$http_error = curl_error($ch);
			$curl_errno = curl_errno($ch);
			curl_close($ch);

			return [
				'url' => $url,
				'response' => $res,
				'http_code' => $http_code,
				'http_error' => $http_error,
				'curl_errno' => $curl_errno,
			];
		}

	}
}
