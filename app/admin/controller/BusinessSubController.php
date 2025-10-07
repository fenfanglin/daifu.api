<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\auth\CrossDomainAuth;
use app\model\Business;
use app\model\BusinessMoneyLog;
use app\model\BusinessRecharge;

class BusinessSubController extends AuthController
{
	private $controller_name = '四方商户';

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

		$query->where('type', 3);         //类型：1代理 2工作室 3商户

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
			$tmp['allow_withdraw'] = $model->allow_withdraw;
			$tmp['last_login_time'] = $model->last_login_time;
			$tmp['login_count'] = $model->login_count;
			$tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;
			$tmp['parent_name'] = $model->parent ? $model->parent->username : '';

			$tmp['status_str'] = isset(Business::STATUS[$model->status]) ? Business::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Business::STATUS_CLASS[$model->status]) ? Business::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string) $model->status;

			$tmp['verify_status_str'] = isset(Business::VERIFY_STATUS[$model->verify_status]) ? Business::VERIFY_STATUS[$model->verify_status] : '';
			$tmp['verify_status_class'] = isset(Business::VERIFY_STATUS_CLASS[$model->verify_status]) ? Business::VERIFY_STATUS_CLASS[$model->verify_status] : '';
			$tmp['verify_status'] = (string) $model->verify_status;
			$tmp['order_rate'] = (string) $model->order_rate;

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
		$data['order_rate'] = (string) $model->order_rate;

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
			'order_rate|费率' => 'require|float|gt:0',
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
			$model->type = 3; //类型：1代理 2工作室 3商户
		}

		$model->realname = input('post.realname');
		$model->remark = input('post.remark');
		// $model->phone = input('post.phone');
		// $model->role_id = intval(input('post.role_id'));
		$model->google_secret_key = input('post.google_secret_key');
		// $model->login_ip = input('post.login_ip');
		$model->status = intval(input('post.status'));
		$model->order_rate = input('post.order_rate');

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
