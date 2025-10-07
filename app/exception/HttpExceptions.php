<?php
namespace app\exception;

use Exception;

class HttpExceptions extends \RuntimeException
{
	private $statusCode;
	private $headers;
	
	//主要是重构$code前提，$previous和 $headers在后面方便调用
	public function __construct(int $statusCode, string $message = '', $code = 0, Exception $previous = null, array $headers = [])
	{
		$this->statusCode = $statusCode;
		$this->headers	= $headers;
		
		parent::__construct($message, $code, $previous);
	}
	
	public function getStatusCode()
	{
		return $this->statusCode;
	}
	
	public function getHeaders()
	{
		return $this->headers;
	}
}