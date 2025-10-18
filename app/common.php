<?php
/**
 * 统计订单缓存时间
 */
function getDataCacheTime()
{
	// return -1;

	$timeout = 5; //缓存5分钟
	return $timeout * 60 + mt_rand(-30, 60);
}

/**
 * 统计订单缓存时间（短时间）
 */
function getDataCacheTimeMin()
{
	$timeout = 5; //缓存1分钟
	return $timeout * 60 + mt_rand(-30, 30);
}

/**
 * 检查账号是否连续失败n笔
 */
function checkAccountOrder($channel_account_id, $fail_num)
{
	$where = [];
	$where[] = ['channel_account_id', '=', $channel_account_id];
	$where[] = ['expire_time', '<', date('Y-m-d H:i:s')];

	$global_redis = \app\extend\common\Common::global_redis();
	if ($timer = $global_redis->get("checkAccount_timer_{$channel_account_id}"))
	{
		$where[] = ['create_time', '>', $timer];
	}

	$list = \app\model\Order::field('id, create_time, status')->where($where)->limit($fail_num)->order('id', 'desc')->select()->toArray();

	// \app\extend\common\Common::writeLog([
	// 	'where' => $where,
	// 	'checkAccount_timer_{$channel_account_id}' => "checkAccount_timer_{$channel_account_id}",
	// ], 'test');

	if (!$list)
	{
		return true;
	}

	$check = false;
	foreach ($list as $value)
	{
		if ($value['status'] > 0)
		{
			$check = true;
			break;
		}
	}

	return $check;
}
/**
 * 验证银行卡号
 */
if (!function_exists('checkBanKCard'))
{
	function checkBanKCard($bank_no)
	{
		return true;

		$n = 0;
		$ns = strrev($bank_no); // 倒序
		for ($i = 0; $i < strlen($bank_no); $i++)
		{
			if ($i % 2 == 0)
			{
				$n += $ns[$i]; // 偶数位，包含校验码
			}
			else
			{
				$t = $ns[$i] * 2;
				if ($t >= 10)
				{
					$t = $t - 9;
				}
				$n += $t;
			}
		}
		return ($n % 10) == 0 ? true : false;
	}
}

/**
 * 收款列表
 */
if (!function_exists('listAccountType'))
{
	function listAccountType()
	{
		return [
			1 => '银行卡',
			2 => 'USDT',
			3 => '支付宝',
		];
	}
}

/**
 * 去除字符串两边空格
 */
if (!function_exists('removeSpaces'))
{
	function removeSpaces($params)
	{
		if (is_array($params))
		{
			foreach ($params as &$item)
			{
				$item = trim($item);
			}
		}
		else
		{
			$params = trim($params);
		}
		return $params;
	}
}

/**
 * 判断重复点击
 */
if (!function_exists('judgeRepeatClick'))
{
	function judgeRepeatClick($key)
	{
		$redis = new \Redis();
		$redis->connect(env('redis.host'), env('redis.port'));
		$redis->auth(env('redis.password'));
		$value = $redis->set($key, 1, ['NX', 'EX' => 1]);
		return $value;
	}
}

function createQrcode($content, $level = 'H', $size = 10, $margin = 1)
{
	require_once __DIR__ . '/../extend/phpqrcode/QRcode.php';

	\qrcode\QRcode::png($content, false, $level, $size, $margin);

	$img = ob_get_contents();
	ob_end_clean();

	return 'data:image/png;base64,' . base64_encode($img);
}

function milliseconds()
{
	// 获取当前的UNIX时间戳和微秒数
	$microtime = microtime();

	// 分割微秒数和秒数
	list($seconds, $microseconds) = explode(" ", $microtime);

	// 转换为毫秒
	$milliseconds = (int) round($seconds * 1000);
	$milliseconds += (int) round($microseconds * 1000);

	return $milliseconds;
}

/**
 * 根据商户类型生成查询条件
 */
function setChannelWhere($user, &$query)
{
	// 类型：1代理 2工作室 3商户
	if (in_array($user->type, [1]))
	{
		$query->where('business_id', $user->id);
	}
	elseif (in_array($user->type, [2]))
	{
		$query->where('card_business_id', $user->id);
	}
	else
	{
		$query->where('id', 0); //不显示内容
	}
}

/**
 * 检查信息是否属于商户
 */
function checkAccountBelongBusiness($user, $model)
{
	// 类型：1代理 2工作室 3商户
	if (in_array($user->type, [1]))
	{
		if ($model->business_id != $user->id)
		{
			return ['status' => false, 'msg' => '信息不属于代理'];
		}
	}
	elseif (in_array($user->type, [2]))
	{
		if ($model->card_business_id != $user->id)
		{
			return ['status' => false, 'msg' => '信息不属于工作室'];
		}
	}
	else
	{
		return ['status' => false, 'msg' => '信息不属于商户'];
	}

	return ['status' => true, 'msg' => '成功'];
}

/**
 * 收款账号设置商户信息
 */
function setAccountBusinessInfo($user, &$model)
{
	// 类型：1代理 2工作室 3商户
	if (in_array($user->type, [1]))
	{
		$model->business_id = $user->id;
		$model->card_business_id = intval(input('post.card_business_id'));
	}
	elseif (in_array($user->type, [2]))
	{
		$model->business_id = $user->parent_id;
		$model->card_business_id = $user->id;
	}
}