<?php
namespace app\admin\controller;

use app\extend\common\BaseController;
use app\extend\common\Common;
use app\model\Admin;
use app\model\BotGroup;
use app\model\BotOperator;
use app\model\BusinessChannel;
use app\model\DB2Order;
use app\model\Business;
use app\model\Channel;
use app\model\Order;
use app\service\BusinessService;

class BotOperatorController extends AuthController
{
	public $model;
	public function __construct()
	{
		$this->model = new BotOperator();
	}
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
		$keyword = $params['keyword'] ?? NULL;
		$status = intval($params['status'] ?? NULL);
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = $this->model::field('*');
		if (!empty($keyword))
		{
			$query->where('business_id|operator', 'like', '%' . $keyword . '%');
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
			$tmp['business_id'] = $model->business_id;
			$tmp['operator'] = $model->operator;
			$tmp['status'] = (string) $model->status;
			$tmp['create_time'] = $model->create_time;
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
		$model = $this->model::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}
		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['business_id'] = $model->business_id;
		$data['operator'] = $model->operator;
		$data['status'] = $model->status;
		return $this->returnData($data);
	}

	/**
	 * 新增/修改
	 */
	public function save()
	{
		$rule = [
			'business_id|商户ID' => 'require|max:50',
			'operator|TG用户名' => 'require|max:50',
			'status|状态' => 'require|integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		if ($no)
		{
			// 检查内部权限
//            $this->checkPermission('edit', true);
			$model = $this->model::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
			$where = [];
			$where[] = ['no', '<>', input('post.no')];
			$where[] = ['business_id', '=', input('post.business_id')];
			$where[] = ['operator', '=', input('post.operator')];
			$group_info = $this->model->where($where)->find();
			if ($group_info)
			{
				return $this->returnError('商户操作员已存在！');
			}
		}
		else
		{
			//            $this->checkPermission('add', true);
			$model = $this->model;
			$where = [];
			$where[] = ['business_id', '=', input('post.business_id')];
			$where[] = ['operator', '=', input('post.operator')];
			$group_info = $this->model->where($where)->find();
			if ($group_info)
			{
				return $this->returnError('商户操作员已存在！');
			}
		}

		$model->business_id = input('post.business_id');
		$model->operator = input('post.operator');
		$model->status = intval(input('post.status'));
		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}
		return $this->returnSuccess('保存成功');
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
		$model = $this->model::where('no', $no)->find();
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
		$model = $this->model::where('no', $no)->find();
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
		\think\facade\Db::startTrans();
		try
		{

			foreach ($ids as $no)
			{
				$model = $this->model::where('no', $no)->find();
				if (!$model)
				{
					throw new \Exception('无法找到信息');
				}
				$model->delete();
			}

			\think\facade\Db::commit();

			return $this->returnSuccess('ok');

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			return $this->returnError($e->getMessage());

		}
	}
}
