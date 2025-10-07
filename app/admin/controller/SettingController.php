<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\auth\GoogleAuthenticator;

class SettingController extends AuthController
{
	private $controller_name = '设置';
	
	/**
	 * 查看
	 */
	public function view()
	{
		$data = [];
		$data['recharge_channel'] = $this->setting->recharge_channel;
		$data['recharge_account_usdt'] = $this->setting->recharge_account_usdt;
		$data['less_money_notify'] = (string)$this->setting->less_money_notify;
		$data['less_money_can_view_order'] = (string)$this->setting->less_money_can_view_order;
		$data['less_money_can_order'] = (string)$this->setting->less_money_can_order;
		$data['cannot_order_less_than'] = $this->setting->cannot_order_less_than;
		
		$data['is_google_auth'] = $this->user->is_google_auth;
		
		
		return $this->returnData($data);
	}
	
	/**
	 * 保存
	 */
	public function save()
	{
		$this->writeLog('保存' . $this->controller_name);
		
		$rule = [
			'recharge_channel|充值通道' => 'in:1',
			'recharge_account_usdt|Usdt充值地址' => 'max:255',
			'less_money_notify|商户余额不足提醒' => 'in:1,-1',
			'less_money_can_view_order|商户余额不足允许查看订单' => 'in:1,-1',
			'less_money_can_order|商户余额不足允许下单' => 'in:1,-1',
			'cannot_order_less_than|商户余额低于额度不能下单' => 'float|<=:0',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		if (!$this->verifyGoogleCode())
		{
			return $this->returnError($this->getGoogleAuthError());
		}
		
		
		$this->setting->recharge_channel = input('post.recharge_channel');
		$this->setting->recharge_account_usdt = trim(input('post.recharge_account_usdt'));
		$this->setting->less_money_notify = input('post.less_money_notify');
		$this->setting->less_money_can_view_order = input('post.less_money_can_view_order');
		$this->setting->less_money_can_order = input('post.less_money_can_order');
		$this->setting->cannot_order_less_than = input('post.cannot_order_less_than');
		
		
		if (!$this->setting->save())
		{
			return $this->returnError('修改密码失败');
		}
		
		return $this->returnSuccess('修改密码成功');
	}
	
	/**
	 * 修改密码
	 */
	public function change_password()
	{
		$this->writeLog($this->controller_name . '修改密码');
		
		$rule = [
			'password|密码' => 'require|min:6|max:50',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		if (!$this->verifyGoogleCode())
		{
			return $this->returnError($this->getGoogleAuthError());
		}
		
		
		$password = input('post.password');
		
		$this->user->auth_key = Common::randomStr(6);
		$this->user->password = Common::generatePassword($password, $this->user->auth_key);
		
		
		if (!$this->user->save())
		{
			return $this->returnError('修改密码失败');
		}
		
		return $this->returnSuccess('修改密码成功');
	}
	
	/**
	 * 修改监控密码
	 */
	public function change_password_api()
	{
		$this->writeLog($this->controller_name . '修改监控密码');
		
		$rule = [
			'username_api|监控账号' => 'require|alphaNum|max:20',
			'password_api|密码' => 'require|min:6|max:50',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		if (!$this->verifyGoogleCode())
		{
			return $this->returnError($this->getGoogleAuthError());
		}
		
		
		$password_api = input('post.password_api');
		
		$this->user->auth_key_api = Common::randomStr(6);
		$this->user->password_api = Common::generatePassword($password_api, $this->user->auth_key_api);
		
		$this->user->username_api = input('post.username_api');
		
		if (!$this->user->save())
		{
			return $this->returnError('修改密码失败');
		}
		
		return $this->returnSuccess('修改密码成功');
	}
	
	/**
	 * 查看谷歌验证绑定
	 * 未绑定：返回未绑定，二维码
	 * 已绑定：返回已绑定，是否能解绑
	 */
	public function google_auth_view()
	{
		if (!$this->user->google_secret_key)
		{
			$ga = new GoogleAuthenticator;
			
			// 生成谷歌密钥
			$google_secret_key = $ga->createSecret();
			
			// 把谷歌密钥缓存
			$key = 'google_secret_key_' . $this->user->no;
			$this->redis->set($key, $google_secret_key, config('app.google_secret_key_cache'));
			
			$code_title = $_SERVER['HTTP_HOST'] . '@' . $this->user->username;
			
			// 生成谷歌密钥链接
			$qrcode = $ga->getQRCodeGoogleUrl($code_title, $google_secret_key);
			
			$data = [
				'is_bind' => 0,
				'qrcode' => $qrcode,
			];
			
			return $this->returnData($data);
		}
		else
		{
			$data = [
				'is_bind' => 1,
				'allow_unbind' => $this->setting->allow_unbind_business,
			];
			
			return $this->returnData($data);
		}
	}
	
	/**
	 * 绑定谷歌密钥
	 */
	public function google_auth_bind()
	{
		$this->writeLog($this->controller_name . '绑定谷歌密钥');
		
		$rule = [
			'google_code|谷歌验证码' => 'require|alphaNum|max:6',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		// 账号已绑定谷歌密钥，不能再绑定
		if ($this->user->google_secret_key)
		{
			return $this->returnError('账号已绑定谷歌密钥');
		}
		
		// 获取谷歌密钥缓存
		$key = 'google_secret_key_' . $this->user->no;
		$google_secret_key = $this->redis->get($key);
		
		// 缓存密钥过期或者不存在
		if (!$google_secret_key)
		{
			return $this->returnError('密钥已过期，请重新绑定');
		}
		
		$ga = new GoogleAuthenticator;
		if (false === $ga->verifyCode($google_secret_key, input('post.google_code')))
		{
			return $this->returnError('谷歌验证码不正确');
		}
		
		$this->user->google_secret_key = $google_secret_key;
		if (!$this->user->save())
		{
			return $this->returnError('绑定失败');
		}
		
		return $this->returnSuccess('绑定成功');
	}
	
	/**
	 * 解绑谷歌密钥
	 */
	public function google_auth_unbind()
	{
		$this->writeLog($this->controller_name . '解绑谷歌密钥');
		
		$rule = [
			'google_code|谷歌验证码' => 'require|alphaNum|max:6',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		// 账号未绑定谷歌密钥
		if (!$this->user->google_secret_key)
		{
			return $this->returnError('账号未绑定谷歌身份验证');
		}
		
		// 解绑谷歌密钥没开启
		if ($this->setting->allow_unbind_business != 1)
		{
			return $this->returnError('系统不允许解绑谷歌身份验证，请联系后台管理员！');
		}
		
		$ga = new GoogleAuthenticator;
		if (false === $ga->verifyCode($this->user->google_secret_key, input('post.google_code')))
		{
			return $this->returnError('谷歌验证码不正确');
		}
		
		$this->user->google_secret_key = NULL;
		if (!$this->user->save())
		{
			return $this->returnError('解绑失败');
		}
		
		return $this->returnSuccess('解绑成功');
	}
}