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
		'option:channel', //列出通道
		'option:system_bank', //列出系统银行卡
	],
	'routers' => [
		'system' => [
			'title' => '账户管理',
			'icon' => 'icon-user-outlined',
			'hidden' => false,
			'children' => [
				'setting' => [
					'title' => '基本设置',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'setting:save',
					],
					'permission' => [
						
					],
				],
				'change_password' => [
					'title' => '登录密码',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'admin:change_password',
					],
					'permission' => [
						
					],
				],
				'google_auth' => [
					'title' => 'Google身份验证',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'google_auth:view',
						'google_auth:bind',
						'google_auth:unbind',
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
					'noCache' => false,
					'white_list' => [
						'order:list',
						'order:view',
					],
					'permission' => [
						'order:export' => '导出',
						'order:set_order_paid' => '设置为已支付',
					],
				],
				'list_success' => [
					'title' => '成功订单',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'order:list',
						'order:view',
					],
					'permission' => [
						'order:export' => '导出',
					],
				],
				'list_abnormal' => [
					'title' => '异常订单',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'order:list',
						'order:view',
					],
					'permission' => [
						'order:export' => '导出',
						'order:re_submit' => '补发订单',
					],
				],
				'list_sms' => [
					'title' => '短信消息记录',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'sms:list',
						'sms:view',
					],
					'permission' => [
						'sms:export' => '导出',
					],
				],
			],
		],
		'business' => [
			'title' => '卡商管理',
			'icon' => 'icon-user-outlined',
			'hidden' => false,
			'children' => [
				'list_all' => [
					'title' => '卡商管理',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'business:list',
						'business:view', //查看编辑
						// 'business:view_channel', //查看通道
						// 'business:view_recharge', //查看充值
					],
					'permission' => [
						'business:save:add' => '添加',
						'business:save:edit' => '编辑',
						'business:change_password' => '密码',
						'business:delete' => '删除',
						'business:enable' => '启用',
						'business:disable' => '禁用',
					],
				],
			],
		],
		'channel' => [
			'title' => '通道管理',
			'icon' => 'icon-yingyongguanli',
			'hidden' => false,
			'children' => [
				'list_bank' => [
					'title' => '银行卡管理',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel_web_bank:list',
						'channel_web_bank:view',
					],
					'permission' => [
						'channel_web_bank:save:add' => '添加',
						'channel_web_bank:save:edit' => '编辑',
						'channel_web_bank:delete' => '删除',
						'channel_web_bank:enable' => '启用',
						'channel_web_bank:disable' => '禁用',
					],
				],
				'list_usdt' => [
					'title' => 'USDT管理',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel_usdt:list',
						'channel_usdt:view',
					],
					'permission' => [
						'channel_usdt:save:add' => '添加',
						'channel_usdt:save:edit' => '编辑',
						'channel_usdt:delete' => '删除',
						'channel_usdt:enable' => '启用',
						'channel_usdt:disable' => '禁用',
					],
				],
				'list_rmb' => [
					'title' => '数字人民币',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel_rmb:list',
						'channel_rmb:view',
					],
					'permission' => [
						'channel_rmb:save:add' => '添加',
						'channel_rmb:save:edit' => '编辑',
						'channel_rmb:delete' => '删除',
						'channel_rmb:enable' => '启用',
						'channel_rmb:disable' => '禁用',
					],
				],
				'list_zfb' => [
					'title' => '支付宝收款码',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel_zfb:list',
						'channel_zfb:view',
					],
					'permission' => [
						'channel_zfb:save:add' => '添加',
						'channel_zfb:save:edit' => '编辑',
						'channel_zfb:delete' => '删除',
						'channel_zfb:enable' => '启用',
						'channel_zfb:disable' => '禁用',
					],
				],
			],
		],
		'admin' => [
			'title' => 'API管理',
			'icon' => 'icon-mima',
			'hidden' => false,
			'children' => [
				'list' => [
					'title' => 'API开发文档',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'admin:list',
						'admin:view',
						'role:list_option',
					],
					'permission' => [
						'admin:save:add' => '添加',
						'admin:save:edit' => '编辑',
						'admin:delete' => '删除',
						'admin:enable' => '启用',
						'admin:disable' => '禁用',
						'admin:export' => '导出',
					],
				],
			],
		],
	],
];