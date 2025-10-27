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
		'index:get_sign', //
		'index:get_token', //
		'upload:qr_decode', //
		'option:role', //列出角色
		'option:card_business', //列出卡商
		'option:channel', //列出通道
		'option:system_bank', //列出系统银行卡
		'user:check_channel', //检查商户通道是否有开启
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
				'log' => [
					'title' => '操作记录',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'log:list',
						'log:url_list',
					],
					'permission' => [

					],
				],
				'binding_system' => [
					'title' => '绑定系统',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'user:get_bind_jqk',
						'user:login_jqk',
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
						'order:set_order_success' => '设为成功',
						'order:set_order_fail' => '设为失败',
						'order:set_order_not_pay' => '设为未支付',
						'order:set_remark' => '备注',
						'order:resend_notify' => '补发通知',
						'order:allocation' => '分配工作室',
						'order:list_card_by_order' => '工作室列表',
						'order:upload_image' => '上传图片',
						'order:get_new_order_status' => '新订单状态',
					],
				],
			],
		],
		'card_business' => [
			'title' => '工作室管理',
			'icon' => 'icon-user-outlined',
			'hidden' => false,
			'children' => [
				'list_all' => [
					'title' => '工作室管理',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'card_business:list',
						'card_business:view', //查看编辑
						'card_business:view_channel', //查看通道
					],
					'permission' => [
						'card_business:save:add' => '添加',
						'card_business:save:edit' => '编辑',
						'card_business:save_channel' => '通道',
						'card_business:save_withdraw' => '提现',
						'card_business:change_password' => '密码',
						'card_business:delete' => '删除',
						'card_business:enable' => '启用',
						'card_business:disable' => '禁用',

						'system_relation:get_bind_jqk' => '获取绑定JQK',
						'system_relation:bind_jqk' => '绑定JQK',
						'system_relation:unbind_jqk' => '解绑JQK',
					],
				],
			],
		],
		'business' => [
			'title' => '商户管理',
			'icon' => 'icon-user-outlined',
			'hidden' => false,
			'children' => [
				'list_all' => [
					'title' => '商户管理',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'business:list',
						'business:view', //查看编辑
						'business:view_channel', //查看通道
					],
					'permission' => [
						'business:save:add' => '添加',
						'business:save:edit' => '编辑',
						'business:save_channel' => '通道',
						'business:save_withdraw' => '提现',
						'business:change_password' => '密码',
						'business:delete' => '删除',
						'business:enable' => '启用',
						'business:disable' => '禁用',
					],
				],
			],
		],
		'channel' => [
			'title' => '三方通道',
			'icon' => 'icon-yingyongguanli',
			'hidden' => false,
			'children' => [
				'list_shundatong' => [
					'title' => '瞬达通',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel_shundatong:list',
						'channel_shundatong:view',
						'user:channel_setting_view',
						'user:channel_setting_save',
						'user:channel_setting_del',
					],
					'permission' => [
						'channel_shundatong:save:add' => '添加',
						'channel_shundatong:save:edit' => '编辑',
						'channel_shundatong:delete' => '删除',
						'channel_shundatong:enable' => '启用',
						'channel_shundatong:disable' => '禁用',
					],
				],
				'list_dingxintong' => [
					'title' => '鼎薪通',
					'hidden' => false,
					'noCache' => false,
					'white_list' => [
						'channel_dingxintong:list',
						'channel_dingxintong:view',
						'user:channel_setting_view',
						'user:channel_setting_save',
						'user:channel_setting_del',
					],
					'permission' => [
						'channel_dingxintong:save:add' => '添加',
						'channel_dingxintong:save:edit' => '编辑',
						'channel_dingxintong:delete' => '删除',
						'channel_dingxintong:enable' => '启用',
						'channel_dingxintong:disable' => '禁用',
					],
				],
			],
		],
		'finance' => [
			'title' => '财务管理',
			'icon' => 'icon-zhexiantu',
			'hidden' => false,
			'children' => [
				'list_recharge' => [
					'title' => '充值记录',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'recharge:list',
						'recharge:view',
						'recharge:get_setting',
						'recharge_usdt:pay', //Usdt充值
					],
					'permission' => [
						'recharge:export' => '导出',
					],
				],
				'list_money_log' => [
					'title' => '资金明细',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'money_log:list',
						'money_log:view',
					],
					'permission' => [
						'money_log:export' => '导出',
					],
				],
				'list_withdraw' => [
					'title' => '商户充值记录',
					'hidden' => false,
					'noCache' => true,
					'white_list' => [
						'withdraw:list',
						'withdraw:view',
						'withdraw:get_setting',
						// 'withdraw:submit', //提交
					],
					'permission' => [
						//						'withdraw:verify' => '审核',
						'withdraw:export' => '导出',
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
					'title' => '群聊列表',
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
