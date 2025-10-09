<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class Demo1Controller extends BaseController
{
	public function query()
	{
		$config = [
			'mchid' => 'M1759755553',
			'appid' => '68e3bd22e4b02b5ff8bc1d63',
			'key' => 'ppp42ij2dzzbd5kx1bevv0m15mx8lvtchwqvoweu5u55qonrgvf8sp4d8q448k5ve8bxm2bhej6dmk8jffazipkhj62zaftac81ted7lfg5zt36gjzyrvzz6kunzac46',
		];

		$service = new \app\service\api\ShundatongService($config);

		// $trans_id = 'T1975220023580078082';
		// $order_no = 'M17597640259103567';
		$trans_id = 'T1975532107903709185';
		$order_no = 'DM2025100720021200295';

		$res = $service->query($trans_id, $order_no);

		dd($res);
	}

	public function create()
	{
		$config = [
			'mchid' => 'M1759755553',
			'appid' => '68e3bd22e4b02b5ff8bc1d63',
			'key' => 'ppp42ij2dzzbd5kx1bevv0m15mx8lvtchwqvoweu5u55qonrgvf8sp4d8q448k5ve8bxm2bhej6dmk8jffazipkhj62zaftac81ted7lfg5zt36gjzyrvzz6kunzac46',
		];

		$service = new \app\service\api\ShundatongService($config);

		$data = [
			'out_trade_no' => 'DM' . date('YmdHis') . '00' . mt_rand(100, 999),
			'amount' => '10',

			'account_type' => 1,
			'account' => '6216710470059167678',
			'account_name' => '万军杰',
			'bank' => '中国农业银行',

			// 'account_type' => 3,
			// 'account' => '15736338534',
			// 'account_name' => '万军杰',
		];

		$res = $service->create($data);

		dd($res);
	}
}