<?php
namespace think;
/**
 * 命令行入口
 * 使用方式： php cli.php controller_action
 * 示例： php cli.php unfreeze_index
 * 也可简写为： php cli.php unfreeze
 */

if (PHP_SAPI != 'cli')
{
	die('cli only!');
}

$_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];

// 命令行入口文件
// 加载基础文件
require __DIR__ . '/vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

define('APP_NAME', 'cli');

// 执行cli应用
$response = $http->name('cli')->run();
// $response = $http->run();

$response->send();

$http->end($response);