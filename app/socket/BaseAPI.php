<?php
namespace app\socket;

class BaseAPI
{
	/**
	* 按综合方式输出通信数据
	* @param integer $code 状态码
	* @param string $message 提示信息
	* @param array $data 数据
	*/
	public function show($code, $message = '', $data = array())
	{
		if (!is_numeric($code))
		{
			return '';
		}
		
		$return_data = array(
			'code'	=> $code,
			'msg'	=> $message,
			'data'	=> $data,
		);
		
		return json($return_data);
	}
}
