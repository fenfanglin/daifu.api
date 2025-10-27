<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\extend\common\BaseRole;
use app\model\Role;
use app\model\LoginLog;
use app\model\Order;
use app\model\Business;

class IndexController extends AuthController
{
	/**
	 * 获取前端菜单
	 */
	public function get_router()
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

		$permission = $role->getAllPermission();
		$routers = $role->getRouter($permission);

		return $this->returnData($routers);
	}

	public function index()
	{
		$business_id = $this->user->id;
		$url = 'index/index';
		$name = '首页';
		$num = 1;
		$num_sql = 6;
		$use_cache = 0;
		//		\app\model\StatisticsLog::addLog($business_id, $url, $name, $num, $num_sql, $use_cache);


		$key = 'business_data_index_' . $this->user->id;

		if ($data = $this->redis->get($key))
		{
			return $this->returnData($data);
		}


		$data = [];

		$log = LoginLog::where(['user_id' => $this->user->id, 'type' => 2])->order('id desc')->find();
		$data['log'] = $log ?? [];


		$where_business = [];
		//类型：1代理 2工作室 3商户
		if (in_array($this->user->type, [1]))
		{
			$field = 'agent';
			$where_business[] = ['business_id', '=', $this->user->id];
		}
		elseif (in_array($this->user->type, [2]))
		{
			$field = 'card';
			$where_business[] = ['card_business_id', '=', $this->user->id];
		}
		elseif (in_array($this->user->type, [3]))
		{
			$field = 'business';
			$where_business[] = ['sub_business_id', '=', $this->user->id];
		}
		else
		{
			$field = '';
			$where_business[] = ['id', '=', 0]; //不显示
		}

		// --------------------------------------------------------------------------
		// 今日订单入金
		$where = $where_business;
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
		$data['info']['today_amout'] = Order::where($where)->sum('amount');

		// --------------------------------------------------------------------------
		// 昨日订单入金
		$where = $where_business;
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
		$where[] = ['success_time', '<', date('Y-m-d')];
		$data['info']['yesterday_amout'] = Order::where($where)->sum('amount');

		// --------------------------------------------------------------------------
		// 累计订单入金
		$where = $where_business;
		$where[] = ['status', 'in', [1, 2]];
		$data['info']['total_amount'] = Order::where($where)->sum('amount');

		// --------------------------------------------------------------------------
		// 累计订单量
		$where = $where_business;
		$where[] = ['status', 'in', [1, 2]];
		$data['info']['total_order'] = Order::where($where)->count('id');
		// echo Order::where($where)->fetchSql(1)->count('id');

		// --------------------------------------------------------------------------
		// 今日平台总费用
		$where = $where_business;
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
		$commission = Order::where($where)->sum("{$field}_commission");
		$order_fee = Order::where($where)->sum("{$field}_order_fee");
		$data['info']['today_fee'] = number_format($commission + $order_fee, 4, '.', '');

		// --------------------------------------------------------------------------
		// 昨日平台总费用
		$where = $where_business;
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
		$where[] = ['success_time', '<', date('Y-m-d')];
		$commission = Order::where($where)->sum("{$field}_commission");
		$order_fee = Order::where($where)->sum("{$field}_order_fee");
		$data['info']['yesterday_fee'] = number_format($commission + $order_fee, 4, '.', '');

		$this->redis->set($key, $data, getDataCacheTime());

		return $this->returnData($data);
	}

	/**
	 * 获取账号信息
	 */
	public function get_userinfo()
	{
		$user = $this->getUser();

		$type_str = isset(Business::TYPE[$user->type]) ? Business::TYPE[$user->type] : '';

		$data = [];
		$data['id'] = $user->id;
		$data['parent_id'] = $user->parent_id;
		$data['name'] = $user->username;
		$data['user_type'] = $user->type;
		$data['system_name'] = $type_str . '后台管理';
		$data['secret_key'] = $user->secret_key;
		$data['login_ip'] = $user->login_ip;
		$data['random_amount'] = $user->random_amount;
		$data['usdt_rate_type'] = $user->usdt_rate_type;
		$data['usdt_rate'] = $user->usdt_rate;
		$data['verify_status'] = $user->verify_status;

		$data['avatar'] = 'https://jqkpay.oss-cn-hongkong.aliyuncs.com/static/images/avatar.png';
		$data['is_google_auth'] = $user->is_google_auth;
		$data['is_auth_when_edit_account'] = $user->is_auth_when_edit_account;

		$data['role'] = config('app.app_name');
		$data['permissions'] = $this->permission;
		$data['audio_loop'] = $user->audio_loop;

		return $this->returnData($data);
	}

	/**
	 * 获取账号信息
	 * 前端获取当下用户信息
	 */
	public function userinfo()
	{
		$user = $this->user;

		$type_str = isset(Business::TYPE[$user->type]) ? Business::TYPE[$user->type] : '';

		$data = [];
		$data['id'] = $user->id;
		$data['parent_id'] = $user->parent_id;
		$data['name'] = $user->username;
		$data['money'] = $user->money;
		$data['allow_withdraw'] = floatval($user->allow_withdraw);
		$data['type'] = $user->type;
		$data['user_type'] = $user->type;
		$data['secret_key'] = $user->secret_key;
		$data['login_ip'] = $user->login_ip;
		$data['multiple_login'] = (string) $user->multiple_login;
		$data['random_amount'] = $user->random_amount;
		$data['usdt_rate_type'] = (string) $user->usdt_rate_type;
		$data['usdt_rate'] = $user->usdt_rate;
		$data['auth_when_edit_account'] = (string) $user->auth_when_edit_account;
		$data['remark_when_balance_over'] = (float) $user->remark_when_balance_over;
		$data['verify_status'] = $user->verify_status;

		$data['is_google_auth'] = $user->is_google_auth;
		$data['is_auth_when_edit_account'] = $user->is_auth_when_edit_account;

		$data['system_name'] = $type_str . '后台管理';
		$data['system_logo'] = '/favicon.ico';
		$data['order_status'] = $user->order_status;
		$data['audio_loop'] = $user->audio_loop;

		if ($user->type == 1)
		{
			$data['system_name'] = $user->system_name ?: $data['system_name'];
			$data['system_logo'] = $user->system_logo ?: $data['system_logo'];
		}
		elseif ($user->parent && $user->parent->type == 1)
		{
			$data['system_name'] = $user->parent->system_name ?: $data['system_name'];
			$data['system_logo'] = $user->parent->system_logo ?: $data['system_logo'];
		}
		return $this->returnData($data);
	}

	/**
	 * 生成签名
	 */
	public function get_sign()
	{
		$id = config('oss.accessKeyId');		  // 请填写您的AccessKeyId。
		$key = config('oss.accessKeySecret');		  // 请填写您的AccessKeyId。
		// $host的格式为 bucketname.endpoint，请替换为您的真实信息。
		$host = 'https://jqkstore.oss-cn-hongkong.aliyuncs.com';
		// $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
		$callbackUrl = 'https://demodoc.jqkpay.top/alipay/notify_url';
		$dir = 'images/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . date('His_') . Common::randomStr(10); // 用户上传文件时指定的前缀。

		$callback_param = array(
			'callbackUrl' => $callbackUrl,
			'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
			'callbackBodyType' => "application/x-www-form-urlencoded"
		);
		$callback_string = json_encode($callback_param);

		$base64_callback_body = base64_encode($callback_string);
		$now = time();
		$expire = 300;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
		$end = $now + $expire;
		$expiration = $this->gmt_iso8601($end);


		//最大文件大小.用户可以自己设置
		$condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
		$conditions[] = $condition;

		// 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
		$start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
		$conditions[] = $start;


		$arr = array('expiration' => $expiration, 'conditions' => $conditions);
		$policy = json_encode($arr);
		$base64_policy = base64_encode($policy);
		$string_to_sign = $base64_policy;
		$signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

		$data = [];
		$data['accessid'] = $id;
		$data['host'] = $host;
		$data['policy'] = $base64_policy;
		$data['signature'] = $signature;
		$data['expire'] = $end;
		$data['callback'] = $base64_callback_body;
		$data['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。

		return $this->returnData($data);
	}

	protected function gmt_iso8601($time)
	{
		return str_replace('+00:00', '.000Z', gmdate('c', $time));
	}


	/**
	 * 生成access_token
	 * @return mixed|string
	 */
	public function get_token()
	{
		$token = cache('access_token');
		if ($token)
		{
			return $token;
		}

		$appid = '41229351';
		$app_key = 'SRXDvcpxaWcVXebbKVaRFnWe';
		$app_secret = '1FrypGOz99bs07qhTLpeNiubLTXCRMT5';
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://aip.baidubce.com/oauth/2.0/token?client_id={$app_key}&client_secret={$app_secret}&grant_type=client_credentials",
			CURLOPT_TIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CUSTOMREQUEST => 'POST',


			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Accept: application/json'
			),

		));
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);
		if (!empty($response['access_token']))
		{
			cache('access_token', $response['access_token'], 2590000);
			return $response['access_token'];
		}
		return '';
	}
}
