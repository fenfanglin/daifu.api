<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\extend\auth\JwtAuth;
use app\extend\common\BaseController;
use app\extend\common\BaseRole;
use app\model\Setting;
use app\model\Business;
use app\model\BusinessLog;

class AuthController extends BaseController
{
	/**
	 * 初始化
	 */
	public function __construct()
	{
		parent::__construct();

		// 验证Token
		$this->verifyToken();

		// 加载系统设置
		$this->loadSetting();

		// 验证账号状态
		$this->verifyUser();

		// 验证接口权限
		$this->verifyPermission();

		// // 写入操作记录
		// $this->writeLog();
	}

	/**
	 * 验证Token
	 */
	private function verifyToken()
	{
		$jwt = new JwtAuth;

		// if (!$jwt->verifyToken())
		// {
		// 	Common::error($jwt->getError(), Common::APP_ERROR_NOT_LOGIN);
		// }

		$jwt->verifyToken();

		$this->new_token = $jwt->getNewToken();

		$this->token_data = $jwt->getData();
	}

	/**
	 * 验证账号状态
	 */
	private function verifyUser()
	{
		$user = Business::where('token', $this->token_data['user_token'])->find();

		if (!$user)
		{
			Common::error('登录已失效，请重新登录！', Common::APP_ERROR_NOT_LOGIN);
		}

		// 账号认证不通过也可以让商户登录后台，只是不能操作
		// if ($user->verify_status != 1)
		// {
		// 	Common::error('您的帐号未认证通过！', Common::APP_ERROR_LOGIN_DENY);
		// }

		if ($user->status != 1)
		{
			Common::error('您的帐号已被禁用！', Common::APP_ERROR_LOGIN_DENY);
		}

		$user->is_google_auth = $user->google_secret_key && $this->setting->google_auth_business == 1;
		$user->is_auth_when_edit_account = $user->google_secret_key && $this->setting->google_auth_business == 1 && $user->auth_when_edit_account == 1;

		$this->user = $user;
	}

	/**
	 * 验证接口权限
	 */
	private function verifyPermission()
	{
		if ($this->user->type == 1) //代理
		{
			$role = new BaseRole('role_agent');
		}
		elseif ($this->user->type == 2) //工作室
		{
			$role = new BaseRole('role_business_card');
		}
		elseif ($this->user->type == 3) //商户
		{
			$role = new BaseRole('role_sub_business');
		}
		else
		{
			$role = new BaseRole('role_business');
		}

		// 所有权限
		$user_permission = $role->getAllPermission();

		// 生成账号具体权限
		$this->permission = $role->getUserActionPermission($user_permission);

		// 生成账号具体权限（2级）
		$permission_two_level = $role->getUserActionPermissionTwoLevel($this->permission);

		// $current_action = strtolower(request()->controller()) . ':' . strtolower(request()->action());

		// 将控制器 OrderDetail 转换成 order_detail
		$current_action = Common::convertUnderline(request()->controller()) . ':' . Common::convertUnderline(request()->action());

		// var_dump($user_permission, $this->permission, $permission_two_level, $current_action);

		if (!in_array($current_action, $permission_two_level))
		{
			Common::error('无权限请求接口！');
		}
	}

	/**
	 * 加载系统设置
	 */
	private function loadSetting()
	{
		$this->setting = Setting::where('id', 1)->find();

		if (!$this->setting)
		{
			Common::error('加载系统设置失败');
		}
	}

	/**
	 * 写入操作记录
	 */
	protected function writeLog($name = '')
	{
		$user = $this->getUser();

		$log = new BusinessLog();
		$log->business_id = $user->id;
		$log->name = $name;
		$log->ip = Common::getClientIp();
		$log->url = $_SERVER['REQUEST_URI'];
		$log->params = json_encode(request()->post(), JSON_UNESCAPED_UNICODE);

		$log->save();
	}

	/**
	 * 验证谷歌验证码
	 * 验证一般功能（修改密码，修改设置），账号有绑定谷歌密钥就必须验证
	 * 验证修改收款账号，$when_edit_account = 1才验证，账号有打开此功能才验证
	 * $when_edit_account：是否修改收款账号
	 */
	protected function verifyGoogleCode($when_edit_account = 0)
	{
		// // 谷歌身份验证关闭就通过，返回成功
		// if ($this->setting->google_auth_business != 1)
		// {
		// 	return true;
		// }

		// // 账号未绑定谷歌密钥就通过，返回成功
		// if (!$this->user->google_secret_key)
		// {
		// 	return true;
		// }

		// 不需要谷歌验证，返回成功
		if (!$this->user->is_google_auth)
		{
			return true;
		}

		// 是修改收款账号
		if ($when_edit_account)
		{
			// 不需要修改收款账号验证，返回成功
			if (!$this->user->is_auth_when_edit_account)
			{
				return true;
			}
		}


		$rule = [
			'google_code|谷歌验证码' => 'require|alphaNum|max:6',
		];

		$message = [
			'google_code' => '谷歌验证码不正确！',
		];

		if (!$this->validate(input('post.'), $rule, $message))
		{
			$this->google_auth_error = $this->getValidateError();
			return false;
		}


		if (false === Common::verifyGoogleCode($this->user->google_secret_key, input('post.google_code')))
		{
			$this->google_auth_error = '谷歌验证码不正确';
			return false;
		}

		return true;
	}

	/**
	 * 获取验证数据错误
	 */
	protected function getGoogleAuthError()
	{
		return $this->google_auth_error;
	}
}
