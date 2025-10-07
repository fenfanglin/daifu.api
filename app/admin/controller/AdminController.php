<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\Admin;

class AdminController extends AuthController
{
	private $controller_name = '管理员';
	
	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'status|状态' => 'integer',
		];
		
		if (!$this->validate($params, $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$user = $this->getUser();
		
		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$keyword = $params['keyword'] ?? NULL;
		$status = intval($params['status'] ?? NULL);
		$create_time = $params['create_time'] ?? NULL;
		
		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}
		
		$query = Admin::field('*');
		
		if ($user->center_id > 0)
		{
			$query->where('center_id', $user->center_id);
		}
		
		if (!empty($keyword))
		{
			$query->where('username|realname|phone', 'like', '%' . $keyword . '%');
		}
		if (!empty($status))
		{
			$query->where('status', $status);
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
		
		$query->order('id asc');
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
			$tmp['phone'] = $model->phone;
			$tmp['last_login_time'] = $model->last_login_time;
			$tmp['login_count'] = $model->login_count;
			$tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;
			$tmp['status_str'] = isset(Admin::STATUS[$model->status]) ? Admin::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Admin::STATUS_CLASS[$model->status]) ? Admin::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string)$model->status;
			
			$tmp['center_id'] = $model->center_id;
			$tmp['center_name'] = $model->center ? $model->center->name : '';
			
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
			'username' => '登录账号',
			'realname' => '账号名称',
			'phone' => '手机号',
			'role_name' => '角色组',
			'status_str' => '状态',
			'create_time' => '创建时间',
			
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
		$model = Admin::where('no', $no)->find();
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
		// $data['last_login_time'] = $model->last_login_time;
		// $data['login_count'] = $model->login_count;
		// $data['create_time'] = $model->create_time;
		// $data['update_time'] = $model->update_time;
		$data['status'] = $model->status;
		
		$data['center_id'] = $model->center ? $model->center_id : '';
		
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
	 */
	public function save()
	{
		$this->writeLog('保存' . $this->controller_name);
		
		$rule = [
			'realname|账号名称' => 'require|max:50',
			'phone|电话' => 'require|max:11|/^1[3-8]{1}[0-9]{9}$/',
			'role_id|角色' => 'require|integer',
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
			
			$model = Admin::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
		}
		else
		{
			$rule = [
				'username|登录账号' => 'require|alphaNum|max:50',
				'password|密码' => 'require|max:50',
			];
			
			if (!$this->validate(input('post.'), $rule))
			{
				return $this->returnError($this->getValidateError());
			}
			
			// 检查内部权限
			$this->checkPermission('add', true);
			
			$model = new Admin;
			$model->username = input('post.username');
		}
		
		$model->realname = input('post.realname');
		$model->phone = input('post.phone');
		$model->role_id = intval(input('post.role_id'));
		$model->status = intval(input('post.status'));
		
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
	 * 修改密码
	 */
	public function change_password()
	{
		$this->writeLog('修改密码' . $this->controller_name);
		
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
	 * 删除
	 */
	public function delete()
	{
		$this->writeLog('删除' . $this->controller_name);
		
		$rule = [
			'ids' => 'require',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$ids = input('post.ids');
		if (!is_array($ids))
		{
			$ids = [$ids];
		}
		
		$user = $this->getUser();
		
		\think\facade\Db::startTrans();
		try {
			
			foreach ($ids as $no)
			{
				$model = Admin::where('no', $no)->find();
				if (!$model)
				{
					throw new \Exception('无法找到信息');
				}
				
				if ($user->center_id > 0 && $model->center_id != $user->center_id)
				{
					throw new \Exception('安全拦截');
				}
				
				$model->delete();
			}
			
			\think\facade\Db::commit();
			
			return $this->returnSuccess('ok');
			
		} catch (\Exception $e) {
			
			\think\facade\Db::rollback();
			
			return $this->returnError($e->getMessage());
			
		}
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
		$model = Admin::where('no', $no)->find();
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
		$model = Admin::where('no', $no)->find();
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