<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\Channel;

class ChannelController extends AuthController
{
	private $controller_name = '支付通道';

	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{	
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'type|通道类型' => 'integer',
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
		$type = intval($params['type'] ?? NULL);
		$status = intval($params['status'] ?? NULL);
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = Channel::field('*');
		// return '11';

		if ($user->center_id > 0)
		{
			$query->where('center_id', $user->center_id);
		}

		if (!empty($keyword))
		{
			$query->where('id|name', 'like', '%' . $keyword . '%');
		}
		if (!empty($type))
		{
			$query->where('type', $type);
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
			$tmp['code'] = $model->code;
			$tmp['name'] = $model->name;
			$tmp['rate'] = $model->rate;
			$tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;

			$tmp['status_str'] = isset(Channel::STATUS[$model->status]) ? Channel::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Channel::STATUS_CLASS[$model->status]) ? Channel::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string) $model->status;

			$tmp['type_str'] = isset(Channel::TYPE[$model->type]) ? Channel::TYPE[$model->type] : '';
			$tmp['type_class'] = isset(Channel::TYPE_CLASS[$model->type]) ? Channel::TYPE_CLASS[$model->type] : '';
			$tmp['type'] = (string) $model->type;

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
			'id' => '通道ID',
			'name' => '通道名称',
			'code' => '通道代码',
			'type_str' => '通道类型',
			'rate' => '通道费率',
			'status_str' => '通道状态',
			'update_time' => '更新时间',
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
		$model = Channel::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['name'] = $model->name;
		$data['code'] = $model->code;
		$data['rate'] = $model->rate;
		$data['type'] = $model->type;
		$data['status'] = $model->status;
		$data['block_order'] = $model->block_order;
		$data['refund_order'] = $model->refund_order;
		$data['show_refund'] = -1;

		$channel_ids = [112, 114, 162];
		if (in_array($model->id, $channel_ids))
		{
			$data['show_refund'] = 1;
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
			'name|通道名称' => 'require|max:20',
			'code|通道代码' => 'require|max:50',
			// 'rate|通道费率' => 'require|float|between:0.001,0.099',
			'type|通道类型' => 'require|integer',
			'status|通道状态' => 'require|integer',
			// 'block_order|拦截订单' => 'require|integer',
// 			'refund_order|自动退款' => 'require|integer',
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

			$model = Channel::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
		}
		else
		{
			// 检查内部权限
			$this->checkPermission('add', true);

			$model = new Channel;
		}

		$model->name = input('post.name');
		$model->code = input('post.code');
		$model->rate = input('post.rate');
		$model->type = input('post.type');
		$model->status = intval(input('post.status'));
		$model->block_order = intval(input('post.block_order'));
		$model->refund_order = intval(input('post.refund_order'));

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		return $this->returnSuccess('保存成功');
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
	// 			$model = Channel::where('no', $no)->find();
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
		$model = Channel::where('no', $no)->find();
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
		$model = Channel::where('no', $no)->find();
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