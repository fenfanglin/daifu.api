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
						'order:switch_order' => '开启/关闭接单',
						'order:upload_image' => '上传图片',
						'order:get_new_order_status' => '新订单状态',
					],
				],
			],
		],
	],
];
