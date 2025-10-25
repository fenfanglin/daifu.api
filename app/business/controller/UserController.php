<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\extend\auth\GoogleAuthenticator;
use app\model\BusinessChannel;


class UserController extends AuthController
{
	private $controller_name = '基本设置';

	/**
	 * 查看设置
	 */
	public function view_setting()
	{
		$data = [];
		$data['user_type'] = $this->user->type;
		$data['login_ip'] = $this->user->login_ip;
		$data['multiple_login'] = (string) $this->user->multiple_login;
		$data['random_amount'] = $this->user->random_amount;
		$data['usdt_rate_type'] = (string) $this->user->usdt_rate_type;
		$data['usdt_rate'] = $this->user->usdt_rate;
		$data['auth_when_edit_account'] = (string) $this->user->auth_when_edit_account;
		$data['is_google_auth'] = $this->user->is_google_auth;

		$data['random_amount_note'] = "下单提交金额会随机加或减{$this->setting->random_amount_min}~{$this->setting->random_amount_max}";
		$data['usdt_rate_type_note'] = '每10分钟会更新OKX汇率一次';
		$data['audio_loop'] = $this->user->audio_loop;


		return $this->returnData($data);
	}

	/**
	 * 保存设置
	 */
	public function save_setting()
	{
		$this->writeLog($this->controller_name . '保存');

		$rule = [
			'random_amount|随机金额' => 'in:-1,1,2',
			'usdt_rate_type|自动更新Usdt汇率' => 'in:1,2',
			'usdt_rate|Usdt手动汇率' => 'float|>=:0',
			'auth_when_edit_account|修改收款账号需要谷歌验证码' => 'in:1,-1',
			'multiple_login|允许多台电脑同时登录' => 'in:1,-1',
			'login_ip|登录ip白名单' => 'max:1000',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		if (!$this->verifyGoogleCode())
		{
			return $this->returnError($this->getGoogleAuthError());
		}


		$this->user->random_amount = input('post.random_amount');
		$this->user->usdt_rate_type = input('post.usdt_rate_type');
		$this->user->usdt_rate = input('post.usdt_rate');
		$this->user->auth_when_edit_account = input('post.auth_when_edit_account');
		$this->user->multiple_login = input('post.multiple_login');
		$this->user->login_ip = trim(input('post.login_ip'));
		$this->user->audio_loop = trim(input('post.audio_loop'));


		if (!$this->user->save())
		{
			return $this->returnError('修改失败');
		}

		return $this->returnSuccess('修改成功');
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

	/**
	 * 检查商户通道是否有开启
	 */
	public function check_channel()
	{
		$rule = [
			'channel_id|通道id' => 'require|integer|>:0',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$channel_id = input('post.channel_id');

		$business_channel = \app\model\BusinessChannel::where(['business_id' => $this->user->id, 'channel_id' => $channel_id])->find();
		if (!$business_channel || $business_channel->status != 1)
		{
			return $this->returnData(['msg' => '商户通道未开启', 'status' => -1]);
		}

		if ($this->user->parent_id)
		{
			$parent_business_channel = \app\model\BusinessChannel::where(['business_id' => $this->user->parent_id, 'channel_id' => $channel_id])->find();
			if (!$parent_business_channel || $parent_business_channel->status != 1)
			{
				return $this->returnData(['msg' => '四方通道未开启', 'status' => -1]);
			}
		}

		return $this->returnData(['msg' => '通道已开启', 'status' => 1]);
	}

	/**
	 * 获取绑定JQK
	 */
	public function get_bind_jqk()
	{
		$user = $this->getUser();

		// 获取代付绑定JQK系统的商户id
		$jqk_business_id = \app\service\SystemRelationService::getDaifuBindingJqkBusinessId($user->id);

		// 检查JQK商户ID是否存在
		$model_jqk = \app\model\JQK_Business::where('id', $jqk_business_id)->find();
		if ($model_jqk)
		{
			$data = [
				'jqk_business_id' => $model_jqk->id,
				'jqk_business_name' => $model_jqk->realname,
			];
		}
		else
		{
			// 代付商户解绑JQK商户
			\app\service\SystemRelationService::daifuBusinessUnbindingJqkBusiness($user->id);

			$data = [
				'jqk_business_id' => '',
				'jqk_business_name' => '',
			];
		}

		return $this->returnData($data);
	}

	/**
	 * 登录JQK系统
	 */
	public function login_jqk()
	{
		$this->writeLog('登录代付系统');

		$user = $this->getUser();

		// 获取代付绑定JQK系统的商户id
		$jqk_business_id = \app\service\SystemRelationService::getDaifuBindingJqkBusinessId($user->id);

		// 检查JQK商户ID是否存在
		$model_jqk = \app\model\JQK_Business::where('id', $jqk_business_id)->find();
		if (!$model_jqk)
		{
			return $this->returnError('代付商户不存在');
		}

		$auth = new \app\extend\auth\CrossDomainAuth;

		$res = $auth->generate($user->no, $model_jqk->no);

		Common::writeLog([
			'res' => $res,
		], 'auth_login_jqk');

		$params = [
			'token' => Common::randomStr(64),
			'cache' => $res['token'],
			'data' => $res['key'],
			'key' => Common::randomStr(16),
			'sign' => md5(Common::randomStr(6)),
			'timestamp' => time(),
			'referer' => 'relation_system',
		];

		$host = env('JQK.BUSINESS_URL', '') . '/error?';

		$data = [
			'href' => $host . http_build_query($params),
		];

		return $this->returnData($data);
	}

	/**
	 * 查看通道设置
	 */
	public function channel_setting_view()
	{
		// 类型：1代理 2工作室 3商户
		if (!in_array($this->user->type, [1]))
		{
			Common::error('此功能不开放给商户');
		}

		$rule = [
			'channel_id|通道id' => 'require|integer|>:0',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$business_channel = \app\model\BusinessChannel::where(['business_id' => $this->user->id, 'channel_id' => input('post.channel_id')])->find();
		if (!$business_channel)
		{
			return $this->returnError('无法找到信息');
		}

		$data = [];
		$data['execute_time_limit'] = $business_channel->execute_time_limit;
		$data['status'] = (string) $business_channel->status;

		// 关闭商户拉黑功能
		if ($this->user->block_order == -1)
		{
			$data['blacklist_disabled'] = true;
			$data['block_order'] = '-1';
			$data['refund_order'] = '-1';
		}

		return $this->returnData($data);
	}

	/**
	 * 修改通道设置
	 */
	public function channel_setting_save()
	{
		// 类型：1代理 2工作室 3商户
		if (!in_array($this->user->type, [1, 3]))
		{
			Common::error('此功能不开放给商户');
		}

		$rule = [
			'channel_id|通道id' => 'require|integer|>:0',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$business_channel = \app\model\BusinessChannel::where(['business_id' => $this->user->id, 'channel_id' => input('post.channel_id')])->find();
		if (!$business_channel)
		{
			return $this->returnError('无法找到信息');
		}

		// if ($business_channel->business_id != $this->user->id)
		// {
		// 	return $this->returnError('信息不属于商户');
		// }
		$post = input('post.');

		$business_channel->execute_time_limit = isset($post['execute_time_limit']) ? $post['execute_time_limit'] : $business_channel->execute_time_limit;
		$business_channel->status = isset($post['status']) ? $post['status'] : $business_channel->status;

		if (!$business_channel->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog("修改通道设置：{$business_channel->channel->name}");

		return $this->returnSuccess('成功');
	}
}
