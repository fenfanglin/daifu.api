<?php
namespace app\business\controller;

use app\extend\common\Common;

/**
 * 测试下单
 */
class DemoController extends AuthController
{
	private $controller_name = '测试下单';

	/**
	 * 下单接口
	 */
	public function pay()
	{
		$this->writeLog($this->controller_name . '提交');

		$params = input('post.');

		$rule = [
			'account_type|收款类型' => 'require|integer|>:0',
			'out_trade_no|商户订单号' => 'alphaNum|max:30',
			'amount|金额' => 'require|float|>:0',
			'notify_url|回调地址' => 'max:255',
			'attach|附加数据' => 'max:255',
		];

		$message = [
			'account_type' => '收款类型不正确',
			'out_trade_no' => '商户订单号不正确',
			'amount' => '金额不正确',
			'notify_url' => '回调地址不正确',
		];

		if (!$this->validate($params, $rule, $message))
		{
			return $this->returnError($this->getValidateError());
		}

		$user = $this->getUser();

		if ($user->type !== 3)
		{
			return $this->returnError('非商户不可下单！');
		}
		$params['mchid'] = $user->id;

		$params['out_trade_no'] = isset($params['out_trade_no']) ? $params['out_trade_no'] : 'UD' . date('YmdHis') . rand(1000, 9999);
		$params['notify_url'] = isset($params['notify_url']) ? $params['notify_url'] : config('app.api_url') . 'notify/notify';

		if ($params['account_type'] == 1)
		{
			if (empty($params['bank']))
				return $this->returnError('银行卡名称不能为空');
			if (empty($params['branch']))
				return $this->returnError('银行支行不能为空');
			if (empty($params['account_name']))
				return $this->returnError('姓名不能为空');
			if (empty($params['account']) || !checkBanKCard($params['account']))
				return $this->returnError('请填写正确的银行卡号');
			if (empty($params['amount']) || !is_numeric($params['amount']) || $params['amount'] <= 0)
				return $this->returnError('订单金额需要大于0');
		}
		elseif ($params['account_type'] == 2)
		{
			if (empty($params['account']))
				return $this->returnError('钱包地址不能为空');
			if (empty($params['amount']) || !is_numeric($params['amount']) || $params['amount'] <= 0)
				return $this->returnError('订单金额需要大于0');
			$usdt_rate = $this->setting->usdt_rate;
			$params['usdt_amount'] = number_format($params['amount'] / $usdt_rate, 2, '.', '');
		}
		else
		{
			if (empty($params['account_name']))
				return $this->returnError('姓名不能为空');
			if (empty($params['account']))
				return $this->returnError('支付宝账号不能为空');
			if (empty($params['amount']) || !is_numeric($params['amount']) || $params['amount'] <= 0)
				return $this->returnError('订单金额需要大于0');
		}
		$params['timestamp'] = time();

		$params['sign'] = $this->createSign($params, $user->secret_key);

		//        return $this->returnData($params);
//
		$url = config('app.api_url') . 'order/create';

		Common::writeLog([
			'url' => $url,
			'params' => $params,
		], 'demo_pay');

		$res = Common::curl($url, $params);
		$res = json_decode($res, true);

		Common::writeLog($res, 'demo_pay', false);

		if (isset($res['code']) && $res['code'] == '200')
		{
			return $this->returnSuccess('下单成功');
		}
		elseif (isset($res['msg']))
		{
			return $this->returnError($res['msg']);
		}
		else
		{
			return $this->returnError('接口错误');
		}
	}

	/**
	 * 生成签名
	 */
	protected function createSign($params, $secret_key)
	{
		unset($params['sign']);

		ksort($params); //字典排序

		$str = '';
		foreach ($params as $key => $value)
		{
			$str .= strtolower($key) . '=' . $value . '&';
		}

		$str .= 'key=' . $secret_key;

		// Common::writeLog(['app' => 'Demo', 'str' => $str, 'sign' => strtoupper(md5($str))], 'demo_pay');

		return strtoupper(md5($str));
	}
}
