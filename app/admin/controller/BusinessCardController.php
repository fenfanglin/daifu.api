<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\auth\CrossDomainAuth;
use app\model\Business;
use app\model\BusinessMoneyLog;
use app\model\BusinessChannel;
use app\model\Channel;
use app\model\BusinessRecharge;

class BusinessCardController extends AuthController
{
	private $controller_name = '卡商';

	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'verify_status|认证' => 'integer',
			'status|状态' => 'integer',
		];

		if (!$this->validate($params, $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$user = $this->getUser();

		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$id = intval($params['id'] ?? NULL);
		$keyword = $params['keyword'] ?? NULL;
		$verify_status = intval($params['verify_status'] ?? NULL);
		$status = intval($params['status'] ?? NULL);
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = Business::field('*');

		$query->where('type', 2);        //类型：1代理 2工作室 3商户

		if ($user->center_id > 0)
		{
			$query->where('center_id', $user->center_id);
		}

		if (!empty($keyword))
		{
			$query->where('username|realname', 'like', '%' . $keyword . '%');
		}
		if (!empty($id))
		{
			$query->where('id', $id);
		}
		if (!empty($status))
		{
			$query->where('status', $status);
		}
		if (!empty($verify_status))
		{
			$query->where('verify_status', $verify_status);
		}
		if (!empty($create_time[0]) && $create_time[0] > 0)
		{
			$_begin_time = date('Y-m-d H:i:s', strtotime($create_time[0]) - 1);
			$query->where('create_time', '>', $_begin_time);
		}
		if (!empty($create_time[1]) && $create_time[1] > 0)
		{
			$_end_time = date('Y-m-d H:i:s', strtotime($create_time[1] . ' +1 second'));
			$query->where('create_time', '<', $_end_time);
		}

		$query->order('id desc');
		// var_dump($query->getLastSql());
		// var_dump($query->buildSql(true));

		$list = $query->paginate([
			'list_rows' => $limit,
			'page' => $page,
		]);

		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			$tmp['id'] = $model->id;
			$tmp['no'] = $model->no;
			$tmp['username'] = $model->username;
			$tmp['realname'] = $model->realname;
			$tmp['remark'] = $model->remark;
			$tmp['phone'] = $model->phone;
			$tmp['money'] = $model->money;
			$tmp['last_login_time'] = $model->last_login_time;
			$tmp['login_count'] = $model->login_count;
			$tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;

			if ($model->parent && $model->parent->type == 1)
			{
				$tmp['parent_name'] = '商户：' . $model->parent ? $model->parent->username : '';
			}
			elseif ($model->parent)
			{
				$tmp['parent_name'] = '四方：' . $model->parent ? $model->parent->username : '';
			}

			$tmp['status_str'] = isset(Business::STATUS[$model->status]) ? Business::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Business::STATUS_CLASS[$model->status]) ? Business::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string) $model->status;

			$tmp['verify_status_str'] = isset(Business::VERIFY_STATUS[$model->verify_status]) ? Business::VERIFY_STATUS[$model->verify_status] : '';
			$tmp['verify_status_class'] = isset(Business::VERIFY_STATUS_CLASS[$model->verify_status]) ? Business::VERIFY_STATUS_CLASS[$model->verify_status] : '';
			$tmp['verify_status'] = (string) $model->verify_status;

			$tmp['role_id'] = $model->role_id;
			if ($model->role)
			{
				$tmp['role_name'] = $model->role->name;
			}
			elseif ($model->role_id == -1)
			{
				$tmp['role_name'] = $model->center_id ? '代理所有权限' : '系统所有权限';
			}
			else
			{
				$tmp['role_name'] = '';
			}

			$data['list'][] = $tmp;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}

		$data['total'] = $query->count();

		return $data;
	}


	/**
	 * 列表
	 */
	public function list()
	{
		$params = input('post.');

		$data = $this->_search($params);

		return $this->returnData($data);
	}

	/**
	 * 导出
	 */
	public function export()
	{
		$this->writeLog('导出' . $this->controller_name);

		$params = input('post.');
		$params['is_export'] = 1;

		$data = $this->_search($params, $is_export = 1);

		$export_value = [
			'id' => '商户ID',
			'username' => '商户账号',
			'realname' => '商户名称',
			'money' => '余额',
			'verify_status_str' => '认证',
			'status_str' => '状态',
			'create_time' => '注册时间',

		];

		Common::exportExcel($data['list'], $export_value);
	}

	/**
	 * 查看
	 */
	public function view()
	{
		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['username'] = $model->username;
		$data['realname'] = $model->realname;
		$data['phone'] = $model->phone;
		$data['remark'] = $model->remark;
		$data['money'] = $model->money;
		$data['google_secret_key'] = $model->google_secret_key;
		$data['login_ip'] = $model->login_ip;
		// $data['last_login_time'] = $model->last_login_time;
		// $data['login_count'] = $model->login_count;
		// $data['create_time'] = $model->create_time;
		// $data['update_time'] = $model->update_time;
		$data['status'] = $model->status;
		$data['verify_status'] = $model->verify_status;

		$data['role_id'] = $model->role_id;
		if ($model->role)
		{
			$data['role_id'] = $model->role_id;
		}
		elseif ($model->role_id == -1)
		{
			$data['role_id'] = -1; //所有权限
		}
		else
		{
			$data['role_id'] = '';
		}

		return $this->returnData($data);
	}

	/**
	 * 新增/修改
	 * 后台管理员编辑商户，添加默认是已认证
	 */
	public function save()
	{
		$this->writeLog('保存' . $this->controller_name);

		$rule = [
			// 'realname|商户名称' => 'require|max:50',
			// 'phone|电话' => 'require|max:11|/^1[3-8]{1}[0-9]{9}$/',
			// 'role_id|角色' => 'require|integer',
			// 'verify_status|认证' => 'require|integer',
			'status|状态' => 'require|integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$user = $this->getUser();

		$no = input('post.no');
		if ($no)
		{
			// 检查内部权限
			$this->checkPermission('edit', true);

			$model = Business::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
		}
		else
		{
			$rule = [
				'username|商户账号' => 'require|alphaNum|max:20',
				'password|密码' => 'require|min:6|max:50',
			];

			if (!$this->validate(input('post.'), $rule))
			{
				return $this->returnError($this->getValidateError());
			}

			// 检查内部权限
			$this->checkPermission('add', true);

			$model = new Business;
			$model->username = input('post.username');
			$model->verify_status = 1;
			$model->type = 2; //类型：1代理 2工作室 3商户
		}

		$model->realname = input('post.realname');
		$model->remark = input('post.remark');
		// $model->phone = input('post.phone');
		// $model->role_id = intval(input('post.role_id'));
		$model->google_secret_key = input('post.google_secret_key');
		// $model->login_ip = input('post.login_ip');
		$model->status = intval(input('post.status'));

		if ($password = input('post.password'))
		{
			$model->auth_key = Common::randomStr(6);
			$model->password = Common::generatePassword($password, $model->auth_key, 'business');
		}

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		return $this->returnSuccess('保存成功');
	}

	// /**
	//  * 充值
	//  */
	// public function save_recharge()
	// {
	// 	$this->writeLog($this->controller_name . '充值');

	// 	$rule = [
	// 		'no' => 'require|max:50',
	// 		'recharge_type|操作' => 'require|in:1,-1',
	// 		'amount|金额' => 'require|float|>:0',
	// 	];

	// 	if (!$this->validate(input('post.'), $rule))
	// 	{
	// 		return $this->returnError($this->getValidateError());
	// 	}

	// 	$no = input('post.no');
	// 	$business = Business::where('no', $no)->find();
	// 	if (!$business)
	// 	{
	// 		return $this->returnError('无法找到信息');
	// 	}

	// 	$user = $this->getUser();

	// 	$amount = input('post.amount');
	// 	$recharge_type = input('post.recharge_type');
	// 	$remark = "[{$user->id}] 总后台操作";
	// 	if (input('post.remark'))
	// 	{
	// 		$remark .= ': ' . input('post.remark');
	// 	}

	// 	if ($recharge_type == -1)
	// 	{
	// 		$amount = -$amount;
	// 	}

	// 	$recharge = new BusinessRecharge();
	// 	$recharge->business_id			= $business->id;
	// 	$recharge->recharge_type		= -1; //充值方式：-1后台充值 1Usdt
	// 	$recharge->account_name			= '';
	// 	$recharge->account				= '';
	// 	$recharge->account_sub			= '';
	// 	$recharge->post_amount			= $amount;
	// 	$recharge->pay_amount			= $amount;
	// 	// $recharge->usdt_rate			= 0;
	// 	// $recharge->usdt_amount			= 0;
	// 	$recharge->ip					= Common::getClientIp();
	// 	$recharge->remark				= $remark;
	// 	// $recharge->expire_time			= $expire_time;
	// 	$recharge->success_time			= date('Y-m-d H:i:s');
	// 	$recharge->status				= 1; //状态：-1未支付 1成功 -2生成订单失败

	// 	if (!$recharge->save())
	// 	{
	// 		$this->returnError('生成订单失败');
	// 	}

	// 	// 更新商户余额
	// 	$res = \app\service\BusinessService::changeMoney($business->id, $amount, $type = 3, $user->id, $remark);

	// 	if ($res['code'] != 1)
	// 	{
	// 		return $this->returnError($res['msg']);
	// 	}
	// 	else
	// 	{
	// 		return $this->returnSuccess('保存成功');
	// 	}
	// }

	/**
	 * 修改密码
	 */
	public function change_password()
	{
		$this->writeLog($this->controller_name . '修改密码');

		$rule = [
			'no' => 'require|max:50',
			'password|密码' => 'require|min:6|max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if ($password = input('post.password'))
		{
			$model->auth_key = Common::randomStr(6);
			$model->password = Common::generatePassword($password, $model->auth_key);
		}

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 查看通道
	 */
	public function view_channel()
	{
		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$business = Business::where('no', $no)->find();
		if (!$business)
		{
			return $this->returnError('无法找到信息');
		}

		// 1、获取有效通道
		// 2、每个有效通道，获取商户通道信息（如果没有就新增，默认是关闭状态，费率0）

		$list_channel = Channel::field('*')->where('status', 1)->order('sort asc')->select();

		$data = [];
		foreach ($list_channel as $channel)
		{
			$parent_business_channel = BusinessChannel::where(['business_id' => $business->parent_id, 'channel_id' => $channel->id])->find();
			if (!$parent_business_channel || $parent_business_channel->status != 1)
			{
				continue;
			}

			$business_channel = BusinessChannel::where(['business_id' => $business->id, 'channel_id' => $channel->id])->find();
			if (!$business_channel)
			{
				$business_channel = new BusinessChannel;
				$business_channel->business_id = $business->id;
				$business_channel->channel_id = $channel->id;
				$business_channel->rate = $channel->rate * 10;
				$business_channel->timeout = $channel->timeout;
				$business_channel->status = -1;

				// 回调提交金额
				if (in_array($channel->id, [102, 125])) //Usdt收款，聚合码转卡
				{
					$business_channel->notify_amount = 2;
				}

				$business_channel->save();

				$business_channel->rate = number_format($business_channel->rate, 3, '.', '');
			}

			$tmp = [];
			$tmp['no'] = $business_channel['no'];
			$tmp['channel_name'] = $channel['name'];
			$tmp['rate'] = $business_channel['rate'];
			$tmp['status'] = (string) $business_channel['status'];

			$data[] = $tmp;
		}

		return $this->returnData($data);
	}

	/**
	 * 通道保存
	 */
	public function save_channel()
	{
		$this->writeLog($this->controller_name . '通道保存');

		\think\facade\Db::startTrans();
		try
		{

			$post = input('post.');
			foreach ($post as $param)
			{
				$rule = [
					'no' => 'require|max:50',
					'status|状态' => 'require|in:1,-1',
					'rate|费率' => 'require|float|between:0.001,0.010',
				];

				if (!$this->validate($param, $rule))
				{
					throw new \Exception($this->getValidateError());
				}

				$no = $param['no'];
				$business_channel = BusinessChannel::where('no', $no)->find();
				if (!$business_channel)
				{
					throw new \Exception('无法找到信息');
				}

				$business_channel->status = $param['status'];
				$business_channel->rate = $param['rate'];
				if (!$business_channel->save())
				{
					throw new \Exception('保存失败');
				}
			}

			\think\facade\Db::commit();

			return $this->returnSuccess('保存成功');

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			return $this->returnError($e->getMessage());

		}
	}

	/**
	 * 登录商户后台
	 */
	public function login()
	{
		$this->writeLog('登录四方后台');

		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$business = Business::where('no', $no)->find();
		if (!$business)
		{
			return $this->returnError('无法找到信息');
		}

		$user = $this->getUser();

		$auth = new CrossDomainAuth;

		$res = $auth->generate($user->no, $business->no);

		$params = [
			'token' => Common::randomStr(64),
			'cache' => $res['token'],
			'data' => $res['key'],
			'key' => Common::randomStr(16),
			'sign' => md5(Common::randomStr(6)),
			'timestamp' => time(),
		];

		$data = [
			'params' => http_build_query($params),
		];

		return $this->returnData($data);
	}

	// /**
	//  * 删除
	//  */
	// public function delete()
	// {
	// 	$rule = [
	// 		'ids' => 'require',
	// 	];

	// 	if (!$this->validate(input('post.'), $rule))
	// 	{
	// 		return $this->returnError($this->getValidateError());
	// 	}

	// 	$ids = input('post.ids');
	// 	if (!is_array($ids))
	// 	{
	// 		$ids = [$ids];
	// 	}

	// 	$user = $this->getUser();

	// 	\think\facade\Db::startTrans();
	// 	try {

	// 		foreach ($ids as $no)
	// 		{
	// 			$model = Business::where('no', $no)->find();
	// 			if (!$model)
	// 			{
	// 				throw new \Exception('无法找到信息');
	// 			}

	// 			if ($user->center_id > 0 && $model->center_id != $user->center_id)
	// 			{
	// 				throw new \Exception('安全拦截');
	// 			}

	// 			$model->delete();
	// 		}

	// 		\think\facade\Db::commit();

	// 		return $this->returnSuccess('ok');

	// 	} catch (\Exception $e) {

	// 		\think\facade\Db::rollback();

	// 		return $this->returnError($e->getMessage());

	// 	}
	// }

	/**
	 * 启用
	 */
	public function enable()
	{
		$this->writeLog('启用' . $this->controller_name);

		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$model->status = 1;

		if (!$model->save())
		{
			return $this->returnError('失败');
		}

		return $this->returnSuccess('成功');
	}

	/**
	 * 禁用
	 */
	public function disable()
	{
		$this->writeLog('禁用' . $this->controller_name);

		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$model->status = -1;

		if (!$model->save())
		{
			return $this->returnError('失败');
		}

		return $this->returnSuccess('成功');
	}
}
