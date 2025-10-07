<?php
return [
	'app_name' => 'pay',
	'google_discrepancy' => 2, //谷歌验证码容差时间，如果这里是2，那么就是 2 * 30 = 60秒
	'google_secret_key_cache' => 10 * 60, //生成谷歌密钥缓存时间
	'jwt' => [
		'key' => 'Ph@pHz@-0A39=E@9E3*H!wc=rngBesp7*ecH!s8x',
		'aud' => 'pay',
		'expire_time' => 86400 * 1,
		'refresh_time' => 3600,
		// 'expire_time' => 15,
		// 'refresh_time' => 10,
	],
];