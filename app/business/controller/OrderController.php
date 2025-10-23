<?php

namespace app\business\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\Order;
use app\service\api\Jinqianbao;
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
		$sub_business_id = intval(input('post.sub_business_id') ?? NULL);
		$card_business_id = input('post.card_business_id') ?? NULL;
		$success_time = input('post.success_time') ?? NULL;
		$create_time = input('post.create_time') ?? NULL;
		$account_type = input('post.account_type') ?? NULL;


		$where = [];

		//类型：1代理 2工作室 3商户
		if (in_array($this->user->type, [1]))
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
		if (!empty($sub_business_id))
		{
			$where[] = ['sub_business_id', '=', $sub_business_id];
		}
		if (isset($card_business_id) && $card_business_id !== '')
		{
			$where[] = ['card_business_id', '=', $card_business_id];
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
			// $limit = Common::EXPORT_MAX_ROWS;
			$limit = 30000;
			$page = 0;
		}

		$query = Order::field('*');

		$query->where($where)->order('id desc');
		// var_dump($query->getLastSql());
		// var_dump($query->buildSql(true));
		// echo $query->fetchSql(1)->select();

		$list = $query->paginate([
			'list_rows' => $limit,
			'page' => $page,
		]);

		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			// $tmp['id'] = $model->id;
			$tmp['no'] = $model->no;
			$tmp['order_no'] = $model->order_no;
			$tmp['out_trade_no'] = $model->out_trade_no;
			$tmp['account_type'] = $model->account_type;
			$tmp['account_name'] = $model->account_name;
			$tmp['account'] = $model->account;
			$tmp['business_id'] = $model->business_id;
			$tmp['sub_business_id'] = $model->sub_business_id;
			$tmp['sub_business_realname'] = $model->subBusiness->realname ?? '';
			$tmp['amount'] = $model->amount;
			$tmp['usdt_amount'] = $model->usdt_amount;
			$tmp['ip'] = $model->ip;
			$tmp['pay_ip'] = $model->pay_ip;
			$tmp['create_time'] = $model->create_time;
			$tmp['success_time'] = $model->success_time;
			$tmp['bank'] = $model->bank;
			$tmp['branch'] = $model->branch;

			$tmp['info'] = json_decode($model->info, true);
			$tmp['remark'] = $model->remark;
			$tmp['pay_remark'] = $model->pay_remark;
			$tmp['card_business_id'] = $model->card_business_id;
			$tmp['card_business_realname'] = $model->cardBusiness->realname ?? '';
			$tmp['image_url'] = $model->image_url;
			$tmp['business_order_fee'] = $model->business_order_fee;
			$tmp['business_commission'] = $model->business_commission;
			$tmp['total_fee'] = $model->business_order_fee + $model->business_commission;

			$tmp['system_fee'] = 0;
			$tmp['business_fee'] = 0;
			$tmp['allow_withdraw'] = 0;

			if ($model->status > 0)
			{
				$tmp['system_fee'] = $model->system_fee + $model->commission;
				$tmp['business_fee'] = $model->business_fee;
				$tmp['allow_withdraw'] = $model->allow_withdraw;
			}

			$tmp['status_str'] = isset(Order::STATUS[$model->status]) ? Order::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(Order::STATUS_CLASS[$model->status]) ? Order::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string) $model->status;


			$data['list'][] = $tmp;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}

		$data['total'] = $query->count('id');

		if (!$is_export)
		{
			$business_id = $this->user->id;

			$sign = md5(json_encode($where, JSON_UNESCAPED_UNICODE));
			$key = "business_data_order_{$business_id}_{$sign}";

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
					// 订单费用
					$commission = Order::where($where)->where('status', '>', 0)->sum("business_commission");
					$order_fee = Order::where($where)->where('status', '>', 0)->sum("business_order_fee");
					$data['info']['success_fee'] = number_format($commission + $order_fee, 4, '.', '');

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
				// $data['info']['sql'] = var_dump(Order::getLastSql());
				// 今日交易笔数
				$data['info']['today_success_order'] = Order::where($where)->count('id');
				// 今日总费用
				$commission = Order::where($where)->sum("business_commission");
				$order_fee = Order::where($where)->sum("business_order_fee");
				$data['info']['today_fee'] = number_format($commission + $order_fee, 4, '.', '');

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
		set_time_limit(0);
		ini_set('memory_limit', -1);

		if (in_array($this->user->type, [1]))   //类型：1代理 2工作室 3商户
		{
			$export_value = [
				'account_type' => '收款方式',
				'out_trade_no' => '商户单号',
				'amount' => '交易金额',
				'usdt_amount' => 'Usdt金额',
				'account' => '账号',
				'system_fee' => '系统费用',
				'business_rate' => '订单费率',
				'allow_withdraw' => '可提现金额',
				'create_time' => '下单时间',
				'success_time' => '成功时间',
				'status' => '状态',
				'remark' => '下单备注',
				'pay_remark' => '支付备注',
			];
		}
		elseif (in_array($this->user->type, [2]))
		{
			$export_value = [
				'account_type' => '收款方式',
				'out_trade_no' => '商户单号',
				'amount' => '交易金额',
				'usdt_amount' => 'Usdt金额',
				'account' => '账号',
				'create_time' => '下单时间',
				'success_time' => '成功时间',
				'status' => '状态',
				'remark' => '下单备注',
				'pay_remark' => '支付备注',
			];
		}
		elseif (in_array($this->user->type, [3]))
		{
			$export_value = [
				'account_type' => '收款方式',
				'out_trade_no' => '商户单号',
				'amount' => '交易金额',
				'usdt_amount' => 'Usdt金额',
				'account' => '账号',
				'account_name' => '账号名称',
				'business_commission' => '固定订单费用',
				'business_order_fee' => '订单手续费',
				// 'business_rate' => '订单费率',
				// 'allow_withdraw' => '可提现金额',
				'create_time' => '下单时间',
				'success_time' => '成功时间',
				'status' => '状态',
				'remark' => '下单备注',
				'pay_remark' => '支付备注',
			];
		}

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

				if ($v['status'] != 2)
				{
					$v['business_commission'] = 0;
					$v['business_order_fee'] = 0;
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

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属代理');
			}
		}
		elseif (in_array($this->user->type, [2]))
		{
			if ($model->card_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于工作室');
			}
		}
		elseif (in_array($this->user->type, [3]))
		{
			if ($model->sub_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于商户');
			}
		}
		else
		{
			return $this->returnError('信息错误');
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
				'title' => 'Usdt汇率',
				'value' => $model->usdt_rate,
				'class' => '',
			];
			$info[] = [
				'title' => 'Usdt金额',
				'value' => $model->usdt_amount,
				'class' => '',
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

		//类型：1代理 2工作室 3商户
		if (in_array($this->user->type, [1]))
		{
			$agent_commission = floatval($model->agent_commission ?? 0);
			$agent_order_fee = floatval($model->agent_order_fee ?? 0);

			$info[] = [
				'title' => '代理费用',
				'value' => $model->status > 0 ? $agent_order_fee : '',
				'class' => '',
			];
			$info[] = [
				'title' => '固定费用',
				'value' => $model->status > 0 ? $agent_commission : '',
				'class' => '',
			];
		}
		elseif (in_array($this->user->type, [2]))
		{
			$card_commission = floatval($model->card_commission ?? 0);
			$card_order_fee = floatval($model->card_order_fee ?? 0);

			$info[] = [
				'title' => '工作室费用',
				'value' => $model->status > 0 ? $card_order_fee : '',
				'class' => '',
			];
			$info[] = [
				'title' => '固定费用',
				'value' => $model->status > 0 ? $card_commission : '',
				'class' => '',
			];
		}
		elseif (in_array($this->user->type, [3]))
		{
			$business_commission = floatval($model->business_commission ?? 0);
			$business_order_fee = floatval($model->business_order_fee ?? 0);

			$info[] = [
				'title' => '商户费用',
				'value' => $model->status > 0 ? $business_order_fee : '',
				'class' => '',
			];
			$info[] = [
				'title' => '固定费用',
				'value' => $model->status > 0 ? $business_commission : '',
				'class' => '',
			];
		}

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
		$data['row'] = $model;

		return $this->returnData($data);
	}

	/**
	 * 设为成功
	 */
	public function set_order_success()
	{
		$rule = [
			'no' => 'require',
			'pay_remark|备注' => 'max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		if (!judgeRepeatClick($no))
		{
			return $this->returnError('请勿重复点击！');
		}
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属于代理');
			}
		}
		elseif (in_array($this->user->type, [2]))
		{
			if ($model->card_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于工作室');
			}
		}
		elseif (in_array($this->user->type, [3]))
		{
			return $this->returnError('权限不足');
		}
		else
		{
			return $this->returnError('权限不足');
		}
		if ($model->status > 0)
		{
			return $this->returnError('订单已经处理过');
		}
		\app\service\OrderService::completeOrder($model->id);

		$model->pay_remark = input('post.pay_remark');
		$model->image_url = input('post.image_url') ?? '';

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "设为成功：{$model->out_trade_no}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 设为失败
	 */
	public function set_order_fail()
	{
		$rule = [
			'no' => 'require',
			'pay_remark|备注' => 'max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		if (!judgeRepeatClick($no))
		{
			return $this->returnError('请勿重复点击！');
		}
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属于代理');
			}
		}
		elseif (in_array($this->user->type, [2]))
		{
			if ($model->card_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于工作室');
			}
		}
		elseif (in_array($this->user->type, [3]))
		{
			return $this->returnError('权限不足');
		}
		else
		{
			return $this->returnError('权限不足');
		}
		if ($model->status == -2)
		{
			return $this->returnError('订单已经处理过');
		}
		\app\service\OrderService::failOrder($model->id);

		$model->pay_remark = input('post.pay_remark');
		$model->image_url = input('post.image_url') ?? '';

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "设为失败：{$model->out_trade_no}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 设为未支付
	 */
	public function set_order_not_pay()
	{
		$rule = [
			'no' => 'require',
			'pay_remark|备注' => 'max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		if (!judgeRepeatClick($no))
		{
			return $this->returnError('请勿重复点击！');
		}
		$model = Order::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属于代理');
			}
		}
		elseif (in_array($this->user->type, [2]))
		{
			if ($model->card_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于工作室');
			}
		}
		elseif (in_array($this->user->type, [3]))
		{
			return $this->returnError('权限不足');
		}
		else
		{
			return $this->returnError('权限不足');
		}
		if ($model->status == -1)
		{
			return $this->returnError('订单已经处理过');
		}
		\app\service\OrderService::notPayOrder($model->id);

		$model->pay_remark = input('post.pay_remark');
		// $model->image_url = input('post.image_url') ?? '';

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "设为未支付：{$model->out_trade_no}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 备注
	 */
	public function set_remark()
	{
		$rule = [
			'no' => 'require'
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

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属于代理');
			}
		}
		elseif (in_array($this->user->type, [2]))
		{
			if ($model->card_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于工作室');
			}
		}

		if (in_array($this->user->type, [3]))
		{
			$model->remark = input('post.remark');
		}
		else
		{
			$model->pay_remark = input('post.pay_remark');
			$model->image_url = input('post.image_url');
		}


		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "备注：{$model->out_trade_no}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 补发通知
	 */
	public function resend_notify()
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

		if (in_array($this->user->type, [1])) //类型：1代理 2工作室 3商户
		{
			if ($model->business_id != $this->user->id)
			{
				return $this->returnError('信息不属于代理');
			}
		}
		elseif (in_array($this->user->type, [2]))
		{
			if ($model->card_business_id != $this->user->id)
			{
				return $this->returnError('信息不属于工作室');
			}
		}
		elseif (in_array($this->user->type, [3]))
		{
			return $this->returnError('权限不足');
		}
		else
		{
			return $this->returnError('权限不足');
		}

		$res = \app\service\OrderService::sendNotify($model->id, $return_data = 1);
		$this->writeLog($this->controller_name . "补发通知：{$model->out_trade_no}");

		return $this->returnData($res);
	}

	/**
	 * 添加订单
	 */
	public function add()
	{
		// 类型：1代理 2工作室 3商户
		if (!in_array($this->user->type, [3]))
		{
			return $this->returnError('只允许商户账号下单');
		}

		$rule = [
			'account_type|收款类型' => 'require|integer|in:1,2,3,4',
			'out_trade_no|商户订单号' => 'require|alphaNum|max:30',
			'amount|金额' => 'require|float|>:0',
			'notify_url|回调连接' => 'require|max:255',
		];

		if (input('post.account_type') == 1)
		{
			$rule['bank|银行名称'] = 'require|max:50';
			$rule['branch|银行支行'] = 'require|max:50';
			$rule['account_name|账户名称'] = 'require|max:50';
			$rule['account|银行卡号'] = 'require|max:20';
		}
		elseif (input('post.account_type') == 2)
		{
			$rule['account|钱包地址'] = 'require|max:34';
		}
		elseif (input('post.account_type') == 3)
		{
			$rule['account_name|账户名称'] = 'require|max:50';
			$rule['account|支付宝账号'] = 'require|max:50';
		}
		elseif (input('post.account_type') == 4)
		{
			$rule['account_name|姓名'] = 'require|max:50';
			$rule['account|账号'] = 'require|max:50';
		}

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$params = input('post.');

		$params['mchid'] = $this->user->id;
		$params['timestamp'] = time();
		$params['sign'] = $this->createSign($params, $this->user->secret_key);

		$url = config('app.api_url') . 'order/create';

		$res = Common::curl($url, $params);

		Common::writeLog([
			'url' => $url,
			'params' => $params,
			'res' => json_decode($res, true) ?? $res,
		], 'business_order_add');

		$res = json_decode($res, true);

		if (isset($res['code']) && $res['code'] == '200')
		{
			$this->writeLog($this->controller_name . '下单成功：' . ($res['data']['out_trade_no'] ?? ''));

			return $this->returnSuccess('下单成功');
		}
		else
		{
			$this->writeLog($this->controller_name . '下单失败：' . ($res['msg'] ?? '接口错误'));

			return $this->returnError($res['msg'] ?? '接口错误');
		}
	}

	/**
	 * 导入订单
	 */
	public function import()
	{
		$success = 0;
		$error = 0;
		$result = [];
		$data = input('post.data') ?? [];

		foreach ($data as $value)
		{
			$rule = [
				'out_trade_no|商户订单号' => 'require|alphaNum|max:30',
				'amount|金额' => 'require|float|>:0',
				'notify_url|回调连接' => 'require|max:255',
			];

			$arr_account_type = [
				'银行卡' => 1,
				'USDT' => 2,
				'支付宝' => 3,
				'数字RMB' => 4,
			];

			$account_type = $arr_account_type[$value['account_type']] ?? 0;
			if (!$account_type)
			{
				$error++;

				$result[] = [
					'status' => 'error',
					'msg' => '收款类型不正确1',
					'account_type' => $account_type,
				];

				continue;
			}

			if ($account_type == 1)
			{
				$rule['bank|银行名称'] = 'require|max:50';
				$rule['branch|银行支行'] = 'require|max:50';
				$rule['account_name|账户名称'] = 'require|max:50';
				$rule['account|银行卡号'] = 'require|max:20';
			}
			elseif ($account_type == 2)
			{
				$rule['account|钱包地址'] = 'require|max:34';
			}
			elseif ($account_type == 3)
			{
				$rule['account_name|账户名称'] = 'require|max:50';
				$rule['account|支付宝账号'] = 'require|max:50';
			}
			elseif ($account_type == 4)
			{
				$rule['account_name|姓名'] = 'require|max:50';
				$rule['account|账号'] = 'require|max:50';
			}

			if (!$this->validate($value, $rule))
			{
				$error++;

				$result[] = [
					'status' => 'error',
					'msg' => $this->getValidateError(),
				];

				continue;
			}

			$params = $value;

			$params['mchid'] = $this->user->id;
			$params['account_type'] = $account_type;
			$params['timestamp'] = time();
			$params['sign'] = $this->createSign($params, $this->user->secret_key);

			$url = config('app.api_url') . 'order/create';

			$res = Common::curl($url, $params);

			Common::writeLog([
				'url' => $url,
				'params' => $params,
				'res' => json_decode($res, true) ?? $res,
			], 'business_order_add');

			$res = json_decode($res, true);

			if (isset($res['code']) && $res['code'] == '200')
			{
				$success++;
				$result[] = [
					'status' => 'success',
					'msg' => '下单成功',
				];
			}
			else
			{
				$error++;
				$result[] = [
					'status' => 'error',
					'msg' => $res['msg'] ?? '接口错误',
				];
			}
		}

		Common::writeLog([
			'data' => $data,
			'result' => $result,
		], 'business_order_import');

		return $this->returnData($result);

	}

	/**
	 * 生成签名
	 */
	private function createSign($params, $secret_key)
	{
		unset($params['sign']);

		ksort($params); //字典排序

		$str = '';
		foreach ($params as $key => $value)
		{
			$str .= strtolower($key) . '=' . $value . '&';
		}

		$str .= 'key=' . $secret_key;

		return strtoupper(md5($str));
	}

	/**
	 * 可分配工作室列表
	 */
	public function list_card_by_order()
	{
		$param = input('post.');
		$order = Order::where('no', $param['no'])->find();
		if (empty($order))
		{
			return $this->returnData([]);
		}
		$business = Business::where('id', $order['sub_business_id'])->find();
		$list = Business::field('id,username,realname')->where('type', 2)->where('id', 'in', $business['card_business_ids'])->select()->toArray();
		foreach ($list as &$value)
		{
			$value['show'] = '【' . $value['id'] . '】' . $value['realname'];
		}
		$list = array_merge([['id' => 0, 'show' => '请选择工作室']], $list);
		return $this->returnData($list);
	}

	/**
	 * 分配工作室
	 */
	public function allocation()
	{
		$rule = [
			'no' => 'require',
			'card_business_id' => 'require|gt:1'
		];
		$message = [
			'card_business_id' => '工作室必选'
		];
		if (!$this->validate(input('post.'), $rule, $message))
		{
			return $this->returnError($this->getValidateError());
		}
		$params = input('post.');
		$model = Order::where('no', $params['no'])->find();
		if (!empty($model->card_business_id))
		{
			return $this->returnError('请勿重复分配！');
		}
		$business = Business::where('id', $model->sub_business_id)->find();
		if (!in_array($params['card_business_id'], explode(',', $business['card_business_ids'])))
		{
			return $this->returnError('工作室选择错误！');
		}
		$model->card_business_id = $params['card_business_id'];
		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "分配工作室：{$model->out_trade_no}：{$params['card_business_id']}");
		return $this->returnSuccess('保存成功');
	}


	/**
	 * 获取usdt汇率
	 */
	public function get_usdt_rate()
	{
		$data = $this->setting->usdt_rate;
		return $this->returnData($data);
	}

	/**
	 * 工作室修改接单状态
	 */
	public function switch_order()
	{
		$rule = [
			'order_status' => 'require|in:1,-1',
		];
		$message = [
			'order_status' => '数据错误！'
		];
		if (!$this->validate(input('post.'), $rule, $message))
		{
			return $this->returnError($this->getValidateError());
		}
		$params = input('post.');
		$model = $this->user;
		$model->order_status = $params['order_status'];
		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}
		return $this->returnSuccess('保存成功');
	}


	public function get_new_order_status()
	{
		$where = [];
		if ($this->user->type == 1)
		{
			$where['business_id'] = $this->user->id;

		}
		elseif ($this->user->type == 2)
		{
			$where['card_business_id'] = $this->user->id;
		}
		else
		{
			$where['business_id'] = 0;
		}
		$model = Order::where($where)->order('id', 'desc')->find();
		$id = $model->id ?? 0;
		$key = 'business_' . $this->user->id;
		$old_id = $this->redis->get($key);
		if ($old_id != $id)
		{
			$this->redis->set($key, $id);
			return $this->returnData(1);
		}
		else
		{
			return $this->returnData(-1);
		}
	}
}
