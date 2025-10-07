<?php
namespace app\api\controller\frontend;

use app\api\BaseAPI;
use \Firebase\JWT\JWT;

class Index extends BaseAPI
{
	public function check_data()
	{
		$data = [
			'name'	=> 'think',
			'age'	=> '12a',
			'phone'	=> '13788830695',
			'email'	=> 'thinkphp@qq.com',
		];
		
		// ---------------------------------------------------------------------------------
		// 验证数据
		$rule = [
			'name'	=> 'require|max:25',
			'age'	=> 'require|number|between:1,120',
			'phone'	=> 'require|max:11|/^1[3-8]{1}[0-9]{9}$/',
			'email'	=> 'email',
		];
		
		$validate = new \think\Validate();
 		
		if (!$validate->check($data, $rule))
		{
			return $this->show(400, $validate->getError());
		}
		// ---------------------------------------------------------------------------------
		
		
		return $this->show(1, 'ok');
	}
	
	public function get_token()
	{
		$key = 'huang';  //这里是自定义的一个随机字串，应该写在config文件中的，解密时也会用，相当	于加密中常用的 盐  salt
		$token = [
			'iss'=>'',  //签发者 可以为空
			'aud'=>'', //面象的用户，可以为空
			'iat' => time(), //签发时间
			'nbf' => time()+3, //在什么时候jwt开始生效  （这里表示生成100秒后才生效）
			'exp' => time()+7200, //token 过期时间
			'uid' => 123 //记录的userid的信息，这里是自已添加上去的，如果有其它信息，可以再添加数组的键值对
		];
		
		$jwt = JWT::encode($token, $key, 'HS256'); //根据参数生成了 token
		
		return json(['token' => $jwt]);
	}
}
