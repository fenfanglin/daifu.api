<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\model\BusinessRecharge;
use app\model\Device;

class RechargeController extends AuthController
{
	private $controller_name = '充值记录';
	
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
		$create_time = $params['create_time'] ?? NULL;
		
		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}
		
		$query = BusinessRecharge::field('*');
		
		$query->where('business_id', $this->user->id);
		
		if (!empty($order_no))
		{
			$query->where('order_no', $order_no);
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
			$tmp['order_no'] = $model->order_no;
			$tmp['business_id'] = $model->business_id;
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
			$tmp['status'] = (string)$model->status;
			
			$tmp['recharge_type_str'] = isset(BusinessRecharge::RECHARGE_TYPE[$model->recharge_type]) ? BusinessRecharge::RECHARGE_TYPE[$model->recharge_type] : '';
			$tmp['recharge_type_class'] = isset(BusinessRecharge::RECHARGE_TYPE_CLASS[$model->recharge_type]) ? BusinessRecharge::RECHARGE_TYPE_CLASS[$model->recharge_type] : '';
			$tmp['recharge_type'] = (string)$model->recharge_type;
			
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

		if (!$this->validate(input('post.'), $rule)) {
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = BusinessRecharge::where('no', $no)->find();
		if (!$model) {
			return $this->returnError('无法找到信息');
		}

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属代理');
			}
		}
		else
		{
			return $this->returnError('信息错误');
		}

		$tmp['info'] = json_decode($model->info, true);

		$info = [];
		
		$info[] = [
			'title' => '充值通道',
			'value' => isset(BusinessRecharge::RECHARGE_TYPE[$model->recharge_type]) ? BusinessRecharge::RECHARGE_TYPE[$model->recharge_type] : '',
		];
		
		if ($model->recharge_type == 1) //USDT
		{
			$info[] = [
				'title' => '钱包二维码',
				'value' => createQrcode($model->account),
				'check' => 'image',
			];
			$info[] = [
				'title' => 'Usdt钱包地址',
				'value' => $model->account,
			];
			$info[] = [
				'title' => '充值金额',
				'value' => $model->post_amount,
				'class' => 'text-warning bolder',
			];
			$info[] = [
				'title' => 'Usdt金额',
				'value' => $model->usdt_amount,
				'class' => 'text-success bolder',
			];
			$info[] = [
				'title' => 'Usdt汇率',
				'value' => $model->usdt_rate,
			];
		}
		// elseif ($model->account_type == 2) //支付宝
		// {
		// 	$info[] = [
		// 		'title' => '支付宝姓名',
		// 		'value' => $model->account_name,
		// 	];
		// 	$info[] = [
		// 		'title' => '支付宝账号',
		// 		'value' => $model->account,
		// 	];
		// 	$info[] = [
		// 		'title' => '交易金额',
		// 		'value' => $model->amount,
		// 		'class' => 'text-danger bolder',
		// 	];
		// }
		
		$info[] = [
			'title' => '下单时间',
			'value' => $model->create_time,
		];
		$info[] = [
			'title' => '支付时间',
			'value' => $model->success_time,
		];
		
		$info[] = [
			'title' => '状态',
			'value' => isset(BusinessRecharge::STATUS[$model->status]) ? BusinessRecharge::STATUS[$model->status] : '',
			'class' => isset(BusinessRecharge::STATUS_CLASS[$model->status]) ? BusinessRecharge::STATUS_CLASS[$model->status] : '',
		];

		$data = [];
		$data['no'] = $model->no;
		$data['status'] = $model->status;
		$data['info'] = $info;
		$data['row'] = $model;

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
	
	/**
	 * 列表
	 */
	public function get_setting()
	{
		$data = [
			'usdt_rate' => $this->setting->usdt_rate,
		];
		
		return $this->returnData($data);
	}
}