<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\DemoOrder;
use app\model\DemoParam;
use app\model\Channel;
use app\model\DemoAccount;

class DemoOrderController extends AuthController
{
	private $controller_name = '演示订单';

	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'status|状态' => 'integer',
			'out_trade_no|商户订单号' => 'alphaNum',
			'card_business_id|卡商' => 'integer',
			'account_type|收款类型' => 'integer',
			// 'success_time|成功时间' => 'dateFormat:Y-m-d H:i:s',
			// 'create_time|下单时间' => 'dateFormat:Y-m-d H:i:s',
		];

		if (!$this->validate($params, $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$user = $this->user;

		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$status = intval($params['status'] ?? NULL);
		$business_id = $params['business_id'] ?? NULL;
		$account_type = intval($params['account_type'] ?? NULL);
		$success_time = $params['success_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = DemoOrder::field('*');

		if (!empty($business_id))
		{
			$query->where('business_id', $business_id);
		}
		if (!empty($account_type))
		{
			$query->where('account_type', $account_type);
		}
		if (!empty($status))
		{
			$query->where('status', $status);
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

		$query->order('create_time desc');
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
			// $tmp['id'] = $model->id;
			$tmp['no'] = $model->no;
			$tmp['order_no'] = $model->order_no;
			$tmp['out_trade_no'] = $model->out_trade_no;
			$tmp['account_name'] = $model->account_name;
			$tmp['account'] = $model->account;
			$tmp['business_id'] = $model->business_id;
			$tmp['amount'] = $model->amount;
			$tmp['usdt_amount'] = $model->usdt_amount;
			$tmp['system_fee'] = $model->system_fee;
			$tmp['pay_ip'] = $model->pay_ip;
			$tmp['create_time'] = $model->create_time;
			$tmp['success_time'] = $model->success_time;
			$tmp['bank'] = $model->bank;
			$tmp['branch'] = $model->branch;
			$tmp['remark'] = $model->remark;
			$tmp['account_type'] = $model->account_type;

//			$tmp['channel_id'] = $model->channel_id;
//			$tmp['channel_name'] = $model->channel ? $model->channel->name : '';

			$tmp['status_str'] = isset(DemoOrder::STATUS[$model->status]) ? DemoOrder::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(DemoOrder::STATUS_CLASS[$model->status]) ? DemoOrder::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string)$model->status;

			$data['list'][] = $tmp;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}

		$data['total'] = $query->count();

		if (!$is_export)
		{
			$where = [];
			$where[] = ['status', 'in', [1, 2]];
			$where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
			if (!empty($account_type))
			{
				$where[] = ['account_type', '=', $account_type];
			}
			if (!empty($business_id))
			{
				$where[] = ['business_id', '=', $business_id];
			}

			// 今日交易总额
			$data['info']['today_amount'] = DemoOrder::where($where)->sum('amount');

			// 今日交易笔数
			$data['info']['today_order'] = DemoOrder::where($where)->count('id');

			// 今日总笔数
			$where = [];
			$where[] = ['create_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];
			if (!empty($account_type))
			{
				$where[] = ['account_type', '=', $account_type];
			}
			if (!empty($business_id))
			{
				$where[] = ['business_id', '=', $business_id];
			}

			$data['info']['today_order_total'] = DemoOrder::where($where)->count('id');

			// 今日成功率
			if (!$data['info']['today_order_total'])
			{
				$data['info']['today_success_rate'] = 0;
			}
			else
			{
				$data['info']['today_success_rate'] = round($data['info']['today_order']/($data['info']['today_order_total']) * 100, 2);
			}
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
		$this->writeLog('导出' . $this->controller_name);

		$params = input('post.');
		$params['is_export'] = 1;

		$data = $this->_search($params, $is_export = 1);

		$export_value = [
			'out_trade_no' => '商户单号',
			'post_amount' => '发起金额',
			'pay_amount' => '交易金额',
			'usdt_amount' => 'Usdt金额',
			'channel_name' => '支付通道',
			'account_name' => '账号名称',
			'account' => '账号',
			'fee' => '费用',
			'create_time' => '下单时间',
			'success_time' => '成功时间',
			'status_str' => '状态',
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
		$model = DemoOrder::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

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

		$info[] = [
			'title' => '收款类型',
			'value' => listAccountType()[$model->account_type] ?? '',
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
        } elseif ($model->account_type == 2) //USDT收款
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
                'class' => 'text-warning bolder',
            ];
            $info[] = [
                'title' => 'Usdt金额',
                'value' => $model->usdt_amount,
                'class' => 'text-success bolder',
            ];
        } elseif ($model->account_type == 3) //支付宝
        {
            $info[] = [
                'title' => '账号名称',
                'value' => $model->account_name,
            ];
            $info[] = [
                'title' => '交易账号',
                'value' => $model->account,
            ];
            $info[] = [
                'title' => '交易金额',
                'value' => $model->amount,
                'class' => 'text-danger bolder',
            ];
        } else {
            $info[] = [
                'title' => '交易账号',
                'value' => $model->account,
            ];
            $info[] = [
                'title' => '交易金额',
                'value' => $model->pay_amount,
                'class' => 'text-danger bolder',
            ];
        }
		$info[] = [
			'title' => '费用',
			'value' => $model->status > 0 ? $model->system_fee : '',
			'class' => 'bolder',
		];
		$info[] = [
			'title' => '状态',
			'value' => isset(DemoOrder::STATUS[$model->status]) ? DemoOrder::STATUS[$model->status] : '',
			'class' => isset(DemoOrder::STATUS_CLASS[$model->status]) ? DemoOrder::STATUS_CLASS[$model->status] : '',
		];

		$data = [];
		$data['no'] = $model->no;
		$data['status'] = $model->status;
		$data['info'] = $info;

		return $this->returnData($data);
	}

	/**
	 * 快速生成测试订单
	 */
	public function save_simple()
	{
		$this->writeLog('生成测试订单' . $this->controller_name);

		$rule = [
			'business_id|商户' => 'require|integer|>:0',
			'account_type|收款类型' => 'require|integer|in:1,2,3',
			'amount|实付金额' => 'require|float|>:0',
			'create_time_type|下单时间' => 'require|in:2,3,4,5',
			'status|状态' => 'require|in:-1,2',
		];


		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

        $params = input('post.');
        $business = Business::where('id',$params['business_id'])->find();
        if (empty($business) || $business['type'] != 4){
            return  $this->returnError('请输入商户id');
        }
        $parent = Business::where('id',$business['parent_id'])->find();
		$order_rate = $parent->order_rate;



		$param_ip = self::random_ip();
		$order = new DemoOrder;
		$order->business_id = $params['business_id'];
		$order->account_type = $params['account_type'];
		$order->amount = $params['amount'];
		$order->pay_ip = $param_ip[0];
		$order->create_time = date('Y-m-d H:i:s', strtotime('-' . input('post.create_time_type') . ' minutes')) ;

        if ($params['account_type'] == 1){
            $order->account = $params['account'];
            $order->bank = $params['bank'];
            $order->branch = $params['branch'];
            $order->account_name = $params['account_name'];
        }elseif ($params['account_type'] == 2){
            $usdt_rate = $this->setting->usdt_rate;
            $usdt_amount = $order->amount / $usdt_rate;
            $usdt_amount = number_format($usdt_amount, 2, '.', '');
            $order->usdt_rate = $usdt_rate;
            $order->usdt_amount = $usdt_amount;
            $order->account = $params['account'] ?? Common::randomStr(34);
        }else{
            $order->account_name = $params['account_name'];
            $order->account = $params['account'];
        }


		$order->status = input('post.status');

		if ($order->status == 2) //成功，已回调
		{
			$success_time = strtotime($order->create_time) + mt_rand(61, 180);
			$order->success_time = date('Y-m-d H:i:s', $success_time);

			$system_fee = $order->amount * $order_rate;
            $system_fee = number_format($system_fee, 4, '.', '');
			$order->system_fee = $system_fee;
		}


		if (!$order->save())
		{
			return $this->returnError('保存失败');
		}

		return $this->returnSuccess('保存成功');
	}

    static function random_ip() {
        $ranges = array(
            array('10.0.0.1','126.255.255.254'),
            array('128.0.0.1','191.255.255.254'),
            array('193.0.0.1','223.255.255.254')
        );
        $pos = rand(0, count($ranges)-1);
        $start = ip2long($ranges[$pos][0]);
        $end = ip2long($ranges[$pos][1]);
        return long2ip(rand($start, $end));
    }
}
