<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\auth\GoogleAuthenticator;

class GoogleAuthController extends AuthController
{
	private $controller_name = '谷歌密钥';
	
	/**
	 * 查看谷歌验证绑定
	 * 未绑定：返回未绑定，二维码
	 * 已绑定：返回已绑定，是否能解绑
	 */
	public function view()
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
				'allow_unbind' => $this->setting->allow_unbind_admin,
			];
			
			return $this->returnData($data);
		}
	}
	
	/**
	 * 绑定谷歌密钥
	 */
	public function bind()
	{
		$this->writeLog('绑定' . $this->controller_name);
		
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
	public function unbind()
	{
		$this->writeLog('解绑' . $this->controller_name);
		
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
		if ($this->setting->allow_unbind_admin != 1)
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