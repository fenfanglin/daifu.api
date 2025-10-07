<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\Order;
use app\model\FinanceStatistics;

class FinanceController extends AuthController
{
	private $controller_name = '交易';

	/**
	 * 初始化
	 */
	public function __construct()
	{
		parent::__construct();

		///类型：1代理 2工作室 3商户
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
	 * 交易排行榜
	 */
	public function transaction_rank()
	{
		$rule = [
			'sub_business_id|商户编号' => 'integer',
			'channel_id|通道' => 'integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$sub_business_id = input('post.sub_business_id');
		$channel_id = input('post.channel_id');
		$search_time = input('post.search_time');

		if (!empty($sub_business_id))
		{
			$business = Business::where('id', $sub_business_id)->find();
			if (!$business)
			{
				return $this->returnError('无法找到商户');
			}

			if ($business->parent_id != $this->user->id)
			{
				return $this->returnError('商户id不属于四方商户');
			}
		}

		if (!$search_time)
		{
			$search_time[0] = $search_time[1] = date('Y-m-d');
		}

		$search_time[0] = date('Y-m-d', strtotime($search_time[0]));
		$search_time[1] = date('Y-m-d', strtotime($search_time[1]));

		$where = [];
		$where[] = ['business_id', '=', $this->user->id];
		$where[] = ['sub_business_id', '>', 0];

		if (!empty($search_time[0]))
		{
			$where[] = ['date', '>=', $search_time[0]];
		}
		if (!empty($search_time[1]))
		{
			$where[] = ['date', '<=', $search_time[1] . ' 23:59:59'];
		}
		if (!empty($sub_business_id))
		{
			$where[] = ['sub_business_id', '=', $sub_business_id];
		}
		if (!empty($channel_id))
		{
			$where[] = ['channel_id', '=', $channel_id];
		}
		$finance_list = FinanceStatistics::field('sub_business_id, SUM(total_order) as total_order, SUM(success_order) as success_order, SUM(success_amount) as success_amount')->where($where)->group('sub_business_id')->select();

		// var_dump(FinanceStatistics::field('sub_business_id, SUM(total_order) as total_order, SUM(success_order) as success_order, SUM(success_amount) as success_amount')->where($where)->group('sub_business_id')->fetchSql(1)->select());

		$data = [];
		foreach ($finance_list as $value)
		{
			$tmp = [
				'success_amount' => $value['success_amount'],
				'sub_business_id' => $value['sub_business_id'],
				'business_username' => isset($value->subBusiness->username) ? $value->subBusiness->username : '',
				'total_order' => $value['total_order'],
				'success_order' => $value['success_order'],
			];

			$data[$value['sub_business_id']] = $tmp;
		}

		if ($search_time[1] >= date('Y-m-d'))
		{
			$where = [];
			$where[] = ['parent_id', '=', $this->user->id];
			$where[] = ['status', '=', 1]; //状态：1启用 -1禁用
			$where[] = ['type', '=', 1]; //类型：1代理 2工作室 3商户
			if (!empty($sub_business_id))
			{
				$where[] = ['id', '=', $sub_business_id];
			}

			$business_list = Business::field('id, username')->where($where)->select();

			foreach ($business_list as $business)
			{
				$where_total = [];
				$where_total[] = ['sub_business_id', '=', $business->id];
				$where_total[] = ['create_time', '>=', $search_time[0]];
				$where_total[] = ['create_time', '<=', $search_time[1] . ' 23:59:59'];

				$where_success = [];
				$where_success[] = ['sub_business_id', '=', $business->id];
				$where_success[] = ['success_time', '>=', $search_time[0]];
				$where_success[] = ['success_time', '<=', $search_time[1] . ' 23:59:59'];

				if (!empty($channel_id))
				{
					$where_total[] = ['channel_id', '=', $channel_id];

					$where_success[] = ['channel_id', '=', $channel_id];
				}

				$total_order = Order::where($where_total)->count('id');
				$success_order = Order::where($where_success)->where('status', '>', 0)->count('id');
				$success_amount = Order::where($where_success)->where('status', '>', 0)->sum('pay_amount');

				if (isset($data[$business->id]))
				{
					$value = $data[$business->id];
				}
				else
				{
					$value = [
						'success_amount' => 0,
						'sub_business_id' => $business->id,
						'business_username' => $business->username,
						'total_order' => 0,
						'success_order' => 0,
					];
				}

				$tmp = [
					'success_amount' => $value['success_amount'] + $success_amount,
					'sub_business_id' => $value['sub_business_id'],
					'business_username' => $value['business_username'],
					'total_order' => $value['total_order'] + $total_order,
					'success_order' => $value['success_order'] + $success_order,
				];

				$data[$value['sub_business_id']] = $tmp;
			}
		}

		$_data = $data;
		$data = [];
		foreach ($_data as $value)
		{
			if ($value['success_amount'] > 0)
			{
				if ($value['total_order'])
				{
					$value['success_rate'] = round($value['success_order'] / $value['total_order'] * 100, 2) . '%';
				}
				else
				{
					$value['success_rate'] = 0 . '%';
				}

				$value['success_amount'] = round($value['success_amount'], 2);

				$data[] = $value;
			}
		}

		sort($data);
		$data = array_reverse($data);

		return $this->returnData($data);
	}
}
