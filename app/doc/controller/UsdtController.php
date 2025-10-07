<?php
namespace app\doc\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;
use app\service\UsdtService;

class UsdtController extends BaseController
{
	// /**
	//  * 获取Usdt汇率
	//  */
	// public function autorate()
	// {
	// 	$res = UsdtService::getUsdtRate();

	// 	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	// 	exit;
	// }


	public function test_redis()
	{
		// $this->global_redis->set('test_key', date('Y-m-d H:i:s'));

		$data = date('Y-m-d H:i:s');

		$this->global_redis->rpush('alipay_transfer_qrcode_queue', $data);

		echo $data;
		exit;
	}
}
