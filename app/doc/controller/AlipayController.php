<?php
namespace app\doc\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class AlipayController extends BaseController
{
	public function notify_url()
	{
		$get = input('get.');
		$post = input('post.');

		$data = [
			'get' => $get,
			'post' => $post,
		];

		Common::writeLog($data, 'notify_url');

		$params = input('post.');
		$secret_key = 'PmkcRKof0kxgMqcJKkIlH3ELi2P4Su30'; //10301商户密钥

		if ($params['sign'] == Common::createSign($params, $secret_key))
		{
			return 'success';
		}
		else
		{
			return 'fail';
		}
	}

	public function return_url()
	{
		$get = input('get.');
		$post = input('post.');

		$data = [
			'get' => $get,
			'post' => $post,
		];

		Common::writeLog($data, 'return_url');

		$params = input('post.');
		$secret_key = 'PmkcRKof0kxgMqcJKkIlH3ELi2P4Su30'; //10301商户密钥

		if ($params['sign'] == Common::createSign($params, $secret_key))
		{
			return 'success';
		}
		else
		{
			return 'fail';
		}
	}
}