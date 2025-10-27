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
		'channel' => [
			'title' => '通道管理',
			'icon' => 'icon-yingyongguanli',
			'hidden' => false,
			'children' => [
				'list_all' => [
					'title' => '三方通道管理',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel:list',
						'channel:view',
					],
					'permission' => [
						'channel:save:add' => '添加',
						'channel:save:edit' => '编辑',
						'channel:enable' => '启用',
						'channel:disable' => '禁用',
					],
				],
				/* 'list_system_bank' => [
																										'title' => '系统银行卡',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'system_bank:list',
																											'system_bank:view',
																										],
																										'permission' => [
																											'system_bank:save:add' => '添加',
																											'system_bank:save:edit' => '编辑',
																											'system_bank:delete' => '删除',
																											'system_bank:enable' => '启用',
																											'system_bank:disable' => '禁用',
																										],
																									],
																									'list_bank' => [
																										'title' => '银行卡',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_bank:list',
																											'channel_bank:view',
																											'channel_bank:list_channel',
																										],
																										'permission' => [
																											// 'channel_bank:save:add' => '添加',
																											// 'channel_bank:save:edit' => '编辑',
																											'channel_bank:enable' => '启用',
																											'channel_bank:disable' => '禁用',
																										],
																									],
																									'list_alipay' => [
																										'title' => '支付宝',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_alipay:list',
																											'channel_alipay:view',
																											'channel_alipay:list_channel',
																											'channel_alipay:view_api',
																										],
																										'permission' => [
																											// 'channel_alipay:save:add' => '添加',
																											// 'channel_alipay:save:edit' => '编辑',
																											'channel_alipay:enable' => '启用',
																											'channel_alipay:disable' => '禁用',
																										],
																									],
																									'list_agent' => [
																										'title' => '四方平台',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_agent:list',
																											'channel_agent:view',
																											'channel_agent:list_channel',
																										],
																										'permission' => [
																											// 'channel_agent:save:add' => '添加',
																											// 'channel_agent:save:edit' => '编辑',
																											'channel_agent:enable' => '启用',
																											'channel_agent:disable' => '禁用',
																										],
																									],
																									'list_wechat' => [
																										'title' => '微信通道',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_wechat:list',
																											'channel_wechat:view',
																											'channel_wechat:list_channel',
																										],
																										'permission' => [
																											// 'channel_wechat:save:add' => '添加',
																											// 'channel_wechat:save:edit' => '编辑',
																											'channel_wechat:enable' => '启用',
																											'channel_wechat:disable' => '禁用',
																										],
																									],
																									'list_rgcz' => [
																										'title' => '人工充值',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_rgcz:list',
																											'channel_rgcz:view',
																											'channel_rgcz:list_channel',
																										],
																										'permission' => [
																											// 'channel_rgcz:save:add' => '添加',
																											// 'channel_rgcz:save:edit' => '编辑',
																											'channel_rgcz:enable' => '启用',
																											'channel_rgcz:disable' => '禁用',
																										],
																									],
																									'list_pdddaifu' => [
																										'title' => '拼多多代付',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_pdddaifu:list',
																											'channel_pdddaifu:view',
																											'channel_pdddaifu:list_channel',
																										],
																										'permission' => [
																											// 'channel_pdddaifu:save:add' => '添加',
																											// 'channel_pdddaifu:save:edit' => '编辑',
																											'channel_pdddaifu:enable' => '启用',
																											'channel_pdddaifu:disable' => '禁用',
																										],
																									],
																									'list_douyin' => [
																										'title' => '抖音',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_douyin:list',
																											'channel_douyin:view',
																											'channel_douyin:list_channel',
																										],
																										'permission' => [
																											// 'channel_douyin:save:add' => '添加',
																											// 'channel_douyin:save:edit' => '编辑',
																											'channel_douyin:enable' => '启用',
																											'channel_douyin:disable' => '禁用',
																										],
																									],
																									'list_usdt' => [
																										'title' => 'USDT',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_usdt:list',
																											'channel_usdt:view',
																											'channel_usdt:list_channel',
																										],
																										'permission' => [
																											// 'channel_usdt:save:add' => '添加',
																											// 'channel_usdt:save:edit' => '编辑',
																											'channel_usdt:enable' => '启用',
																											'channel_usdt:disable' => '禁用',
																										],
																									],
																									'list_monitor' => [
																										'title' => '监控管理',
																										'hidden' => false,
																										'noCache' => false,
																										'white_list' => [
																											'channel_monitor:list',
																											'channel_monitor:view',
																											'channel_monitor:list_channel',
																											'channel_monitor:view_api',
																										],
																										'permission' => [
																											'channel_monitor:save:add' => '添加',
																											'channel_monitor:save:edit' => '编辑',
																											'channel_monitor:delete' => '删除',
																											'channel_monitor:enable' => '启用',
																											'channel_monitor:disable' => '禁用',
																										],
																									], */
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
		'bot' => [
			'title' => '机器人管理',
			'icon' => 'icon-xingzhuang-xingxing',
			'hidden' => false,
			'children' => [
				'list_group' => [
					'title' => '所有群聊',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'bot_group:list',
						'bot_group:save:add',
						'bot_group:save:edit',
						'bot_group:delete'
					],
					'permission' => [
						'bot_group:delete' => '删除',
						'bot_group:view' => '查看',
						'bot_group:save:edit' => '添加',
						'bot_group:enable' => '启用',
						'bot_group:disable' => '禁用',
					],
				],
				'list_operator' => [
					'title' => '操作员',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'bot_operator:list',
						'bot_question:add',
						'bot_operator:save:add',
						'bot_operator:save:edit',
						'bot_operator:delete'
					],
					'permission' => [
						'bot_operator:delete' => '删除',
						'bot_operator:view' => '查看',
						'bot_operator:save:edit' => '编辑',
						'bot_operator:enable' => '启用',
						'bot_operator:disable' => '禁用',
					],
				],
			],
		],
	],
];
