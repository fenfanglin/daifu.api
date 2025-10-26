<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\common\BaseRole;
use app\model\Role;
use app\model\LoginLog;
use app\model\Order;

class IndexController extends AuthController
{
	/**
	 * 获取前端菜单
	 */
	public function get_router()
	{
		$role = new BaseRole();

		$all_permission = $role->getAllPermission();

		// $permission = $role->getAllPermission();
		// $routers = $role->getRouter($permission);

		// return $this->returnData($routers);

		$user = $this->getUser();

		// 总后台所有权限/代理后台权限
		if ($user->center_id == 0)
		{
			// 所有权限
			$center_permission = $all_permission;
		}
		else
		{
			$center_permission = Role::getCenterPermission($user->center_id);
		}

		// 账号权限
		if ($user->role_id == -1)
		{
			// 所有权限
			$user_permission = $all_permission;
		}
		else
		{
			$user_permission = Role::getUserPermission($user->role_id);
		}

		// 过滤账号权限
		$permission = $role->matchPermission($center_permission, $user_permission);


		// 所有
		// $permission = $all_permission;



		$data = $role->getRouter($permission);
		return $this->returnData($data);
	}

	public function index()
	{
		$key = 'admin_data_index';

		if ($data = $this->redis->get($key))
		{
			return $this->returnData($data);
		}


		$data = [];

		$log = LoginLog::where(['user_id' => $this->user->id, 'type' => 1])->order('id desc')->find();
		$data['log'] = $log ?? [];

		// --------------------------------------------------------------------------
		// 平台总入金
		$where = [];
		$where[] = ['status', 'in', [1, 2]];
		$data['info']['total_amount'] = Order::where($where)->sum('amount');

		// --------------------------------------------------------------------------
		// 今日平台总入金
		$where = [];
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
		$data['info']['today_amout'] = Order::where($where)->sum('amount');

		// --------------------------------------------------------------------------
		// 昨日平台总入金
		$where = [];
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
		$where[] = ['success_time', '<', date('Y-m-d')];
		$data['info']['yesterday_amout'] = Order::where($where)->sum('amount');

		// --------------------------------------------------------------------------
		// 今日平台总费用
		$where = [];
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
		$data['info']['today_fee'] = Order::where($where)->sum('system_fee');

		// --------------------------------------------------------------------------
		// 昨日平台总费用
		$where = [];
		$where[] = ['status', 'in', [1, 2]];
		$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
		$where[] = ['success_time', '<', date('Y-m-d')];
		$data['info']['yesterday_fee'] = Order::where($where)->sum('system_fee');

		$this->redis->set($key, $data, getDataCacheTime());

		return $this->returnData($data);
	}

	/**
	 * 获取账号信息
	 * 前端刷新加载一次
	 */
	public function get_userinfo()
	{
		$user = $this->getUser();

		$data = [];
		$data['name'] = $user->realname;
		$data['avatar'] = 'https://jqkpay.oss-cn-hongkong.aliyuncs.com/static/images/avatar.png';
		$data['is_google_auth'] = $user->is_google_auth;
		$data['role'] = config('app.app_name');
		$data['permissions'] = $this->permission;

		return $this->returnData($data);
	}

	/**
	 * 获取账号信息
	 * 前端获取当下用户信息
	 */
	public function userinfo()
	{
		$user = $this->user;

		$data = [];
		$data['name'] = $user->realname;
		$data['avatar'] = 'https://jqkpay.oss-cn-hongkong.aliyuncs.com/static/images/avatar.png';
		$data['is_google_auth'] = $user->is_google_auth;

		return $this->returnData($data);
	}

	/**
	 * 系统提醒信息
	 */
	public function get_notice()
	{
		$data = [];


		return $this->returnData($data);
	}

	/**
	 * 生成签名
	 */
	public function get_sign()
	{
		$id = config('oss.accessKeyId');          // 请填写您的AccessKeyId。
		$key = config('oss.accessKeySecret');          // 请填写您的AccessKeyId。
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
}
