<?php
namespace app\pay\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;
use app\model\Setting;
use app\model\PayLog;

class AuthController extends BaseController
{
	public $setting;

	//不写入操作日志
	private $not_log = [
		'/option/role',
		'/option/business',
		'/option/channel',
		'/option/system_bank',
	];


	/**
	 * 初始化
	 */
	public function __construct()
	{
		parent::__construct();

		// 加载系统设置
		$this->loadSystemSetting();

		// // 写入操作日志
		// $this->writeLog();
	}

	/**
	 * 加载系统设置
	 */
	private function loadSystemSetting()
	{
		$this->setting = Setting::where('id', 1)->find();

		if (!$this->setting)
		{
			Common::error('加载系统设置失败');
		}
	}

	/**
	 * 写入日志
	 */
	protected function writeLog()
	{
		if (in_array($_SERVER['REQUEST_URI'], $this->not_log))
		{
			return false;
		}

		$business_id = intval(input('post.mchid'));

		if (in_array($_SERVER['REQUEST_URI'], $this->not_log))
		{
			return false;
		}

		$log = new PayLog();
		$log->business_id = $business_id;
		$log->ip = Common::getClientIp();
		$log->url = $_SERVER['REQUEST_URI'];
		$log->params = json_encode(request()->post(), JSON_UNESCAPED_UNICODE);

		$log->save();
	}

	// -------------------------------------------------------------------------------
	/**
	 * 下单报错
	 * 记录所有下单接口报错
	 */
	protected function orderError($msg)
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg], 'order_error');

		Common::error($msg);
	}

	/**
	 * 下单报错
	 * 只记录参数异常，控制器错误
	 */
	protected function notifyError($msg, $data = [])
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg, 'data' => $data], 'notify_error');

		Common::error($msg);
	}

	// -------------------------------------------------------------------------------
	// 接口签名，每个接口都必须验证签名
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

		// Common::writeLog(['app' => 'Pay', 'str' => $str, 'sign' => strtoupper(md5($str))], 'demo_pay');

		return strtoupper(md5($str));
	}

	/**
	 * 验证签名
	 */
	protected function checkSign($params, $secret_key)
	{
		if (!isset($params['sign']))
		{
			Common::error('缺少sign签名');
		}

		if ($params['sign'] != $this->createSign($params, $secret_key))
		{
			// Common::error('sign签名不正确='.$this->createSign($params, $secret_key).',secret_key='.$secret_key);

//			if (config('app.app_debug') == false)
//			{
				Common::error('sign签名不正确');
//			}
		}
	}

	// -------------------------------------------------------------------------------
	// 控制器安全验证，用于Order执行OrderWebBank->pay()验证
	// 只能通过Order控制器请求OrderWebBank->pay()
	/**
	 * 生成Controller签名（下单接口，监控接口使用）
	 */
	protected function createControllerSign($params, $secret_key)
	{
		if (!$secret_key)
		{
			return false;
		}

		unset($params['sign']);

		return md5(md5(json_encode($params, JSON_UNESCAPED_UNICODE)) . $secret_key);
	}

	/**
	 * 生成控制器安全
	 */
	protected function createSecurity($secret_key)
	{
		$security = [];
		$security['key'] = Common::randomStr(32);
		$security['sign'] = $this->createControllerSign($security, $secret_key);

		return $security;
	}

	/**
	 * 安全验证（只能通过order/create请求）
	 */
	protected function checkSecurity($data)
	{
		if (!$data)
		{
			Common::error('拒绝访问1');
		}

		if (!isset($data['security']))
		{
			Common::error('拒绝访问2');
		}

		if (!isset($data['security']['sign']))
		{
			Common::error('拒绝访问3');
		}

		if (!isset($data['secret_key']))
		{
			Common::error('拒绝访问4');
		}

		// 通道控制器验证
		if ($data['security']['sign'] != $this->createControllerSign($data['security'], $data['secret_key']))
		{
			Common::error('拒绝访问5');
		}

		if (!isset($data['params']))
		{
			Common::error('拒绝访问6');
		}
	}
}
