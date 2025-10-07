<?php

namespace app\pay\controller;

use app\extend\common\Common;
use app\model\Business;
use app\model\Order;

class NotifyController extends AuthController
{
	public function notify()
	{
		$params = input('post.');
		$business = new Business();
		$business = $business->where('id', $params['mchid'])->find();
		$this->checkSign($params, $business->secret_key);
		return $this->returnSuccess('success');
	}

}
