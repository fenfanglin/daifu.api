<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$sub_domain = explode('.', $_SERVER['HTTP_HOST'])[0];
if (in_array($sub_domain, ['dfsysapi']))
{
	define('APP_NAME', 'admin');

	// 执行admin应用
	$response = $http->name('admin')->run();
}
elseif (in_array($sub_domain, ['dfagentapi', 'dfcardapi', 'dfbizapi']))
{
	define('APP_NAME', 'business');

	// 执行business应用
	$response = $http->name('business')->run();
}
elseif (in_array($sub_domain, ['dfapi']))
{
	define('APP_NAME', 'pay');

	// 执行pay应用
	$response = $http->name('pay')->run();
}
elseif (in_array($sub_domain, ['dfdoc']))
{
	define('APP_NAME', 'doc');

	// 执行doc应用
	$response = $http->name('doc')->run();
}
else
{
	header('HTTP/1.1 403 Forbidden');
	exit();
}

$response->send();

$http->end($response);
