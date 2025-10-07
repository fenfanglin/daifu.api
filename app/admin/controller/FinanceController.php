<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\Order;
use app\model\Channel;
use app\model\FinanceStatistics;

class FinanceController extends AuthController
{
	private $controller_name = '交易';

	/**
	 * 交易排行榜
	 */
	public function transaction_rank()
	{
		$rule = [
			'business_id|商户编号' => 'integer',
			'channel_id|通道' => 'integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$business_id = input('post.business_id');
		$channel_id = input('post.channel_id');
		$search_time = input('post.search_time');

		if (!$search_time)
		{
			$search_time[0] = $search_time[1] = date('Y-m-d');
		}

		$search_time[0] = date('Y-m-d', strtotime($search_time[0]));
		$search_time[1] = date('Y-m-d', strtotime($search_time[1]));

		$where = [];
		$where[] = ['sub_business_id', '=', 0];

		if (!empty($search_time[0]))
		{
			$where[] = ['date', '>=', $search_time[0]];
		}
		if (!empty($search_time[1]))
		{
			if ($search_time[1] >= date('Y-m-d'))
			{
				$where[] = ['date', '<=', $search_time[1] . date(' H:00:00')];
			}
			else
			{
				$where[] = ['date', '<=', $search_time[1] . ' 23:59:59'];
			}
		}
		if (!empty($business_id))
		{
			$where[] = ['business_id', '=', $business_id];
		}
		if (!empty($channel_id))
		{
			$where[] = ['channel_id', '=', $channel_id];
		}
		$finance_list = FinanceStatistics::field('business_id, SUM(total_order) as total_order, SUM(success_order) as success_order, SUM(success_amount) as success_amount')->where($where)->group('business_id')->select();

		// var_dump(FinanceStatistics::field('business_id, SUM(total_order) as total_order, SUM(success_order) as success_order, SUM(success_amount) as success_amount')->where($where)->group('business_id')->fetchSql(1)->select());

		$data = [];
		foreach ($finance_list as $value)
		{
			$tmp = [
				'success_amount' => $value['success_amount'],
				'business_id' => $value['business_id'],
				'business_username' => $value->business->username,
				'total_order' => $value['total_order'],
				'success_order' => $value['success_order'],
			];

			$data[$value['business_id']] = $tmp;
		}

		// if ($search_time[1] >= date('Y-m-d'))
		// {
		// 	$where = [];
		// 	$where[] = ['status', '=', 1]; //状态：1启用 -1禁用
		// 	$where[] = ['type', 'in', [1, 3]]; //类型：1商户 2卡商 3四方 4四方商户
		// 	if (!empty($business_id))
		// 	{
		// 		$where[] = ['id', '=', $business_id];
		// 	}
		// 	$business_list = Business::field('id, username')->where($where)->select();

		// 	foreach ($business_list as $business)
		// 	{
		// 		$where_total = [];
		// 		$where_total[] = ['create_time', '>=', $search_time[0]];
		// 		$where_total[] = ['create_time', '<=', $search_time[1] . ' 23:59:59'];
		// 		$where_total[] = ['business_id', '=', $business->id];

		// 		$where_success = [];
		// 		$where_success[] = ['success_time', '>=', $search_time[0]];
		// 		$where_success[] = ['success_time', '<=', $search_time[1] . ' 23:59:59'];
		// 		$where_success[] = ['business_id', '=', $business->id];

		// 		if (!empty($channel_id))
		// 		{
		// 			$where_total[] = ['channel_id', '=', $channel_id];

		// 			$where_success[] = ['channel_id', '=', $channel_id];
		// 		}

		// 		$total_order = Order::where($where_total)->count('id');
		// 		$success_order = Order::where($where_success)->where('status', '>', 0)->count('id');
		// 		$success_amount = Order::where($where_success)->where('status', '>', 0)->sum('pay_amount');

		// 		if (isset($data[$business->id]))
		// 		{
		// 			$value = $data[$business->id];
		// 		}
		// 		else
		// 		{
		// 			$value = [
		// 				'success_amount' => 0,
		// 				'business_id' => $business->id,
		// 				'business_username' => $business->username,
		// 				'total_order' => 0,
		// 				'success_order' => 0,
		// 			];
		// 		}

		// 		$tmp = [
		// 			'success_amount' => $value['success_amount'] + $success_amount,
		// 			'business_id' => $value['business_id'],
		// 			'business_username' => $value['business_username'],
		// 			'total_order' => $value['total_order'] + $total_order,
		// 			'success_order' => $value['success_order'] + $success_order,
		// 		];

		// 		$data[$value['business_id']] = $tmp;
		// 	}
		// }

		$_data = $data;
		$data = [];
		foreach ($_data as $value)
		{
			if (in_array($value['business_id'], [10301]))
			{
				continue;
			}

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

	/**
	 * 今日通道数据
	 */
	public function channel_data()
	{
		$today_begin = date('Y-m-d 00:00:00');
		$today_end = date('Y-m-d H:00:00');

		$yesterday_begin = date('Y-m-d 00:00:00', strtotime('-1day'));
		$yesterday_end = date('Y-m-d H:00:00', strtotime('-1day'));
		$channel = Channel::where('status', 1)->column('name', 'id');
		$title_order = [];
		$today_order = [];
		$yesterday_order = [];
		$title_fee = [];
		$today_fee = [];
		$yesterday_fee = [];
		$today_rate = [];
		$yesterday_rate = [];
		$list_today_order = FinanceStatistics::field('sum(success_order) as success_order,sum(total_order) as total_order,channel_id')
			->where('date', 'between', [$today_begin, $today_end])
			->where('sub_business_id', 0)
			->group('channel_id')
			->order('success_order desc')
			->limit(0, 10)
			->select()
			->toArray();
		$channel_ids = array_column($list_today_order, 'channel_id');
		$list_yesterday_order = FinanceStatistics::field('sum(success_order) as success_order,sum(total_order) as total_order,channel_id')
			->where('date', 'between', [$yesterday_begin, $yesterday_end])
			->where('sub_business_id', 0)
			->group('channel_id')
			->order('success_order desc')
			->limit(0, 10)
			->select()
			->toArray();
		$yesterday_channel_ids = array_column($list_yesterday_order, 'channel_id');
		$diff_channel_ids = array_diff($yesterday_channel_ids, $channel_ids);
		foreach ($diff_channel_ids as $v) {
			$list_today_order[] = ['success_order' => 0, 'total_order' => 0, 'channel_id' => $v];
		}
		$list_today_order = array_slice($list_today_order, 0, 10);
		foreach ($list_today_order as &$value) {
			$success_order = FinanceStatistics::where('date', 'between', [$yesterday_begin, $yesterday_end])
				->where('channel_id', $value['channel_id'])
				->where('sub_business_id', 0)
				->sum('success_order');
			$total_order = FinanceStatistics::where('date', 'between', [$yesterday_begin, $yesterday_end])
				->where('channel_id', $value['channel_id'])
				->where('sub_business_id', 0)
				->sum('total_order');
			$today_order[] = $value['success_order'];
			$yesterday_order[] = $success_order;
			$title_order[] = $channel[$value['channel_id']] ?? '';
			if ($value['total_order'] == 0) {
				$today_rate[] = 0;
			} else {
				$today_rate[] = number_format($value['success_order'] / $value['total_order'] * 100, 2, '.', '');
			}
			if ($total_order == 0) {
				$yesterday_rate[] = 0;
			} else {
				$yesterday_rate[] = number_format($success_order / $total_order * 100, 2, '.', '');
			}
		}


		$list_today_fee = FinanceStatistics::field('sum(total_fee) as total_fee,channel_id')
			->where('date', 'between', [$today_begin, $today_end])
			->where('sub_business_id', 0)
			->group('channel_id')
			->order('total_fee desc')
			->limit(0, 10)
			->select()
			->toArray();
		$channel_ids = array_column($list_today_fee, 'channel_id');
		$list_yesterday_fee = FinanceStatistics::field('sum(total_fee) as total_fee,channel_id')
			->where('date', 'between', [$yesterday_begin, $yesterday_end])
			->where('sub_business_id', 0)
			->group('channel_id')
			->order('total_fee desc')
			->limit(0, 10)
			->select()
			->toArray();
		$yesterday_channel_ids = array_column($list_yesterday_fee, 'channel_id');
		$diff_channel_ids = array_diff($yesterday_channel_ids, $channel_ids);
		foreach ($diff_channel_ids as $v) {
			$list_today_fee[] = ['total_fee' => 0, 'channel_id' => $v];
		}
		$list_today_fee = array_slice($list_today_fee, 0, 10);
		foreach ($list_today_fee as &$value) {
			$temp_fee = FinanceStatistics::where('date', 'between', [$yesterday_begin, $yesterday_end])
				->where('channel_id', $value['channel_id'])
				->where('sub_business_id', 0)
				->sum('total_fee');
			$today_fee[] = number_format($value['total_fee'], 2, '.', '');
			$yesterday_fee[] = number_format($temp_fee, 2, '.', '');
			$title_fee[] = $channel[$value['channel_id']] ?? '';
		}
		$data = [
			'title_order' => $title_order,
			'today_order' => $today_order,
			'yesterday_order' => $yesterday_order,
			'title_fee' => $title_fee,
			'today_fee' => $today_fee,
			'yesterday_fee' => $yesterday_fee,
			'today_rate' => $today_rate,
			'yesterday_rate' => $yesterday_rate
		];
		return $this->returnData($data);
	}

	/**
	 * 今日商户数据
	 */
	public function business_data()
	{
		$today_begin = date('Y-m-d 00:00:00');
		$today_end = date('Y-m-d H:00:00');

		$yesterday_begin = date('Y-m-d 00:00:00', strtotime('-1day'));
		$yesterday_end = date('Y-m-d H:00:00', strtotime('-1day'));
		$title_order = [];
		$today_order = [];
		$yesterday_order = [];
		$title_fee = [];
		$today_fee = [];
		$yesterday_fee = [];
		$today_rate = [];
		$yesterday_rate = [];
		$list_today_order = FinanceStatistics::field('sum(success_order) as success_order,sum(total_order) as total_order,business_id')
			->where('date', 'between', [$today_begin, $today_end])
			->where('sub_business_id', 0)
			->group('business_id')
			->order('success_order desc')
			->limit(0, 10)
			->select()
			->toArray();
		$business_ids = array_column($list_today_order, 'business_dds');
		$list_yesterday_order = FinanceStatistics::field('sum(success_order) as success_order,sum(total_order) as total_order,business_id')
			->where('date', 'between', [$yesterday_begin, $yesterday_end])
			->where('sub_business_id', 0)
			->group('business_id')
			->order('success_order desc')
			->limit(0, 10)
			->select()
			->toArray();
		$yesterday_business_ids = array_column($list_yesterday_order, 'business_id');
		$diff_business_ids = array_diff($yesterday_business_ids, $business_ids);
		foreach ($diff_business_ids as $v) {
			$list_today_order[] = ['success_order' => 0, 'total_order' => 0, 'business_id' => $v];
		}
		$list_today_order = array_slice($list_today_order, 0, 10);
		foreach ($list_today_order as &$value) {
			$success_order = FinanceStatistics::where('date', 'between', [$yesterday_begin, $yesterday_end])
				->where('sub_business_id', 0)
				->where('business_id', $value['business_id'])
				->sum('success_order');
			$total_order = FinanceStatistics::where('date', 'between', [$yesterday_begin, $yesterday_end])
				->where('sub_business_id', 0)
				->where('business_id', $value['business_id'])
				->sum('total_order');
			$today_order[] = $value['success_order'];
			$yesterday_order[] = $success_order;
			$title_order[] = $value['business_id'];
			if ($value['total_order'] == 0) {
				$today_rate[] = 0;
			} else {
				$today_rate[] = number_format($value['success_order'] / $value['total_order'] * 100, 2, '.', '');
			}
			if ($total_order == 0) {
				$yesterday_rate[] = 0;
			} else {
				$yesterday_rate[] = number_format($success_order / $total_order * 100, 2, '.', '');
			}
		}
		$list_today_fee = FinanceStatistics::field('sum(total_fee) as total_fee,business_id')
			->where('date', 'between', [$today_begin, $today_end])
			->where('sub_business_id', 0)
			->group('business_id')
			->order('total_fee desc')
			->limit(0, 10)
			->select()
			->toArray();
		$business_ids = array_column($list_today_fee, 'business_id');
		$list_yesterday_fee = FinanceStatistics::field('sum(total_fee) as total_fee,business_id')
			->where('date', 'between', [$yesterday_begin, $yesterday_end])
			->where('sub_business_id', 0)
			->group('business_id')
			->order('total_fee desc')
			->limit(0, 10)
			->select()
			->toArray();
		$yesterday_business_ids = array_column($list_yesterday_fee, 'business_id');
		$diff_channel_ids = array_diff($yesterday_business_ids, $business_ids);
		foreach ($diff_channel_ids as $v) {
			$list_today_fee[] = ['total_fee' => 0, 'business_id' => $v];
		}
		$list_today_fee = array_slice($list_today_fee, 0, 10);
		foreach ($list_today_fee as &$value) {
			$temp_fee = FinanceStatistics::where('date', 'between', [$yesterday_begin, $yesterday_end])
				->where('business_id', $value['business_id'])
				->where('sub_business_id', 0)
				->sum('total_fee');
			$today_fee[] = number_format($value['total_fee'], 2, '.', '');
			$yesterday_fee[] = number_format($temp_fee, 2, '.', '');
			$title_fee[] = $value['business_id'];
		}
		$data = [
			'title_order' => $title_order, 'today_order' => $today_order, 'yesterday_order' => $yesterday_order,
			'title_fee' => $title_fee, 'today_fee' => $today_fee, 'yesterday_fee' => $yesterday_fee,
			'today_rate' => $today_rate, 'yesterday_rate' => $yesterday_rate
		];
		return $this->returnData($data);
	}

	/**
	 * 本周数据
	 */
	public function week_data()
	{
		$begin_date = date('Y-m-d', time() - 86400 * 6);
		$weeks = [];
		$title = [];
		for ($i = 0; $i < 7; $i++) {
			$weeks[] = [date('Y-m-d 00:00:00', strtotime($begin_date) + $i * 86400), date('Y-m-d 23:59:59', strtotime($begin_date) + $i * 86400)];
			$title[] = date('Y-m-d', strtotime($begin_date) + $i * 86400);
		}
		$total_order = [];
		$total_fee = [];
		foreach ($weeks as $value) {
			$temp_order = FinanceStatistics::where('date', 'between', $value)
				->where('sub_business_id', 0)
				->sum('success_order');
			$temp_fee = FinanceStatistics::where('date', 'between', $value)
				->where('sub_business_id', 0)
				->sum('total_fee');
			$total_order[] = $temp_order;
			$total_fee[] = $temp_fee;
		}
		$now_week_begin = date('Y-m-d 00:00:00', strtotime('this week Monday'));
		$now_week_end = date('Y-m-d 23:59:59', strtotime('this week Sunday'));
		$last_week_begin = date('Y-m-d 00:00:00', strtotime('last week monday'));
		$last_week_end = date('Y-m-d 23:59:59', strtotime('last week sunday'));

		$last_week_order = FinanceStatistics::where('date', 'between', [$last_week_begin, $last_week_end])
			->where('sub_business_id', 0)
			->sum('success_order');
		$last_week_fee = FinanceStatistics::where('date', 'between', [$last_week_begin, $last_week_end])
			->where('sub_business_id', 0)
			->sum('total_fee');
		$week_order = FinanceStatistics::where('date', 'between', [$now_week_begin, $now_week_end])
			->where('sub_business_id', 0)
			->sum('success_order');
		$week_fee = FinanceStatistics::where('date', 'between', [$now_week_begin, $now_week_end])
			->where('sub_business_id', 0)
			->sum('total_fee');
		$data = [
			'title' => $title, 'total_order' => $total_order, 'total_fee' => $total_fee,
			'last_week_order' => $last_week_order,
			'last_week_fee' => $last_week_fee,
			'week_order' => $week_order,
			'week_fee' => $week_fee
		];
		return $this->returnData($data);

	}

	/**
	 * 本月数据
	 */
	public function month_data()
	{
		$month_begin = date('Y-m-01 00:00:00');
		$month_end = date('Y-m-d H:i:s');
		$last_month_begin = date('Y-m-01 00:00:00', strtotime($month_begin) - 1);
		$last_month_end = date('Y-m-d H:i:s', strtotime($month_begin) - 1);
		$last_month_order = FinanceStatistics::where('date', 'between', [$last_month_begin, $last_month_end])
			->where('sub_business_id', 0)
			->sum('success_order');
		$last_month_fee = FinanceStatistics::where('date', 'between', [$last_month_begin, $last_month_end])
			->where('sub_business_id', 0)
			->sum('total_fee');
		$month_order = FinanceStatistics::where('date', 'between', [$month_begin, $month_end])
			->where('sub_business_id', 0)
			->sum('success_order');
		$month_fee = FinanceStatistics::where('date', 'between', [$month_begin, $month_end])
			->where('sub_business_id', 0)
			->sum('total_fee');

		$last_30_begin = date('Y-m-d 00:00:00', time() - 86400 * 30);
		$last_30_order = FinanceStatistics::field("sum(success_order) as total_order,date_format(date,'%Y-%m-%d' ) as day")
			->where('date', '>=', $last_30_begin)
			->where('sub_business_id', 0)
			->group('day')
			->select()
			->toArray();
		$last_30_fee = FinanceStatistics::field("sum(total_fee) as total_fee,date_format(date,'%Y-%m-%d' ) as day")
			->where('date', '>=', $last_30_begin)
			->where('sub_business_id', 0)
			->group('day')
			->select()
			->toArray();
		$title = array_column($last_30_order, 'day');
		$total_order = array_column($last_30_order, 'total_order');
		$total_fee = array_column($last_30_fee, 'total_fee');
		$data = [
			'last_month_order' => $last_month_order,
			'last_month_fee' => $last_month_fee,
			'month_order' => $month_order,
			'month_fee' => $month_fee,
			'total_order' => $total_order,
			'total_fee' => $total_fee,
			'title' => $title
		];
		return $this->returnData($data);

	}

	/**
	 * 本月通道数据
	 */
	public function month_channel_data()
	{
		$last_30_begin = date('Y-m-d 00:00:00', time() - 86400 * 30);
		$list_channel = Channel::where('status', 1)->column('name', 'id');
		$list_order = [];
		$list_fee = [];
		$months = [];
		$title = [];
		$special_data = [];
		for ($i = 0; $i < 31; $i++) {
			$special_data[] = 0;
			$months[] = [date('Y-m-d 00:00:00', strtotime($last_30_begin) + $i * 86400), date('Y-m-d 23:59:59', strtotime($last_30_begin) + $i * 86400)];
			$title[] = date('Y-m-d', strtotime($last_30_begin) + $i * 86400);
		}
		//echart 表格数据
		foreach ($list_channel as $key => $value) {
			$list_order[$key]['name'] = '订单：' . $list_channel[$key] ?? '';
			$list_fee[$key]['name'] = '费用：' . $list_channel[$key] ?? '';
			$list_order[$key]['smooth'] = true;
			$list_fee[$key]['smooth'] = true;
			$list_order[$key]['type'] = 'line';
			$list_fee[$key]['type'] = 'line';
			$list_order[$key]['yAxisIndex'] = 0;
			$list_fee[$key]['yAxisIndex'] = 1;
			$list_order[$key]['channel_id'] = $key;
			$list_fee[$key]['channel_id'] = $key;
			$list_order[$key]['lineStyle'] = ['color' => 'red'];
			$list_fee[$key]['lineStyle'] = ['color' => 'green'];
			foreach ($months as $val) {
				$temp = FinanceStatistics::field('sum(success_order) as total_order,sum(total_fee) as total_fee')
					->where('date', 'between', $val)
					->where('channel_id', $key)
					->where('sub_business_id', 0)
					->select()
					->toArray();
				$list_order[$key]['data'][] = $temp[0]['total_order'] ?? 0;
				$list_fee[$key]['data'][] = $temp[0]['total_fee'] ?? 0;
			}
		}
		$list_order = array_values($list_order);
		$list_fee = array_values($list_fee);
		$list = array_merge($list_order, $list_fee);
		$show_list = [];
		foreach ($list as $key => &$value) {
			if ($value['data'] == $special_data) {
				unset($list[$key]);
			}
			if (in_array($value['name'], ['订单：支付宝小荷包', '费用：支付宝小荷包'])) {
				$show_list[] = $value;
			}
		}
		$list = array_values($list);

		//table 表格数据
		$temp = FinanceStatistics::field('sum(success_order) as total_order,sum(total_fee) as total_fee,channel_id')
			->where('date', '>=', $last_30_begin)
			->where('sub_business_id', 0)
			->where('channel_id', 'in', array_keys($list_channel))
			->having('total_order', '>', 0)
			->group('channel_id')
			->order('total_order', 'desc')
			->select()
			->toArray();
		$tableHeader = [];
		$tableHeader[] = ['field' => 'x_type', 'channel' => '类型', 'channel_id' => 0, 'class' => ''];
		$temp_table_order = ['x_type' => '订单'];
		$temp_table_fee = ['x_type' => '金额'];
		foreach ($temp as $key => $val) {
			$temp_table_order['x' . $val['channel_id']] = $val['total_order'];
			$temp_table_fee['x' . $val['channel_id']] = $val['total_fee'];
			if ($val['channel_id'] == 106) {
				$tableHeader[] = ['field' => 'x' . $val['channel_id'], 'channel' => $list_channel[$val['channel_id']] ?? '', 'channel_id' => $val['channel_id'], 'class' => 'selectedChannel'];
			} else {
				$tableHeader[] = ['field' => 'x' . $val['channel_id'], 'channel' => $list_channel[$val['channel_id']] ?? '', 'channel_id' => $val['channel_id'], 'class' => ''];
			}
		}
		$tableData = [$temp_table_order, $temp_table_fee];

		$data = [
			'show_list' => $show_list,
			'tableHeader' => $tableHeader,
			'tableData' => $tableData,
			'title' => $title,
			'list' => $list
		];
		return $this->returnData($data);
	}

	/**
	 * 本月商户数据
	 */
	public function month_business_data()
	{
		$last_30_begin = date('Y-m-d 00:00:00', time() - 86400 * 30);
		$list_business = Business::where('status', 'in', [1, 4])->column('id');
		$list_order = [];
		$list_fee = [];
		$months = [];
		$title = [];
		$special_data = [];
		for ($i = 0; $i < 31; $i++) {
			$special_data[] = 0;
			$months[] = [date('Y-m-d 00:00:00', strtotime($last_30_begin) + $i * 86400), date('Y-m-d 23:59:59', strtotime($last_30_begin) + $i * 86400)];
			$title[] = date('Y-m-d', strtotime($last_30_begin) + $i * 86400);
		}
		//echart 表格数据
		foreach ($list_business as $key => $value) {
			if ($value == 10301)
			{
				continue;
			}

			$list_order[$key]['name'] = '订单：' . $value;
			$list_fee[$key]['name'] = '费用：' . $value;
			$list_order[$key]['smooth'] = true;
			$list_fee[$key]['smooth'] = true;
			$list_order[$key]['type'] = 'line';
			$list_fee[$key]['type'] = 'line';
			$list_order[$key]['yAxisIndex'] = 0;
			$list_fee[$key]['yAxisIndex'] = 1;
			$list_order[$key]['business_id'] = $value;
			$list_fee[$key]['business_id'] = $value;
			$list_order[$key]['lineStyle'] = ['color' => 'red'];
			$list_fee[$key]['lineStyle'] = ['color' => 'green'];
			foreach ($months as $val) {
				$temp = FinanceStatistics::field('sum(success_order) as total_order,sum(total_fee) as total_fee')
					->where('date', 'between', $val)
					->where('business_id', $value)
					->where('sub_business_id', 0)
					->select()
					->toArray();
				$list_order[$key]['data'][] = $temp[0]['total_order'] ?? 0;
				$list_fee[$key]['data'][] = $temp[0]['total_fee'] ?? 0;
			}
		}
		$list_order = array_values($list_order);
		$list_fee = array_values($list_fee);
		$list = array_merge($list_order, $list_fee);
		//table 表格数据
		$temp = FinanceStatistics::field('sum(success_order) as total_order,sum(total_fee) as total_fee,business_id')
			->where('date', '>=', $last_30_begin)
			->where('sub_business_id', 0)
			->where('business_id', 'in', $list_business)
			->having('total_order', '>', 0)
			->group('business_id')
			->order('total_order', 'desc')
			->select()
			->toArray();
		$big_business_id = $temp[0]['business_id'] ?? 0;
		$show_list = [];
		foreach ($list as $key => &$value) {
			if ($value['data'] == $special_data) {
				unset($list[$key]);
			}
			if (in_array($value['business_id'], [$big_business_id])) {
				$show_list[] = $value;
			}
		}
		$list = array_values($list);
		$tableHeader = [];
		$tableHeader[] = ['field' => 'x_type', 'business' => '类型', 'business_id' => 0, 'class' => ''];
		$temp_table_order = ['x_type' => '订单'];
		$temp_table_fee = ['x_type' => '金额'];
		foreach ($temp as $key => $val) {
			if ($val['business_id'] == 10301)
			{
				continue;
			}

			$temp_table_order['x' . $val['business_id']] = $val['total_order'];
			$temp_table_fee['x' . $val['business_id']] = $val['total_fee'];
			if ($val['business_id'] == $big_business_id) {
				$tableHeader[] = ['field' => 'x' . $val['business_id'], 'business' => $val['business_id'], 'business_id' => $val['business_id'], 'class' => true];
			} else {
				$tableHeader[] = ['field' => 'x' . $val['business_id'], 'business' => $val['business_id'], 'business_id' => $val['business_id'], 'class' => false];
			}
		}
		$tableData = [$temp_table_order, $temp_table_fee];
		$data = [
			'show_list' => $show_list,
			'tableHeader' => $tableHeader,
			'tableData' => $tableData,
			'title' => $title,
			'list' => $list
		];
		return $this->returnData($data);
	}

	/**
	 * 本月商户数据
	 */
	public function month_all_business_data()
	{
		$last_30_begin = date('Y-m-d 00:00:00', time() - 86400 * 30);
		$list_order = [];
		$list_fee = [];
		$months = [];
		$title = [];
		for ($i = 0; $i < 31; $i++) {
			$months[] = [date('Y-m-d 00:00:00', strtotime($last_30_begin) + $i * 86400), date('Y-m-d 23:59:59', strtotime($last_30_begin) + $i * 86400)];
			$title[] = date('Y-m-d', strtotime($last_30_begin) + $i * 86400);
		}
		$temp = FinanceStatistics::field('sum(success_order) as total_order,business_id')
			->where('date', '>=', $last_30_begin)
			->where('business_id', '<>', 10301)
			->where('sub_business_id', 0)
			->group('business_id')
			->having('total_order', '>', 0)
			->order('total_order', 'desc')
			->select()
			->toArray();
		$list_business = array_column($temp, 'business_id');
		$selected = [];
		//echart 表格数据
		foreach ($list_business as $key => $value) {
			if ($key == 0) {
				$selected['订单：' . $value] = true;
				$selected['费用：' . $value] = true;
			} else {
				$selected['订单：' . $value] = false;
				$selected['费用：' . $value] = false;

			}
			$list_order[$key]['name'] = '订单：' . $value;
			$list_fee[$key]['name'] = '费用：' . $value;
			$list_order[$key]['smooth'] = true;
			$list_fee[$key]['smooth'] = true;
			$list_order[$key]['type'] = 'line';
			$list_fee[$key]['type'] = 'line';
			$list_order[$key]['yAxisIndex'] = 0;
			$list_fee[$key]['yAxisIndex'] = 1;
			$list_order[$key]['business_id'] = $value;
			$list_fee[$key]['business_id'] = $value;
			foreach ($months as $val) {
				$temp = FinanceStatistics::field('sum(success_order) as total_order,sum(total_fee) as total_fee')
					->where('date', 'between', $val)
					->where('business_id', $value)
					->where('sub_business_id', 0)
					->select()
					->toArray();
				$list_order[$key]['data'][] = $temp[0]['total_order'] ?? 0;
				$list_fee[$key]['data'][] = $temp[0]['total_fee'] ?? 0;
			}
		}
		$list_order = array_values($list_order);
		$list_fee = array_values($list_fee);
		$list = [];
		foreach ($list_order as $key => $val) {
			$list[] = $val;
			if (isset($list_fee[$key])) {
				$list[] = $list_fee[$key];
			}
		}
		$list = array_values($list);
		$data = [
			'title' => $title,
			'list' => $list,
			'selected' => $selected
		];
		return $this->returnData($data);
	}
}
