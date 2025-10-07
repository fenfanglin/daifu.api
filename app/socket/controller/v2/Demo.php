<?php
namespace app\api\controller\frontend;

use think\Validate;

class Demo extends Validate
{
	public function index()
	{
		$this->rule = [
			'name'	=> 'require|max:25',
			'age'	=> 'require|number|between:1,120',
			'phone'	=> 'require|max:11|/^1[3-8]{1}[0-9]{9}$/',
			'email'	=> 'email',
		];
		
		$this->message = [
			'name.require'	=> 'name必须',
			'name.max'		=> 'name最多不能超过25个字符',
			'age.number'	=> 'age必须是数字',
			'age.between'	=> 'age必须在1~120之间',
			'email'			=> 'email格式错误',
		];
		
		$data = [
			'name'	=> 'thinkphp',
			'age'	=> '12',
			'phone'	=> '1378883069',
			'email'	=> 'thinkphp@qq.com',
		];
		
		$result = $this->check($data);
		
		if (!$result)
		{
			return json(['error_code' => 1, 'error_smg' => $this->getError()]);
		}
		
		return json(['msg' => 'success']);
	}
}
