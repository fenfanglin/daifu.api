<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;
use app\extend\auth\JwtAuth;
use app\extend\captcha\Captcha;
use app\model\Admin;
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

		$username = input('post.username');
		$pwd = input('post.pwd');
		$code = input('post.code');
		$uuid = input('post.uuid');

		$captcha = new Captcha();
		$check = $captcha->check($code, $uuid);
		if (!$check)
		{
			return $this->returnError('验证码不正确');
		}


		$model = Admin::where('username', $username)->find();

		if (!$model)
		{
			return $this->returnError('您输入的帐号或密码不正确');
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
			// 记录用户验证错误信息

			return $this->returnError('您输入的帐号或密码不正确！');
		}

		// 清除用户验证错误信息

		if ($model->status != 1)
		{
			return $this->returnError('您的帐号已被禁用！');
		}


		$location = Common::getLocation();

		$log = new LoginLog();
		$log->user_id = $model->id;
		$log->type = 1; //类型：1总后台 2商户	
		$log->ip = $location['ip'];
		$log->area = $location['area'];
		$log->save();


		// $check = true;
		// while ($check)
		// {
		// 	$model->token = Common::randomStr(64);
		// 	$check = Admin::where(['token' => $model->token])->count('id');
		// }

		// 首次登录没有token => 刷新token
		if (!$model->token)
		{
			$check = true;
			while ($check)
			{
				$model->token = Common::randomStr(64);
				$check = Admin::where(['token' => $model->token])->count('id');
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
			'token' => $jwt->createToken($token_data, 86400 * 24),
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

	// /**
	// * 刷新token
	// */
	// public function refresh_token()
	// {
	// 	$jwt = new JwtAuth;

	// 	$data = [
	// 		'token' => $jwt->refreshToken(),
	// 	];

	// 	return $this->returnData($data);
	// }
}