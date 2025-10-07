<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
	// 应用地址
	'app_host'			=> env('app.host', ''),
	// 调试
	'app_debug'			=> env('APP_DEBUG', ''),
	// 应用的命名空间
	'app_namespace'		=> '',
	// 是否启用路由
	'with_route'		=> true,
	// 是否启用事件
	'with_event'		=> true,
	// 默认时区
	'default_timezone'	=> 'Asia/Shanghai',

	// 默认应用
	'default_app'		=> 'api',
	// 开启应用快速访问
	'app_express'		=> true,
	// 应用映射（自动多应用模式有效）
	'app_map'			=> [],
	// 域名绑定（自动多应用模式有效）
	'domain_bind'		=> [],
	// 禁止URL访问的应用列表（自动多应用模式有效）
	'deny_app_list'		=> [],

	// 异常页面的模板文件
	'exception_tmpl'	=> app()->getThinkPath() . 'tpl/think_exception.tpl',
	
	// 自动多应用模式
	'auto_multi_app'	=> true,
	
	// 错误显示信息,非调试模式有效
	'error_message'		=> '页面错误！请稍后再试～',
	// 显示错误信息
	'show_error_msg'	=> true,
	
	
	'app_key'		=> 'ahOZctlRdvmM8ZitX3JpbJ2Cf53MaKxy',
	
	'crypt_method'	=> 'IDEA-CBC',
	'crypt_hash'	=> 'A39=E@rngB8*ecH!swesp7c-Ph@pHz0x',
	'crypt_iv'		=> '=9E3*@H!',
	
	'crypt_js_key'	=> 'Rk8YKkoQzl3P5HBs',
	'crypt_js_iv'	=> 'A4ODY0MI3LU5F0BU',
	
	// 调试
	'api_url'			=> env('API.API_URL', ''),
	'api_url_internal'	=> env('API.API_URL_INTERNAL', ''),
	'order_url'			=> env('ORDER.ORDER_URL', ''),
];
