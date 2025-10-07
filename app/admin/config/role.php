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
	// 白名单（不用检查权限）
	'white_list' => [
		'index:index', // 首页
		'index:get_notice', // 系统提醒
		'index:get_router', // 获取前端菜单
		'index:get_userinfo', // 获取账号信息
		'index:userinfo', // 获取账号信息
		'index:get_sign', //
		'option:role', //列出角色
		'option:business', //列出商户
		'option:channel', //列出通道
		'option:system_bank', //列出系统银行卡
		'option:get_usdt_rate', //获取usdt汇率
	],
	'routers' => [
		'system' => [
			'title' => '系统设置',
			'icon' => 'icon-bianji',
			'hidden' => false,
			'children' => [
				'setting' => [
					'title' => '基本设置',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'setting:view',
						'setting:save',
					],
					'permission' => [

					],
				],
				'log' => [
					'title' => '操作记录',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'log:list',
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
					'noCache' => true,
					'white_list' => [
						'order:list',
						'order:view',
					],
					'permission' => [
						'order:export' => '导出',
						'order:resend_notify' => '补发通知',
					],
				],
			],
		],
		'business' => [
			'title' => '商户管理',
			'icon' => 'icon-user-outlined',
			'hidden' => false,
			'children' => [
				'list_agent' => [
					'title' => '全部代理',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'business_agent:list',
						'business_agent:view', //查看编辑
						'business_agent:view_channel', //查看通道
						// 'business_agent:view_recharge', //查看充值
					],
					'permission' => [
						'business_agent:save:add' => '添加',
						'business_agent:save:edit' => '编辑',
						'business_agent:save_channel' => '通道',
						'business_agent:save_recharge' => '充值',
						'business_agent:login' => '登录',
						'business_agent:change_password' => '密码',
						// 'business_agent:delete' => '删除',
						'business_agent:enable' => '启用',
						'business_agent:disable' => '禁用',

						'system_relation:get_bind_jqk' => '获取绑定JQK',
						'system_relation:bind_jqk' => '绑定JQK',
						'system_relation:unbind_jqk' => '解绑JQK',
					],
				],
				'list_business_card' => [
					'title' => '全部工作室',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'business_card:list',
						'business_card:view', //查看编辑
						// 'business_card:view_channel', //查看通道
					],
					'permission' => [
						// 'business_card:save:add' => '添加',
						'business_card:save:edit' => '编辑',
						// 'business_card:save_channel' => '通道',
						// 'business_card:save_recharge' => '充值',
						'business_card:login' => '登录',
						'business_card:change_password' => '密码',
						// 'business_card:delete' => '删除',
						'business_card:enable' => '启用',
						'business_card:disable' => '禁用',
					],
				],
				'list_sub_business' => [
					'title' => '全部商户',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'business_sub:list',
						'business_sub:view', //查看编辑
						'business_sub:view_channel', //查看通道
					],
					'permission' => [
						// 'business_sub:save:add' => '添加',
						'business_sub:save:edit' => '编辑',
						'business_sub:save_channel' => '通道',
						// 'business_sub:save_recharge' => '充值',
						'business_sub:login' => '登录',
						'business_sub:change_password' => '密码',
						// 'business_sub:delete' => '删除',
						'business_sub:enable' => '启用',
						'business_sub:disable' => '禁用',
					],
				],
			],
		],
		'finance' => [
			'title' => '财务管理',
			'icon' => 'icon-zhexiantu',
			'hidden' => false,
			'children' => [
				'transaction_rank' => [
					'title' => '交易排行榜',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'finance:transaction_rank',
					],
					'permission' => [
					],
				],
				'list_recharge' => [
					'title' => '充值记录',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'recharge:list',
						'recharge:view',
					],
					'permission' => [
						'recharge:export' => '导出',
					],
				],
				'list_money_log' => [
					'title' => '资金明细',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'money_log:list',
						'money_log:view',
					],
					'permission' => [
						'money_log:export' => '导出',
					],
				],
				'business_data' => [
					'title' => '今日商户数据',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'finance:business_data',
					],
					'permission' => [
					],
				],
				'week_data' => [
					'title' => '本周数据',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'finance:week_data',
					],
					'permission' => [
					],
				],
				'month_data' => [
					'title' => '本月数据',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'finance:month_data',
					],
					'permission' => [
					],
				],
				'month_business_data' => [
					'title' => '本月商户数据',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'finance:month_business_data',
					],
					'permission' => [
					],
				],
				'month_all_data' => [
					'title' => '本月数据',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'finance:month_all_channel_data',
						'finance:month_all_business_data',
					],
					'permission' => [
					],
				],
			],
		],
		'admin' => [
			'title' => '后台管理',
			'icon' => 'icon-mima',
			'hidden' => false,
			'children' => [
				'list' => [
					'title' => '管理员列表',
					'hidden' => false,
					'noCache' => true,
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
				'role' => [
					'title' => '角色管理',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'role:list',
						'role:view',
						'role:get_center_full_permission',
					],
					'permission' => [
						'role:save:add' => '添加',
						'role:save:edit' => '编辑',
						'role:delete' => '删除',
						'role:enable' => '启用',
						'role:disable' => '禁用',
					],
				],
				'demo_order' => [
					'title' => '全部订单',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'demo_order:list',
						'demo_order:view',
					],
					'permission' => [
						// 'demo_order:save_detail' => '手动添加',
						'demo_order:save_simple' => '快速添加',
					],
				],
			],
		],
	],
];
