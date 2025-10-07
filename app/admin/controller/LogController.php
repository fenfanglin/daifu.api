<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\AdminLog;
use app\model\Admin;

class LogController extends AuthController
{
	private $controller_name = '操作记录';
	
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
		
		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$username = $params['username'] ?? NULL;
		$keyword = $params['keyword'] ?? NULL;
		$url = $params['url'] ?? NULL;
		$ip = $params['ip'] ?? NULL;
		$create_time = $params['create_time'] ?? NULL;
		
		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}
		
		$query = AdminLog::field('*');
		
		if (!empty($username))
		{
			$admin = Admin::where('username', $username)->find();
			if ($admin)
			{
				$query->where('admin_id', $admin->id);
			}
			else
			{
				$query->where('id', 0); //没有匹配的后台管理员账号就不输出结果
			}
			
		}
		
		if (!empty($keyword))
		{
			$query->where('params', 'like', '%' . $keyword . '%');
		}
		if (!empty($url))
		{
			$query->where('url', $url);
		}
		if (!empty($ip))
		{
			$query->where('ip', $ip);
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
			$tmp['ip'] = $model->ip;
			$tmp['url'] = $model->url;
			$tmp['params'] = $model->params;
			$tmp['params_code'] = json_encode(json_decode($model->params, true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
			$tmp['create_time'] = $model->create_time;
			
			$tmp['admin_id'] = $model->admin_id;
			$tmp['admin_username'] = $model->admin ? $model->admin->username : '';
			
			
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
}