<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;
use app\extend\auth\JwtAuth;
use app\extend\auth\CrossDomainAuth;
use app\extend\captcha\Captcha;
use app\model\Admin;
use app\model\Business;
use app\model\LoginLog;
use think\middleware\Throttle;

class LoginController extends BaseController
{
	protected $middleware = [
		Throttle::class,
	];

	/**
	 * 登录
	 */
	public function index()
	{
		$rule = [
			'username|账号' => 'require|alphaNum|max:50',
			'pwd|密码' => 'require|max:50',
			'code|图片验证码' => 'require|max:5',
			'uuid' => 'require|max:32',
		];

		$message = [
			'username' => '账号不正确',
			'pwd' => '密码不正确',
			'code' => '图片验证码不正确！',
			'uuid' => '图片验证码不正确。',
		];

		if (!$this->validate(input('post.'), $rule, $message))
		{
			return $this->returnError($this->getValidateError());
		}

		$_data = input('post.');
		$_data['ip'] = Common::getClientIp();
		Common::writeLog($_data, 'business_login');

		$username = input('post.username');
		$pwd = input('post.pwd');
		$code = input('post.code');
		$uuid = input('post.uuid');

		$captcha = new Captcha();
		$check = $captcha->check($code, $uuid);
		if (!$check)
		{
			return $this->returnError('图片验证码不正确');
		}


		$model = Business::where('username', $username)->find();

		if (!$model)
		{
			return $this->returnError('您输入的帐号或密码不正确1');
		}

		// 账号有绑定谷歌密钥，必须验证谷歌验证码
		if ($model->google_secret_key)
		{
			$rule = [
				'google_code|谷歌验证码' => 'require|alphaNum|max:6',
			];

			$message = [
				'google_code' => '谷歌验证码不正确！',
			];

			if (!$this->validate(input('post.'), $rule, $message))
			{
				// 记录用户验证错误信息

				return $this->returnError($this->getValidateError());
			}

			if (false === Common::verifyGoogleCode($model->google_secret_key, input('post.google_code')))
			{
				// 记录用户验证错误信息

				return $this->returnError('谷歌验证码不正确');
			}
		}

		if (Common::generatePassword($pwd, $model->auth_key) != $model->password)
		{
			return $this->returnError('您输入的帐号或密码不正确！');
		}

		// 清除用户验证错误信息


		// 获取ip与地区信息
		$location = Common::getLocation();

		//判断是白名单登录
		$ip = $location['ip'];
		if (trim($model->login_ip))
		{
			$login_ip = explode("\n", $model->login_ip);
			if (!in_array($ip, $login_ip))
			{
				return $this->returnError('此ip不在登录ip白名单！');
			}
		}

		// 帐号被禁用
		if ($model->status != 1)
		{
			return $this->returnError('您的帐号已被禁用！');
		}


		$log = new LoginLog();
		$log->user_id = $model->id;
		$log->type = 2; //类型：1总后台 2商户
		$log->ip = $location['ip'];
		$log->area = $location['area'];
		$log->save();

		// 如果没开启多台电脑同时登录 => 每次登录就刷新token
		// 首次登录没有token => 刷新token
		if ($model->multiple_login == -1 || !$model->token)
		{
			$check = true;
			while ($check)
			{
				$model->token = Common::randomStr(64);
				$check = Business::where(['token' => $model->token])->count('id');
			}
		}

		$model->last_login_time = date('Y-m-d H:i:s');
		$model->login_count++;

		if ($model->save() === false)
		{
			return $this->returnError('帐号更新失败！');
		}

		$token_data = [
			'user_token' => $model->token,
			'role_id' => $model->role_id,
		];

		$jwt = new JwtAuth;

		$data = [
			'token' => $jwt->createToken($token_data),
		];

		return $this->returnData($data);
	}

	/**
	 * 获取captcha
	 */
	public function captcha()
	{
		$captcha = new Captcha();
		$res = $captcha->create();

		$data = [
			'img' => $res['image'],
			'key' => $res['key'],
		];

		return $this->returnData($data);
	}

	/**
	 * 总后台登录商户账号
	 */
	public function check_login()
	{
		$rule = [
			'token' => 'require',
			'cache' => 'require',
			'data' => 'require',
			'key' => 'require',
			'sign' => 'require',
			'timestamp' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			// return $this->returnError('page not found');
			return $this->returnError($this->getValidateError());
		}

		$token = input('post.cache');
		$key = input('post.data');
		$referer = input('post.referer') ?? '';
		$auth = new CrossDomainAuth;

		$auth->verify($token, $key);
		$res = $auth->getData();

		if ($referer == 'relation_system')
		{

		}
		else
		{
			$admin = Admin::where('no', $res['admin_code'])->find();
			if (!$admin)
			{
				return $this->returnError('admin_code不正确');
			}
		}

		$business = Business::where('no', $res['business_code'])->find();
		if (!$business)
		{
			return $this->returnError('business_code不正确');
		}

		if ($business->status != 1)
		{
			return $this->returnError('您的帐号已被禁用！');
		}

		// 账号认证不通过也可以让商户登录后台，只是不能操作
		// if ($business->verify_status != 1)
		// {
		// 	return $this->returnError('您的帐号未认证通过！');
		// }


		if (!$business->token)
		{
			$check = true;
			while ($check)
			{
				$business->token = Common::randomStr(64);
				$check = Business::where(['token' => $business->token])->count('id');
			}

			if ($business->save() === false)
			{
				return $this->returnError('帐号更新失败！');
			}
		}


		$token_data = [
			'user_token' => $business->token,
			'role_id' => $business->role_id,
		];

		$jwt = new JwtAuth;

		$data = [
			'token' => $jwt->createToken($token_data),
		];

		return $this->returnData($data);
	}
}
