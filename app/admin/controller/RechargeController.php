<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\BusinessRecharge;
use app\model\Device;

class RechargeController extends AuthController
{
	private $controller_name = '充值';
	
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
		$order_no = $params['order_no'] ?? NULL;
		$status = $params['status'] ?? NULL;
		$business_id = $params['business_id'] ?? NULL;
		$success_time = $params['success_time'] ?? NULL;
		
		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}
		
		$query = BusinessRecharge::field('*');
		
		if (!empty($order_no))
		{
			$query->where('order_no', $order_no);
		}
		if (!empty($status))
		{
			$query->where('status', $status);
		}
		if (!empty($business_id))
		{
			$query->where('business_id', $business_id);
		}
		if (!empty($success_time[0]) && $success_time[0] > 0)
		{
			$_begin_time = date('Y-m-d H:i:s', strtotime($success_time[0]) - 1);
			$query->where('success_time', '>', $_begin_time);
		}
		if (!empty($success_time[1]) && $success_time[1] > 0)
		{
			$_end_time = date('Y-m-d H:i:s', strtotime($success_time[1] . ' +1 second'));
			$query->where('success_time', '<', $_end_time);
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
			$tmp['business_id'] = $model->business_id;
			$tmp['order_no'] = $model->order_no;
			$tmp['account_name'] = $model->account_name;
			$tmp['account'] = $model->account;
			$tmp['account_sub'] = $model->account_sub;
			$tmp['post_amount'] = $model->post_amount;
			$tmp['pay_amount'] = $model->pay_amount;
			$tmp['usdt_amount'] = $model->usdt_amount;
			$tmp['ip'] = $model->ip;
			$tmp['create_time'] = $model->create_time;
			$tmp['success_time'] = $model->success_time;
			$tmp['remark'] = $model->remark;
			
			$tmp['status_str'] = isset(BusinessRecharge::STATUS[$model->status]) ? BusinessRecharge::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(BusinessRecharge::STATUS_CLASS[$model->status]) ? BusinessRecharge::STATUS_CLASS[$model->status] : '';
			
			$tmp['recharge_type_str'] = isset(BusinessRecharge::RECHARGE_TYPE[$model->recharge_type]) ? BusinessRecharge::RECHARGE_TYPE[$model->recharge_type] : '';
			$tmp['recharge_type_class'] = isset(BusinessRecharge::RECHARGE_TYPE_CLASS[$model->recharge_type]) ? BusinessRecharge::RECHARGE_TYPE_CLASS[$model->recharge_type] : '';
			
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
			'order_no' => '订单号',
			'recharge_type_str' => '充值通道',
			'account' => '充值账号',
			'create_time' => '创建时间',
			'success_time' => '支付时间',
			'pay_amount' => '实付金额',
			'usdt_amount' => 'Usdt金额',
			'status_str' => '状态',
		];
		
		Common::exportExcel($data['list'], $export_value);
	}
}