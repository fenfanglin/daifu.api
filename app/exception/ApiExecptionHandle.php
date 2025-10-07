<?php
namespace app\exception;

use think\facade\Env;
use think\exception\Handle;
use app\exception\HttpExceptions;
use think\Response;
use Throwable;

class ApiExecptionHandle extends Handle
{
	private $msg = '未知错误';
	private $httpCode = 500;
	private $errorCode = 400;
	
	public function render($request, Throwable $e): Response
	{
		// // 判断在`.env`里面是否开始了调试，开启了调试就原样返回，关闭了调试就返回自定义的json格式的错误信息
		// if (Env::get('APP_DEBUG') == 1)
		// {
		// 	return parent::render($request, $e);
		// }
		
		$this->reportException($request, $e);
		
		$this->msg = $e->getMessage() ?? $this->msg;
		
		// HttpExceptions是继承同级目录下HttpExceptions，代码在下方
		if ($e instanceof HttpExceptions)
		{
			$this->httpCode = $e->getStatusCode() ?? $this->httpCode;
		}
		
		// var_dump($this->httpCode, $e->getCode(), $this->errorCode);
		
		$this->errorCode = $e->getCode() ?? $this->errorCode;
		
		// 关闭调试，系统错误显示404
		if ($this->errorCode == 0 && config('app.app_debug') == false)
		{
			header('HTTP/1.1 404 Not Found');
			exit();
		}
		
		$result_data = [
			'code' => $this->errorCode,
			'msg' => $this->msg,
		];
		return json($result_data, $this->httpCode);
		
		// // 其他错误交给系统处理
		// return parent::render($request, $e);
	}
	
	// 记录exception到日志
	private function reportException($request, Throwable $e):void
	{
		$errorStr = 'url:' . $request->host() . $request->url() . "\n";
		$errorStr .= 'file:' . $e->getFile() . "\n";
		$errorStr .= 'line:' . $e->getLine() . "\n";
		$errorStr .= $e->getTraceAsString();
		
		trace($errorStr, 'error');
	}
}