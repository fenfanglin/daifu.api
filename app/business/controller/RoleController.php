<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\extend\common\BaseRole;
use app\model\Role;

class RoleController extends AuthController
{
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
		
		$query = Role::field('*');
		
		$query->where('type', 2); //类型：1代理总权限 2管理员角色
		
		if ($user->center_id > 0)
		{
			$query->where('center_id', $user->center_id);
		}
		else
		{
			$query->where('center_id', 0);
		}
		
		if (!empty($keyword))
		{
			$query->where('name', 'like', '%' . $keyword . '%');
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
			$tmp['name'] = $model->name;
			
			$tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;
			
			$tmp['status_str'] = isset(Role::STATUS[$model->status]) ? Role::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Role::STATUS_CLASS[$model->status]) ? Role::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string)$model->status;
			
			$tmp['center_id'] = $model->center_id;
			$tmp['center_name'] = $model->center ? $model->center->name : '';
			
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
		
		$user = $this->getUser();
		
		$no = input('post.no');
		$model = Role::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}
		
		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['name'] = $model->name;
		$data['center_id'] = $model->center ? $model->center_id : '';
		$data['type'] = $model->type;
		$data['status'] = $model->status;
		
		$role = new BaseRole();
		
		// 总后台所有权限/代理后台权限
		if ($user->center_id == 0)
		{
			$center_permission = $role->getAllPermission();
		}
		else
		{
			$center_permission = Role::getCenterPermission($user->center_id);
		}
		
		$model->permission = $model->permission ? json_decode($model->permission, true) : [];
		$data['permission'] = $role->generateRoleMenu($center_permission, $model->permission);
		
		return $this->returnData($data);
	}
	
	/**
	 * 新增/修改
	 */
	public function save()
	{
		$rule = [
			'name|角色名称' => 'require|max:50',
			'permission|权限' => 'require',
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
			$model = Role::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
		}
		else
		{
			$model = new Role;
			$model->username = input('post.username');
		}
		
		$model->name = input('post.name');
		$model->permission = $this->createPermissionJson(input('post.permission'));
		$model->status = intval(input('post.status'));
		
		
		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}
		
		return $this->returnSuccess('保存成功');
	}
	
	/**
	 * 删除
	 */
	public function delete()
	{
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
				$model = Role::where('no', $no)->find();
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
		$rule = [
			'no' => 'require',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$no = input('post.no');
		$model = Role::where('no', $no)->find();
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
		$rule = [
			'no' => 'require',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$no = input('post.no');
		$model = Role::where('no', $no)->find();
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
	
	// /**
	//  * 列出选项
	//  */
	// public function list_option()
	// {
	// 	$user = $this->getUser();
		
	// 	$rule = [
	// 		'center_id' => 'integer',
	// 	];
		
	// 	if (!$this->validate(input('post.'), $rule))
	// 	{
	// 		return $this->returnError($this->getValidateError());
	// 	}
		
	// 	$center_id = intval(input('post.center_id'));
		
	// 	$query = Role::field('id, name');
		
	// 	$query->where('type', 2); //类型 1代理后台权限 2用户角色
		
	// 	if ($user->center_id > 0)
	// 	{
	// 		$query->where('center_id', $user->center_id);
	// 	}
	// 	else
	// 	{
	// 		$query->where('center_id', $center_id);
	// 	}
		
	// 	$list = $query->order('id asc')->select();
	// 	// var_dump($query->getLastSql());
	// 	// var_dump($query->buildSql(true));
		
	// 	$data = [];
		
	// 	$tmp = [];
	// 	$tmp['id'] = -1;
	// 	$tmp['name'] = $center_id > 0 ? '代理所有权限' : '系统所有权限';
		
	// 	$data[] = $tmp;
		
	// 	foreach ($list as $value)
	// 	{
	// 		$tmp = [];
	// 		$tmp['id'] = (int)$value['id'];
	// 		$tmp['name'] = $value['name'];
			
	// 		$data[] = $tmp;
	// 	}
		
	// 	return $this->returnData($data);
	// }
	
	public function get_center_full_permission()
	{
		return $this->returnData([]);
	}
	
	/**
	 * 获取菜单权限
	 * 	- 总部，获取全部权限
	 * 	- 代理，获取代理所有权限
	 */
	protected function createPermissionJson($_permission = [])
	{
		$permission = [];
		foreach ($_permission as $value)
		{
			if (!$value['check'])
			{
				continue;
			}
			
			$children = [];
			foreach ($value['children'] as $value2)
			{
				if (!$value2['check'])
				{
					continue;
				}
				
				$children2 = [];
				foreach ($value2['permission'] as $value3)
				{
					if (!$value3['check'])
					{
						continue;
					}
					
					$children2[$value3['id']] = [];
				}
				
				$children[$value2['id']] = $children2;
			}
			
			$permission[$value['id']] = $children;
		}
		
		return json_encode($permission, JSON_UNESCAPED_UNICODE);
	}
}