<?php

return [
	// 默认使用的数据库连接配置
	'default' => env('database.driver', 'mysql'),

	// 自定义时间查询规则
	'time_query_rule' => [],

	// 自动写入时间戳字段
	// true为自动识别类型 false关闭
	// 字符串则明确指定时间字段类型 支持 int timestamp datetime date
	'auto_timestamp' => true,

	// 时间字段取出后的默认时间格式
	'datetime_format' => 'Y-m-d H:i:s',

	// 数据库连接配置信息
	'connections' => [
		'mysql' => [
			// 数据库类型
			'type' => env('database.type', 'mysql'),
			// 服务器地址
			'hostname' => env('database.hostname', '127.0.0.1'),
			// 数据库名
			'database' => env('database.database', 'pay'),
			// 用户名
			'username' => env('database.username', 'root'),
			// 密码
			'password' => env('database.password', ''),
			// 端口
			'hostport' => env('database.hostport', '3306'),
			// 数据库连接参数
			'params' => [],
			// 数据库编码默认采用utf8
			'charset' => env('database.charset', 'utf8mb4'),
			// 数据库表前缀
			'prefix' => env('database.prefix', 'pay_'),

			// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
			'deploy' => 0,
			// 数据库读写是否分离 主从式有效
			'rw_separate' => false,
			// 读写分离后 主服务器数量
			'master_num' => 1,
			// 指定从服务器序号
			'slave_no' => '',
			// 是否严格检查字段是否存在
			'fields_strict' => true,
			// 是否需要断线重连
			'break_reconnect' => false,
			// 监听SQL
			'trigger_sql' => env('app_debug', true),
			// 开启字段缓存
			'fields_cache' => false,
			// 字段缓存路径
			'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
		],

		// 更多的数据库配置信息
		'mysql_relation' => [
			// 数据库类型
			'type' => env('database_relation.type', 'mysql'),
			// 服务器地址
			'hostname' => env('database_relation.hostname', '127.0.0.1'),
			// 数据库名
			'database' => env('database_relation.database', 'pay'),
			// 用户名
			'username' => env('database_relation.username', 'root'),
			// 密码
			'password' => env('database_relation.password', ''),
			// 端口
			'hostport' => env('database_relation.hostport', '3306'),
			// 数据库连接参数
			'params' => [],
			// 数据库编码默认采用utf8
			'charset' => env('database_relation.charset', 'utf8mb4'),
			// 数据库表前缀
			'prefix' => env('database_relation.prefix', 'pay_'),
			// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
			'deploy' => 0,
			// 数据库读写是否分离 主从式有效
			'rw_separate' => false,
			// 读写分离后 主服务器数量
			'master_num' => 1,
			// 指定从服务器序号
			'slave_no' => '',
			// 是否严格检查字段是否存在
			'fields_strict' => true,
			// 是否需要断线重连
			'break_reconnect' => false,
			// 监听SQL
			'trigger_sql' => env('app_debug', true),
			// 开启字段缓存
			'fields_cache' => false,
			// 字段缓存路径
			'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
		],
		'mysql_jqk' => [
			// 数据库类型
			'type' => env('database_jqk.type', 'mysql'),
			// 服务器地址
			'hostname' => env('database_jqk.hostname', '127.0.0.1'),
			// 数据库名
			'database' => env('database_jqk.database', 'pay'),
			// 用户名
			'username' => env('database_jqk.username', 'root'),
			// 密码
			'password' => env('database_jqk.password', ''),
			// 端口
			'hostport' => env('database_jqk.hostport', '3306'),
			// 数据库连接参数
			'params' => [],
			// 数据库编码默认采用utf8
			'charset' => env('database_jqk.charset', 'utf8mb4'),
			// 数据库表前缀
			'prefix' => env('database_jqk.prefix', 'pay_'),
			// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
			'deploy' => 0,
			// 数据库读写是否分离 主从式有效
			'rw_separate' => false,
			// 读写分离后 主服务器数量
			'master_num' => 1,
			// 指定从服务器序号
			'slave_no' => '',
			// 是否严格检查字段是否存在
			'fields_strict' => true,
			// 是否需要断线重连
			'break_reconnect' => false,
			// 监听SQL
			'trigger_sql' => env('app_debug', true),
			// 开启字段缓存
			'fields_cache' => false,
			// 字段缓存路径
			'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
		],
		'mysql_jqk_base' => [
			// 数据库类型
			'type' => env('database_jqk_base.type', 'mysql'),
			// 服务器地址
			'hostname' => env('database_jqk_base.hostname', '127.0.0.1'),
			// 数据库名
			'database' => env('database_jqk_base.database', 'pay'),
			// 用户名
			'username' => env('database_jqk_base.username', 'root'),
			// 密码
			'password' => env('database_jqk_base.password', ''),
			// 端口
			'hostport' => env('database_jqk_base.hostport', '3306'),
			// 数据库连接参数
			'params' => [],
			// 数据库编码默认采用utf8
			'charset' => env('database_jqk_base.charset', 'utf8mb4'),
			// 数据库表前缀
			'prefix' => env('database_jqk_base.prefix', 'pay_'),
			// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
			'deploy' => 0,
			// 数据库读写是否分离 主从式有效
			'rw_separate' => false,
			// 读写分离后 主服务器数量
			'master_num' => 1,
			// 指定从服务器序号
			'slave_no' => '',
			// 是否严格检查字段是否存在
			'fields_strict' => true,
			// 是否需要断线重连
			'break_reconnect' => false,
			// 监听SQL
			'trigger_sql' => env('app_debug', true),
			// 开启字段缓存
			'fields_cache' => false,
			// 字段缓存路径
			'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
		],
	],
];
