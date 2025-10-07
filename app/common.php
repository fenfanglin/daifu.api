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
