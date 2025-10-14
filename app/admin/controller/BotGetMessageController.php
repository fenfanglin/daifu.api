<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\service\bot\BotGetMessageService;


class BotGetMessageController extends AuthController
{
	//转发查单信息
	public function forward()
	{
		// 获取 Telegram 发送的更新
		$data = file_get_contents('php://input'); // 获取原始的 POST 请求内容
		$data = json_decode($data, true); // 将 JSON 数据解码为数组
		//Common::writeLog(['bot_data' => $data], 'bot_message_data');
		//获取协议
		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		// 获取完整的URL
		$fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		// 使用parse_url解析URL
		$parsedUrl = parse_url($fullUrl);
		$query = explode('?', $parsedUrl['query']);

		$query_data = [];
		foreach ($query as &$item)
		{
			$uri = explode('=', $item);
			$query_data[$uri[0]] = $uri[1];
		}
		unset($item);
		// 重复请求不处理
		$global_redis = Common::global_redis();
		$update_id = $global_redis->get('jqk' . $data['update_id']);
		if ($update_id == $data['update_id'])
		{
			return;
		}
		Common::writeLog(['bot_data' => $data], 'bot_message_data');
		$global_redis->set('jqk' . $data['update_id'], $data['update_id']);
		$message_model = new BotGetMessageService($query_data, $data);
		$message_model->message();
	}

	public function test4()
	{
		// 		$data = '{
//         "update_id": 593565865,
//         "message": {
//             "message_id": 47,
//             "from": {
//                 "id": 8109882440,
//                 "is_bot": false,
//                 "first_name": "JQK 值班技术 Beryl",
//                 "username": "JQK188888",
//                 "language_code": "zh-hans"
//             },
//             "chat": {
//                 "id": -1002554091963,
//                 "title": "机器人测试",
//                 "type": "supergroup"
//             },
//             "date": 1746687193,
//             "text": "UD202505072134107488,12311"
//         }
//     }';

		$data = '{
        "update_id": 593567560,
        "message": {
            "message_id": 12383,
            "from": {
                "id": 8109882440,
                "is_bot": false,
                "first_name": "JQK 值班技术 Beryl",
                "username": "JQK188888",
                "language_code": "zh-hans"
            },
            "chat": {
                "id": -4266625637,
                "title": "机器人测试1",
                "type": "group",
                "all_members_are_administrators": true,
                "accepted_gift_types": {
                    "unlimited_gifts": false,
                    "limited_gifts": false,
                    "unique_gifts": false,
                    "premium_subscription": false
                }
            },
            "date": 1747382846,
            "text": "qid"
        }
    }';
		$data = json_decode($data, true);
		$message_model = new BotGetMessageService(['bot_token' => '7608921219:AAGy8oDjlr11SzYKfcucmjNWgropQNr8Was'], $data);
		$message_model->message();
	}

}
