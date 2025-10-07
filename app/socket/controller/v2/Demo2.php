<?php
namespace app\api\controller\frontend;

use app\api\BaseSecure;

class Demo2 extends BaseSecure
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
	
	public function test()
	{
		return $this->show(1, input('param.value'));
	}
}
