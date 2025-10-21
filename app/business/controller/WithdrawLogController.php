<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\model\BusinessWithdrawLog;

class WithdrawLogController extends AuthController
{
	private $controller_name = '可提现资金明细';

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
		$keyword = $params['keyword'] ?? NULL;
		$status = $params['status'] ?? NULL;
		$type = $params['type'] ?? NULL;
		$money = $params['money'] ?? NULL;
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = BusinessWithdrawLog::field('*');

		$query->where('status', '>', 0);

		// 类型：1商户 2卡商 3四方 4四方商户
		if (in_array($this->user->type, [1]))
		{
			$query->where('business_id', $this->user->id);
		}
		elseif (in_array($this->user->type, [3]))
		{
			$query->where('sub_business_id', $this->user->id);
		}
		else
		{
			$query->where('id', 0); //不显示
		}


		if (!empty($keyword))
		{
			$query->where('remark', 'like', '%' . $keyword . '%');
		}
		if (!empty($type))
		{
			$query->where('type', $type);
		}
		if (!empty($money))
		{
			$query->where('money', $money);
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
		$where = [];
		$where[] = ['sub_business_id', '=', $this->user->id];
		$where[] = ['status', '>', 0];
		if (!empty($create_time[0]) && $create_time[0] > 0)
		{
			$_begin_time = date('Y-m-d H:i:s', strtotime($create_time[0]) - 1);
			// $query->where('create_time', '>', $_begin_time);
			$where[] = ['create_time', '>', $_begin_time];
		}
		if (!empty($create_time[1]) && $create_time[1] > 0)
		{
			$_end_time = date('Y-m-d H:i:s', strtotime($create_time[1] . ' +1 second'));
			// $query->where('create_time', '<', $_end_time);
			$where[] = ['create_time', '<', $_end_time];
		}

		$_where = $where;
		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			$tmp['id'] = $model->id;
			$tmp['sub_business_id'] = $model->sub_business_id;
			$tmp['out_trade_no'] = in_array($model->type, [1, 2]) && $model->order ? $model->order->out_trade_no : '';
			$tmp['money'] = $model->money >= 0 ? '+' . $model->money : $model->money;
			$tmp['money_before'] = $model->money_before;
			$tmp['money_after'] = $model->money_after;
			$tmp['remark'] = $model->remark;
			$tmp['create_time'] = $model->create_time;

			$tmp['type_str'] = isset(BusinessWithdrawLog::TYPE[$model->type]) ? BusinessWithdrawLog::TYPE[$model->type] : '';
			$tmp['type_class'] = isset(BusinessWithdrawLog::TYPE_CLASS[$model->type]) ? BusinessWithdrawLog::TYPE_CLASS[$model->type] : '';


			$data['list'][] = $tmp;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}

		$data['total'] = $query->count();
		if (!$is_export)
		{
			$business_id = $this->user->id;
			$url = 'money_log/list';
			$name = '资金明细';
			$num = 1;
			$num_sql = 2;
			$use_cache = 0;

			$data['info'] = [];

			// 总费用
			$data['info']['total_fee'] = BusinessWithdrawLog::where($_where)->where('type', '=', 1)->sum('money');
			// $data['info']['total_fee_sql'] = var_dump(BusinessWithdrawLog::getLastSql());
			// $data['info']['total_fee'] = abs($data['info']['total_fee']);

			// 交易笔数
			$data['info']['total_order'] = BusinessWithdrawLog::where($_where)->where('type', '=', 1)->count('id');
			// $data['info']['total_order_sql'] = var_dump(BusinessWithdrawLog::getLastSql());

		}
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
		$this->writeLog($this->controller_name . '导出');

		$params = input('post.');
		$params['is_export'] = 1;

		$data = $this->_search($params, $is_export = 1);

		$export_value = [
			'type_str' => '类型',
			'out_trade_no' => '商户订单号',
			'money' => '发生金额',
			'money_before' => '发生前金额',
			'money_after' => '发生后金额',
			'create_time' => '时间',
			'remark' => '备注',

		];

		Common::exportExcel($data['list'], $export_value);
	}
}