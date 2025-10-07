<?php
/**
 * 路由与权限设置
 * 路由规则说明:
 * 		- activity: 1级菜单，对应前端的目录
 * 		- list: 2级菜单，对应前端目录的文件
 * 		- activity/list: 前端页面路径
 * 权限规则说明:
 * 		- white_list: 白名单，只要选择页面就默认有这些权限，格式controller:action:sub_action
 * 		- permission: 权限，有选择才有权限，格式controller:action:sub_action（默认检查controller:action，sub_action在action内部检查）
 * 		- action: 只允许export_data格式，不允许exportData格式
 * 		- sub_action: action方法内部权限
 * 数据库保存格式:
 *		'activity' => [
 *			'list' => ['activity:save:add' => [], 'activity:save:edit' => [], 'activity:export' => []],
 *			'edit' => ['activity:save:add' => [], 'activity:save:edit' => []],
 *		],
 *		'attend' => [
 *			'list' => [],
 *			'edit' => [],
 *			'detail' => [],
 *		],
 *		'admin' => [
 *			'list' => [],
 *			'role' => [],
 *		],
 */
return [
	'white_list' => [
		'index:index', // 首页
		'index:get_router', // 获取前端菜单
		'index:get_userinfo', // 获取账号信息
		'index:userinfo', // 获取账号信息
		'option:role', //列出角色
		'option:card_business', //列出卡商
		'option:account_type', //列出收款方式
		'option:system_bank', //列出系统银行卡
		'option:get_usdt_rate', //获取usdt实时费率
		// 'common:upload', //上传文件
	],
	'routers' => [
		'user' => [
			'title' => '账户管理',
			'icon' => 'icon-user-outlined',
			'hidden' => false,
			'children' => [
				'setting' => [
					'title' => '基本设置',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'user:view_setting',
						'user:save_setting',
					],
					'permission' => [

					],
				],
				'change_password' => [
					'title' => '后台登录密码',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'user:change_password',
					],
					'permission' => [

					],
				],
				'google_auth' => [
					'title' => 'Google身份验证',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'user:google_auth_view',
						'user:google_auth_bind',
						'user:google_auth_unbind',
					],
					'permission' => [

					],
				],
			],
		],
		'order' => [
			'title' => '订单管理',
			'icon' => 'icon-gongdan',
			'hidden' => false,
			'children' => [
				'list_all' => [
					'title' => '全部订单',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'order:list',
						'order:view',
					],
					'permission' => [
						'order:add' => '添加',
						'order:export' => '导出',
						'order:set_remark' => '备注',
						'order:import' => '导入订单',
						'order:import_delete' => '删除导入订单',
					],
				],
			],
		],
		'finance' => [
			'title' => '财务管理',
			'icon' => 'icon-zhexiantu',
			'hidden' => false,
			'children' => [
				'list_withdraw' => [
					'title' => '充值记录',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'withdraw:list',
						'withdraw:view',
						'withdraw:get_setting',
						'withdraw:submit', //提交
					],
					'permission' => [
						// 'withdraw:verify' => '审核',
						'withdraw:export' => '导出',
					],
				],
				'list_withdraw_log' => [
					'title' => '资金明细',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'withdraw_log:list',
						'withdraw_log:view',
					],
					'permission' => [
						'withdraw_log:export' => '导出',
					],
				],
			],
		],
		'document' => [
			'title' => '对接文档',
			'icon' => 'icon-mima',
			'hidden' => false,
			'children' => [
				'setting' => [
					'title' => '商户密钥',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'index:userinfo',
						'demo:pay',
					],
					'permission' => [

					],
				],
				'api_create' => [
					'title' => '统一下单',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [

					],
					'permission' => [

					],
				],
				'api_notify' => [
					'title' => '异步通知',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [

					],
					'permission' => [

					],
				],
				'api_query' => [
					'title' => '订单查询',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [

					],
					'permission' => [

					],
				],
				'api_sign' => [
					'title' => '签名算法',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [

					],
					'permission' => [

					],
				],
			],
		],
	],
];
