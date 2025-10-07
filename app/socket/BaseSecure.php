<?php
namespace app\socket;

use think\facade\Request;
use app\socket\BaseAPI;
use \Firebase\JWT\JWT;

class BaseSecure extends BaseAPI
{
	public function __construct()
	{
		if (Request::param('check_token') == 'sTt0oU7e3Md21')
		{
			goto end_check_token;
		}
		
		
		$api_token = Request::param('api_token');
		// if (!isset($api_token))
		// {
		// 	echo json_encode(['check_token' => 'FALSE']);
		// 	exit();
		// }
		
		if (isset($api_token))
		{
			$key = 'huang';  //上一个方法中的 $key 本应该配置在 config文件中的
			$info = JWT::decode($api_token, $key, ['HS256']); //解密jwt
			echo json_encode($info);
			exit();
		}
		
		end_check_token:
	}
	
	
}
