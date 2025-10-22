<?php

namespace app\admin\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\Order;

class OrderController extends AuthController
{
	private $controller_name = '订单';

	protected function _create_where()
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'status|状态' => 'integer',
			'out_trade_no|商户订单号' => 'alphaNum',
			'card_business_id|卡商' => 'integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			Common::error($this->getValidateError());
		}

		$page = intval(input('post.page') ?? 1);
		$limit = intval(input('post.limit') ?? 10);
		$min_amount = floatval(input('post.min_amount'));
		$max_amount = floatval(input('post.max_amount'));
		$status = intval(input('post.status') ?? NULL);
		$out_trade_no = input('post.out_trade_no') ?? NULL;
		$account = input('post.account') ?? NULL;
		$remark = input('post.remark') ?? NULL;
		$business_id = intval(input('post.business_id') ?? NULL);
		$success_time = input('post.success_time') ?? NULL;
		$create_time = input('post.create_time') ?? NULL;
		$account_type = input('post.account_type') ?? NULL;


		$where = [];

		// if (!empty($keyword))
		// {
		// 	$query->where('username|realname', 'like', '%' . $keyword . '%');
		// }
		if (!empty($out_trade_no))
		{
			$where[] = ['out_trade_no', '=', $out_trade_no];
		}
		if (!empty($account))
		{
			$where[] = ['account', '=', $account];
		}
		if (!empty($business_id))
		{
			$where[] = ['business_id', '=', $business_id];
		}

		if (!empty($status))
		{
			$where[] = ['status', '=', $status];
		}
		if (!empty($success_time[0]) && $success_time[0] > 0)
		{
			$_begin_time_success = date('Y-m-d H:i:s', strtotime($success_time[0]) - 1);
			$where[] = ['success_time', '>', $_begin_time_success];
		}
		if (!empty($success_time[1]) && $success_time[1] > 0)
		{
			$_end_time_success = date('Y-m-d H:i:s', strtotime($success_time[1] . ' +1 second'));
			$where[] = ['success_time', '<', $_end_time_success];
		}
		if (!empty($create_time[0]) && $create_time[0] > 0)
		{
			$_begin_time_create = date('Y-m-d H:i:s', strtotime($create_time[0]) - 1);
			$where[] = ['create_time', '>', $_begin_time_create];
		}
		if (!empty($create_time[1]) && $create_time[1] > 0)
		{
			$_end_time_create = date('Y-m-d H:i:s', strtotime($create_time[1] . ' +1 second'));
			$where[] = ['create_time', '<', $_end_time_create];
		}
		if (!empty($min_amount))
		{
			$where[] = ['amount', '>=', $min_amount];
		}
		if (!empty($max_amount))
		{
			$where[] = ['amount', '<=', $max_amount];
		}
		if (!empty($remark))
		{
			$where[] = ['remark', '=', $remark];
		}
		if (!empty($account_type))
		{
			$where[] = ['account_type', '=', $account_type];
		}

		return $where;
	}

	/**
	 * 列表
	 */
	protected function _search($is_export = 0)
	{
		$page = intval(input('post.page') ?? 1);
		$limit = intval(input('post.limit') ?? 10);
		$success_time = input('post.success_time') ?? NULL;
		$create_time = input('post.create_time') ?? NULL;

		if (!empty($success_time[0]) && $success_time[0] > 0)
		{
			$_begin_time_success = date('Y-m-d H:i:s', strtotime($success_time[0]));
		}
		if (!empty($success_time[1]) && $success_time[1] > 0)
		{
			$_end_time_success = date('Y-m-d H:i:s', strtotime($success_time[1]));
		}
		if (!empty($create_time[0]) && $create_time[0] > 0)
		{
			$_begin_time_create = date('Y-m-d H:i:s', strtotime($create_time[0]));
		}
		if (!empty($create_time[1]) && $create_time[1] > 0)
		{
			$_end_time_create = date('Y-m-d H:i:s', strtotime($create_time[1]));
		}

		$where = $this->_create_where();

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = Order::field('*');

		$query->where($where)->order('id desc');

		$list = $query->paginate([
			'list_rows' => $limit,
			'page' => $page,
		]);

		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			$tmp['no'] = $model->no;
			$tmp['order_no'] = $model->order_no;
			$tmp['out_trade_no'] = $model->out_trade_no;
			$tmp['account_title'] = $model->account_title;
			$tmp['account_name'] = $model->account_name;
			$tmp['account'] = $model->account;
			$tmp['account_sub'] = $model->account_sub;
			$tmp['business_id'] = $model->business_id;
			$tmp['business_realname'] = $model->business->realname ?? '';
			$tmp['amount'] = $model->amount;
			$tmp['usdt_amount'] = $model->usdt_amount;
			$tmp['system_fee'] = $model->system_fee + $model->commission;//费率+固定费用
			$tmp['ip'] = $model->ip;
			$tmp['create_time'] = $model->create_time;
			$tmp['success_time'] = $model->success_time;
			$tmp['remark'] = $model->remark;
			$tmp['bank'] = $model->bank;
			$tmp['branch'] = $model->branch;
			$tmp['account_type'] = $model->account_type;
			$tmp['sub_business_id'] = $model->sub_business_id;
			$tmp['sub_business_realname'] = $model->subBusiness->realname ?? '';
			$tmp['card_business_id'] = $model->card_business_id;
			// $tmp['card_business_id'] = $model->card_business_id;
			$tmp['card_business_realname'] = $model->cardBusiness ? $model->cardBusiness->realname : '';

			$tmp['status_str'] = isset(Order::STATUS[$model->status]) ? Order::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Order::STATUS_CLASS[$model->status]) ? Order::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string) $model->status;

			$data['list'][] = $tmp;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}

		$data['total'] = $query->count();

		if (!$is_export)
		{
			$sign = md5(json_encode($where, JSON_UNESCAPED_UNICODE));
			$key = "admin_data_order_{$sign}";

			if ($_data = $this->redis->get($key) && 0)
			{
				$data['info'] = $_data;
			}
			else
			{
				if (isset($_begin_time_success) || isset($_begin_time_create))
				{
					$data['info']['show_search'] = true;

					// 交易总额
					$data['info']['success_amount'] = Order::where($where)->where('status', '>', 0)->sum('amount');

					// 交易笔数
					$data['info']['success_order'] = Order::where($where)->where('status', '>', 0)->count('id');

					// 交易笔数
					$data['info']['total_order'] = Order::where($where)->count('id');

					// 成功率
					if (!$data['info']['total_order'])
					{
						$data['info']['success_rate'] = 0;
					}
					else
					{
						$data['info']['success_rate'] = round($data['info']['success_order'] / ($data['info']['total_order']) * 100, 2);
					}
				}


				$where = [];
				$where[] = ['status', 'in', [1, 2]];
				$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
				if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
				{
					$where[] = ['business_id', '=', $this->user->id];
				}
				elseif (in_array($this->user->type, [2]))
				{
					$where[] = ['card_business_id', '=', $this->user->id];
				}
				elseif (in_array($this->user->type, [3]))
				{
					$where[] = ['sub_business_id', '=', $this->user->id];
				}
				else
				{
					$where[] = ['id', '=', 0]; //不显示
				}

				if (!empty($card_business_id))
				{
					$where[] = ['card_business_id', '=', $card_business_id];
				}

				// 今日交易总额
				$data['info']['today_success_amount'] = Order::where($where)->sum('amount');

				// 今日交易笔数
				$data['info']['today_success_order'] = Order::where($where)->count('id');

				// 今日总笔数
				$where = [];
				$where[] = ['create_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
				if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
				{
					$where[] = ['business_id', '=', $this->user->id];
				}
				elseif (in_array($this->user->type, [2]))
				{
					$where[] = ['card_business_id', '=', $this->user->id];
				}
				elseif (in_array($this->user->type, [3]))
				{
					$where[] = ['sub_business_id', '=', $this->user->id];
				}
				else
				{
					$where[] = ['id', '=', 0]; //不显示
				}

				if (!empty($card_business_id))
				{
					$where[] = ['card_business_id', '=', $card_business_id];
				}

				$data['info']['today_total_order'] = Order::where($where)->count('id');

				// 今日成功率
				if (!$data['info']['today_total_order'])
				{
					$data['info']['today_success_rate'] = 0;
				}
				else
				{
					$data['info']['today_success_rate'] = round($data['info']['today_success_order'] / ($data['info']['today_total_order']) * 100, 2);
				}

				$_data = $data['info'];

				$this->redis->set($key, $_data, getDataCacheTime());
			}
		}

		return $data;
	}


	/**
	 * 列表
	 */
	public function list()
	{
		$data = $this->_search();

		return $this->returnData($data);
	}

	/**
	 * 导出
	 */
	public function export()
	{
		$this->writeLog('导出' . $this->controller_name);

		set_time_limit(0);
		ini_set('memory_limit', -1);
		$export_value = [
			'account_type' => '收款类型',
			'out_trade_no' => '商户单号',
			'amount' => '交易金额',
			'usdt_amount' => 'Usdt金额',
			'account' => '账号',
			'system_fee' => '费用',
			'create_time' => '下单时间',
			'success_time' => '成功时间',
			'status' => '状态',
			'remark' => '下单备注',
			'pay_remark' => '支付备注',
		];

		$fileName = '测试导出';
		header("Content-type:text/csv");

		header("Content-Disposition:attachment;filename=" . $fileName . '.csv');
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:0');
		header('Pragma:public');
		$fp = fopen('php://output', 'a+');//打开php标准输出流
		fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

		fputcsv($fp, $export_value);

		$where = $this->_create_where();


		$status_str = [-1 => '未支付', 1 => '成功，未回调', 2 => '成功，已回调', -2 => '生成订单失败'];

		$total = Order::where($where)->count('id');
		if ($total > 100000)
		{
			$total = 100000;
		}

		//每次导出数据
		$nums = 100000;

		$fields = array_keys($export_value);
		$fields[] = 'commission';
		$fields = implode(',', $fields);
		$max_page = ceil($total / $nums);
		for ($i = 0; $i < $max_page; $i++)
		{
			$list = Order::field($fields)->where($where)->limit($i * $nums, $nums)->order('id', 'desc')->select()->toArray();
			foreach ($list as &$v)
			{
				if (isset($v['commission']))
				{
					$v['system_fee'] = $v['system_fee'] + $v['commission'];
					isset($v['system_rate']) && $v['system_rate'] = $v['system_rate'] . '% + ' . $v['commission'];
					unset($v['commission']);
				}
				$v['account'] = $v['account'] . "\t";
				$v['out_trade_no'] = $v['out_trade_no'] . "\t";
				$v['status'] = $status_str[$v['status']] ?? '';
				$v['account_type'] = listAccountType()[$v['account_type']] ?? '';
				fputcsv($fp, $v);
			}
		}
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
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$tmp['info'] = json_decode($model->info, true);

		$info = [];
		$info[] = [
			'title' => '系统单号',
			'value' => $model->order_no,
		];
		$info[] = [
			'title' => '商户单号',
			'value' => $model->out_trade_no,
		];
		$info[] = [
			'title' => '下单时间',
			'value' => $model->create_time,
		];
		$info[] = [
			'title' => '成功时间',
			'value' => $model->success_time,
		];
		if ($model->account_type == 1) //银行卡
		{
			$info[] = [
				'title' => '银行名称',
				'value' => $model->bank,
			];
			$info[] = [
				'title' => '银行支行',
				'value' => $model->branch,
			];
			$info[] = [
				'title' => '姓名',
				'value' => $model->account_name,
			];
			$info[] = [
				'title' => '银行卡号',
				'value' => $model->account,
			];
			$info[] = [
				'title' => '交易金额',
				'value' => $model->amount,
				'class' => 'text-danger bolder',
			];
		}
		elseif ($model->account_type == 2) //USDT收款
		{
			$info[] = [
				'title' => 'usdt钱包地址',
				'value' => $model->account,
				'check' => 'usdt_trade'
			];
			$info[] = [
				'title' => '交易金额',
				'value' => $model->amount,
				'class' => 'text-danger bolder',
			];
			$info[] = [
				'title' => 'Usdt金额',
				'value' => $model->usdt_amount,
				'class' => 'text-success bolder',
			];
		}
		elseif ($model->account_type == 3) //支付宝
		{
			$info[] = [
				'title' => '支付宝姓名',
				'value' => $model->account_name,
			];
			$info[] = [
				'title' => '支付宝账号',
				'value' => $model->account,
			];
			$info[] = [
				'title' => '交易金额',
				'value' => $model->amount,
				'class' => 'text-danger bolder',
			];
		}
		else
		{
			$info[] = [
				'title' => '数字RMB姓名',
				'value' => $model->account_name,
			];
			$info[] = [
				'title' => '钱包编号',
				'value' => $model->account,
			];
			$info[] = [
				'title' => '交易金额',
				'value' => $model->amount,
				'class' => 'text-danger bolder',
			];
		}

		$agent_commission = floatval($model->agent_commission ?? 0);
		$agent_order_fee = floatval($model->agent_order_fee ?? 0);
		$_fee = ($agent_commission + $agent_order_fee) . "（{$agent_commission}固定费用，{$agent_order_fee}订单费用）";

		$info[] = [
			'title' => '代理费用',
			'value' => $model->status > 0 ? $_fee : '',
			'class' => '',
		];

		if (!empty($model->image_url))
		{
			$info[] = [
				'title' => '转账截图',
				'value' => $model->image_url,
				'check' => 'image',
			];
		}
		$info[] = [
			'title' => '状态',
			'value' => isset(Order::STATUS[$model->status]) ? Order::STATUS[$model->status] : '',
			'class' => isset(Order::STATUS_CLASS[$model->status]) ? Order::STATUS_CLASS[$model->status] : '',
		];

		$data = [];
		$data['no'] = $model->no;
		$data['status'] = $model->status;
		$data['info'] = $info;

		return $this->returnData($data);
	}

	/**
	 * 设为成功
	 */
	public function set_order_success()
	{
		$this->writeLog($this->controller_name . '设为成功');

		$rule = [
			'no' => 'require',
			'remark|备注' => 'max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if ($model->business_id != $this->user->id)
		{
			return $this->returnError('信息不属于商户');
		}

		\app\service\OrderService::completeOrder($model->id);

		$model->remark = input('post.remark');

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 备注
	 */
	public function set_remark()
	{
		$this->writeLog($this->controller_name . '备注');

		$rule = [
			'no' => 'require',
			'remark|备注' => 'require|max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if ($model->business_id != $this->user->id)
		{
			return $this->returnError('信息不属于商户');
		}

		$model->remark = input('post.remark');

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 补发通知
	 */
	public function resend_notify()
	{
		$this->writeLog($this->controller_name . '补发通知');

		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$res = \app\service\OrderService::sendNotify($model->id, $return_data = 1);

		return $this->returnData($res);
	}


}
