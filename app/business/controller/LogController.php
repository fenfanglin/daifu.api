<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\model\BusinessLog;

class LogController extends AuthController
{
	private $controller_name = '操作记录';

	/**
	 * 初始化
	 */
	public function __construct()
	{
		parent::__construct();

        //类型：1代理 2工作室 3商户
		if ($this->user->type == 2)
		{
			Common::error('此功能不开放给工作室');
		}

		if ($this->user->type == 3)
		{
			Common::error('此功能不开放给商户');
		}
	}

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
		$url = $params['url'] ?? NULL;
		$ip = $params['ip'] ?? NULL;
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = BusinessLog::field('*');

		$where = [];

		$where[] = ['business_id', '=', $this->user->id];

		if (!empty($create_time[0]) && $create_time[0] > 0)
		{
			$_begin_time = date('Y-m-d H:i:s', strtotime($create_time[0]) - 1);
			$where[] = ['create_time', '>', $_begin_time];
		}
		if (!empty($create_time[1]) && $create_time[1] > 0)
		{
			$_end_time = date('Y-m-d H:i:s', strtotime($create_time[1] . ' +1 second'));
			$where[] = ['create_time', '<', $_end_time];
		}

		if (!empty($url))
		{
			$where[] = ['url', '=', $url];
		}
		if (!empty($ip))
		{
			$where[] = ['ip', '=', $ip];
		}

		$query->where($where)->order('id desc');
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
			$tmp['business_id'] = $model->business_id;
			$tmp['name'] = $model->name;
			$tmp['ip'] = $model->ip;
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
	 * 导出
	 */
	public function export()
	{
		$this->writeLog($this->controller_name . '导出');

		$params = input('post.');
		$params['is_export'] = 1;

		$data = $this->_search($params, $is_export = 1);

		$export_value = [
			'id' => 'ID',
			'business_id' => '商户编号',
			'name' => '操作',
			'ip' => 'ip',
			'create_time' => '操作时间',
		];

		Common::exportExcel($data['list'], $export_value);
	}

	/**
	 * 系统银行卡
	 */
	public function url_list()
	{
		$query = BusinessLog::field('url,any_value(name)');

		$query->where('business_id', $this->user->id)->group('url');

		$list = $query->order('url asc')->select();

		$data = [];
		foreach ($list as $value)
		{
			$tmp = [];
			$tmp['url'] = $value['url'];
			$tmp['name'] = isset($this->url_arr[$value['url']]) ? $this->url_arr[$value['url']] : '';

			if (!$tmp['name'])
			{
				continue;
			}

			$data[] = $tmp;
		}

		return $this->returnData($data);
	}

	private $url_arr = [
		'/business/change_password' => '商户修改密码',
		'/business/delete' => '商户删除',
		'/business/disable' => '商户禁用',
		'/business/enable' => '商户启用',
		'/business/save' => '商户保存',
		'/business/save_withdraw' => '商户充值',
		'/card_business/change_password' => '工作室修改密码',
		'/card_business/delete' => '工作室删除',
		'/card_business/disable' => '工作室禁用',
		'/card_business/enable' => '工作室启用',
		'/card_business/save' => '工作室保存',
		'/demo/pay' => '测试下单提交',
		'/money_log/export' => '资金明细导出',
		'/notify_log/export' => '监控记录导出',
		'/order/export' => '订单导出',
		'/order/resend_notify' => '订单补发通知',
		'/order/set_order_success' => '订单设为成功',
		'/order/set_remark' => '订单备注',
		'/pay_setting/save' => '支付设置保存',
		'/recharge_usdt/pay' => 'Usdt提交充值',
		'/user/change_password' => '基本设置修改密码',
		'/user/change_password_api' => '基本设置修改监控密码',
		'/user/google_auth_bind' => '基本设置绑定谷歌密钥',
		'/user/save_setting' => '基本设置保存',
		'/withdraw_log/export' => '可提现资金明细导出',
		'/withdraw/submit' => '提现提交',
		'/withdraw/verify' => '提现审核',
	];
}
