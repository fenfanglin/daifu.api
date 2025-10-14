<?php

namespace app\service\bot;

use app\admin\controller\BotHandleController;
use app\model\BotBill;
use app\model\BotForward;
use app\model\BotGroup;
use app\model\BotBatch;
use app\model\BotOperator;
use app\model\ChannelAccount;
use app\extend\common\Common;
use app\model\BotQuestion;

class BotGetMessageService
{
	//消息数据
	public $message_data;

	public $token;

	public $bot_function;

	public $bot_name;

	//消息选项
	public $reply_markup;

	public function __construct($query_data, $message_data)
	{
		$this->message_data = $message_data;
		$this->token = $query_data['bot_token'];
		$this->bot_function = new BotHandleController();
		$this->bot_name = 'jqkceshibot';       //机器人用户名 更换机器人时记得要修改这里
	}

	/**处理读取到的所有消息
	 **/
	public function message()
	{

		if (isset($this->message_data['message']))
		{
			$chatId = $this->message_data["message"]["chat"]["id"];
			// if (isset($this->message_data['message']['reply_to_message']))
			// {
			// 	//跨群回复聊天
			// 	if ($this->message_data['message']['reply_to_message']['from']['username'] == $this->bot_name)
			// 	{
			// 		//处理被回复的信息无图片 和回复的信息也无图片的消息
			// 		if (isset($this->message_data['message']['text']) && isset($this->message_data['message']['reply_to_message']['text']) && !isset($this->message_data['message']['photo']))
			// 		{
			// 			$send_message = $this->message_data['message']['text'];
			// 			$reply_text = $this->message_data['message']['reply_to_message']['text'];
			// 			$forward_model = new BotForward();
			// 			$search_chat_id = $forward_model->where(['text' => $reply_text, 'vice_chat_id' => $chatId])->order('message_id desc')->field('id,main_chat_id,message_id,photo')->find();
			// 			$bot_group_model = new BotGroup();
			// 			$card_business = $bot_group_model->where(['chat_id' => $chatId])->find();
			// 			if (!$card_business)
			// 			{
			// 				$send_message = "未查询到码商群，请联系管理员绑定码商!";
			// 				self::sendMessage($chatId, $send_message);
			// 			}
			// 			$forward_model->bot_group_id = $card_business['id'];
			// 			$forward_model->main_chat_id = $chatId;
			// 			$forward_model->vice_chat_id = $search_chat_id['main_chat_id'];
			// 			$forward_model->text = $send_message;
			// 			$forward_model->message_id = $this->message_data['message']['message_id'];
			// 			if (!$forward_model->save())
			// 			{
			// 				$data = [
			// 					'chat_id' => $chatId,
			// 					'text' => '转发失败请联系管理员',
			// 					'reply_to_message_id' => $this->message_data['message']['message_id']
			// 				];
			// 				$this->sendForwardTextMessage($data);
			// 			}

			// 			$data = [
			// 				'chat_id' => intval($search_chat_id['main_chat_id']),
			// 				'text' => $send_message,
			// 				'reply_to_message_id' => intval($search_chat_id['message_id'])
			// 			];

			// 			$this->sendForwardTextMessage($data);
			// 		}
			// 		//处理被回复的信息无图片 回复的信息有图片
			// 		if (isset($this->message_data['message']['caption']) && isset($this->message_data['message']['reply_to_message']['text']) && isset($this->message_data['message']['photo']))
			// 		{
			// 			$send_message = $this->message_data['message']['caption'];
			// 			$reply_text = $this->message_data['message']['reply_to_message']['text'];
			// 			$photo = end($this->message_data['message']['photo']); // 获取最大的图片尺寸
			// 			$file_id = $photo['file_id'];
			// 			$forward_model = new BotForward();
			// 			$search_chat_id = $forward_model->where(['text' => $reply_text, 'vice_chat_id' => $chatId])->order('message_id desc')->field('id,main_chat_id,message_id,photo')->find();
			// 			$bot_group_model = new BotGroup();
			// 			$card_business = $bot_group_model->where(['chat_id' => $chatId])->find();
			// 			if (!$card_business)
			// 			{
			// 				$send_message = "未查询到码商群，请联系管理员绑定码商!";
			// 				self::sendMessage($chatId, $send_message);
			// 			}

			// 			$forward_model->bot_group_id = $card_business['id'];
			// 			$forward_model->main_chat_id = $chatId;
			// 			$forward_model->vice_chat_id = $search_chat_id['main_chat_id'];
			// 			$forward_model->text = $send_message;
			// 			$forward_model->message_id = $this->message_data['message']['message_id'];
			// 			$forward_model->photo = $file_id;
			// 			if (!$forward_model->save())
			// 			{
			// 				$data = [
			// 					'chat_id' => $chatId,
			// 					'text' => '转发失败请联系管理员',
			// 					'reply_to_message_id' => $this->message_data['message']['message_id']
			// 				];
			// 				$this->sendForwardTextMessage($data);
			// 			}
			// 			$data = [
			// 				'chat_id' => $search_chat_id['main_chat_id'],
			// 				'photo' => $file_id,
			// 				'caption' => $send_message,
			// 				'reply_to_message_id' => $search_chat_id['message_id']
			// 			];
			// 			$this->sendForwardMessage($data);
			// 			exit;
			// 		}
			// 		//处理被回复的信息有图片 回复的信息无图片的消息
			// 		if (isset($this->message_data['message']['text']) && isset($this->message_data['message']['reply_to_message']['caption']) && isset($this->message_data['message']['reply_to_message']['photo']) && !isset($this->message_data['message']['photo']))
			// 		{
			// 			$send_message = $this->message_data['message']['text'];
			// 			$reply_text = $this->message_data['message']['reply_to_message']['caption'];
			// 			$forward_model = new BotForward();
			// 			$search_chat_id = $forward_model->where(['text' => $reply_text, 'vice_chat_id' => $chatId])->order('message_id desc')->field('id,main_chat_id,message_id,photo')->find();
			// 			$bot_group_model = new BotGroup();
			// 			$card_business = $bot_group_model->where(['chat_id' => $chatId])->find();
			// 			if (!$card_business)
			// 			{
			// 				$send_message = "未查询到码商群，请联系管理员绑定码商!";
			// 				self::sendMessage($chatId, $send_message);
			// 			}

			// 			$forward_model->bot_group_id = $card_business['id'];
			// 			$forward_model->main_chat_id = $chatId;
			// 			$forward_model->vice_chat_id = $search_chat_id['main_chat_id'];
			// 			$forward_model->text = $send_message;
			// 			$forward_model->message_id = $this->message_data['message']['message_id'];
			// 			if (!$forward_model->save())
			// 			{
			// 				$data = [
			// 					'chat_id' => $chatId,
			// 					'text' => '转发失败请联系管理员',
			// 					'reply_to_message_id' => $this->message_data['message']['message_id']
			// 				];
			// 				$this->sendForwardTextMessage($data);
			// 			}
			// 			$data = [
			// 				'chat_id' => $search_chat_id['main_chat_id'],
			// 				'text' => $send_message,
			// 				'reply_to_message_id' => $search_chat_id['message_id']
			// 			];
			// 			$this->sendForwardTextMessage($data);
			// 		}

			// 		//处理被回复的信息有图片 回复的信息有图片的消息
			// 		if (isset($this->message_data['message']['caption']) && isset($this->message_data['message']['reply_to_message']['caption']) && isset($this->message_data['message']['reply_to_message']['photo']) && isset($this->message_data['message']['photo']))
			// 		{
			// 			$send_message = $this->message_data['message']['caption'];
			// 			$reply_text = $this->message_data['message']['reply_to_message']['caption'];
			// 			$photo = end($this->message_data['message']['photo']); // 获取最大的图片尺寸
			// 			$file_id = $photo['file_id'];
			// 			$forward_model = new BotForward();
			// 			$search_chat_id = $forward_model->where(['text' => $reply_text, 'vice_chat_id' => $chatId])->order('message_id desc')->field('id,main_chat_id,message_id,photo')->find();

			// 			$bot_group_model = new BotGroup();
			// 			$card_business = $bot_group_model->where(['chat_id' => $chatId])->find();
			// 			if (!$card_business)
			// 			{
			// 				$send_message = "未查询到码商群，请联系管理员绑定码商!";
			// 				self::sendMessage($chatId, $send_message);
			// 			}

			// 			$forward_model->bot_group_id = $card_business['id'];
			// 			$forward_model->main_chat_id = $chatId;
			// 			$forward_model->vice_chat_id = $search_chat_id['main_chat_id'];
			// 			$forward_model->text = $send_message;
			// 			$forward_model->message_id = $this->message_data['message']['message_id'];
			// 			$forward_model->photo = $file_id;
			// 			if (!$forward_model->save())
			// 			{
			// 				$data = [
			// 					'chat_id' => $chatId,
			// 					'text' => '转发失败请联系管理员',
			// 					'reply_to_message_id' => $this->message_data['message']['message_id']
			// 				];
			// 				$this->sendForwardTextMessage($data);
			// 			}
			// 			$data = [
			// 				'chat_id' => $search_chat_id['main_chat_id'],
			// 				'photo' => $file_id,
			// 				'caption' => $send_message,
			// 				'reply_to_message_id' => $search_chat_id['message_id']
			// 			];
			// 			$this->sendForwardMessage($data);
			// 			exit;
			// 		}
			// 	}
			// }
			//处理普通文本信息
			if (isset($this->message_data["message"]["text"]))
			{
				$message = $this->message_data["message"];
				$this->textMessage($message, $chatId);
			}
			// 			if (isset($this->message_data['message']['photo']))
// 			{
// 				// 处理图片消息
// 				$photo = end($this->message_data['message']['photo']); // 获取最大的图片尺寸
// 				$file_id = $photo['file_id'];
// 				$caption = isset($this->message_data['message']['caption']) ? $this->message_data['message']['caption'] : '';
// 				$pattern = '/\b[A-Za-z0-9]{16,}\b/';
// 				$is_order = preg_match($pattern, $caption, $matches);
// 				if ($is_order)
// 				{
// 					$order_info = $this->bot_function->getOrder($matches[0]);
// 					if (!$order_info)
// 					{
// 						$send_message = '订单不存在！';
// 						self::sendMessage($chatId, $send_message);
// 					}
// 					switch ($order_info['status'])
// 					{
// 						case -1:    //未支付  转发码商
// 							$bot_group_model = new BotGroup();
// 							$card_business = $bot_group_model->where(['business_id' => $order_info['card_business_id']])->find();
// 							if (!$card_business)
// 							{
// 								$send_message = "未查询到码商群，请联系管理员绑定码商!";
// 								self::sendMessage($chatId, $send_message);
// 							}
// 							else
// 							{
// 								$data = [
// 									'chat_id' => $card_business['chat_id'],
// 									'photo' => $file_id,
// 									'caption' => $caption,
// 									'send_chat_id' => $chatId
// 								];
// 								$result = $this->sendForwardMessage($data);
// 								$result = json_decode($result, true);
// 								if (isset($result['ok']) && $result['ok'])
// 								{
// 									$bot_forward_model = new BotForward();
// 									$bot_forward_model->bot_group_id = $card_business['id'];
// 									$bot_forward_model->main_chat_id = $chatId;
// 									$bot_forward_model->message_id = $this->message_data['message']['message_id'];
// 									$bot_forward_model->text = $caption;
// 									$bot_forward_model->photo = $file_id;
// 									$bot_forward_model->vice_chat_id = $card_business['chat_id'];
// 									$bot_forward_model->save();
// 								}
// 								exit;
// 							}
// 						case 1:
// 							//发送回调请求
// 							$send_message = "订单成功未回调,商户未返回正确参数“SUCCESS”,请发送给商户技术检查";
// 							self::sendMessage($chatId, $send_message);
// 						case 2:
// 							$send_message = "订单成功已回调";
// 							self::sendMessage($chatId, $send_message);
// 					}
// 				}
// 				//                if (strpos($caption, '回单-') !== false) {
// //                    $reply = $this->message_data['message']['reply_to_message'];
// //                    $search_order_model = new XgSearchOrder();
// //                    $reply_text = isset($reply['caption']) ? $reply['caption'] : $reply['text'];
// //                    $search_chat_id = $search_order_model->where(['text'=> $reply_text,'card_business_chat_id'=>$chatId])->order('message_id desc')->field('id,search_chat_id,message_id')->find();
// //                    if(!$search_chat_id){
// //                        $send_message = "当前商户未绑定卡商无法查单!";
// //                        //发送消息
// //                        self::sendMessage($chatId, $send_message);
// //                        exit;
// //                    }else{
// //                        $data = [
// //                            'chat_id' => $search_chat_id['search_chat_id'],
// //                            'photo' => $file_id,
// //                            'caption'=>$caption,
// //                            'send_chat_id' => $chatId,
// //                            'reply_to_message_id' => $search_chat_id['message_id']
// //                        ];
// //                        $this->sendForwardMessage($data);
// //                        exit;
// //                    }
// //                }
// 			}
		}
		//返回菜单选项
		if (isset($this->message_data['callback_query']))
		{
			$chatId = $this->message_data["callback_query"]['message']["chat"]["id"];
			//群验证
			$this->checkOperator($chatId, $this->message_data['callback_query']['from']['username']);
			$this->callbackMessage($this->message_data["callback_query"], $chatId);
		}

		if (isset($this->message_data['message']['photo']))
		{
			// 处理图片消息
			$chatId = $this->message_data["chat"]["id"];
			$photo = end($this->message_data['photo']); // 获取最大的图片尺寸
			$file_id = $photo['file_id'];
			$caption = isset($this->message_data['caption']) ? $this->message_data['caption'] : '';
			$pattern = '/\b[A-Za-z0-9]{16,}\b/';
			$is_order = preg_match($pattern, $caption, $matches);
			if ($is_order)
			{
				$order_info = $this->bot_function->getOrder($matches[0]);
				if (!$order_info)
				{
					$send_message = '订单不存在！';
					self::sendMessage($chatId, $send_message);
					exit;
				}
				switch ($order_info['status'])
				{
					case -1:    //未支付  转发码商
						$bot_group_model = new BotGroup();
						$card_business = $bot_group_model->where(['business_id' => $order_info['card_business_id']])->find();
						if (!$card_business)
						{
							$send_message = "未查询到码商群，请联系管理员绑定码商!";
							self::sendMessage($chatId, $send_message);
						}
						else
						{
							$data = [
								'chat_id' => $card_business['chat_id'],
								'photo' => $file_id,
								'caption' => $caption,
								'send_chat_id' => $chatId
							];
							$result = $this->sendForwardMessage($data);
							$result = json_decode($result, true);
							if (isset($result['ok']) && $result['ok'])
							{
								$bot_forward_model = new BotForward();
								$bot_forward_model->bot_group_id = $card_business['id'];
								$bot_forward_model->main_chat_id = $chatId;
								$bot_forward_model->message_id = $this->message_data['message']['message_id'];
								$bot_forward_model->text = $caption;
								$bot_forward_model->photo = $file_id;
								$bot_forward_model->vice_chat_id = $card_business['chat_id'];
								$bot_forward_model->save();
							}
							exit;
						}
					case 1:
						//发送回调请求
						$send_message = "订单成功未回调,商户未返回正确参数“SUCCESS”,请发送给商户技术检查";
						self::sendMessage($chatId, $send_message);
					case 2:
						$send_message = "订单成功已回调";
						self::sendMessage($chatId, $send_message);
				}
			}
		}
		//会员进群
		if (
			isset($this->message_data['message']['new_chat_participant']) &&
			isset($this->message_data['message']['new_chat_member']) &&
			isset($this->message_data['message']['new_chat_members'])
		)
		{

			if ($this->message_data['message']['new_chat_member']['is_bot'] == false)
			{
				$chatId = $this->message_data["message"]["chat"]["id"];
				$user_name = $this->message_data['message']['new_chat_member']['username'];
				//进群发送信息提醒商户绑定
				$name = $this->message_data['message']['new_chat_member']['first_name'] . $this->message_data['message']['new_chat_member']['last_name'];
				$this->sendMessage($chatId, "欢迎新成员 $name  @$user_name  加入群组！");
			}
			else
			{
				// $this->sendMessage($chatId, "请将机器人设置为管理员以便接收消息\n请前往JQK后台绑定群聊信息当前群聊ID：" . $chatId);
				$this->sendMessage($chatId, "请将机器人设置为管理员以便接收消息");
			}
		}
	}


	/**消息文本处理
	 * $message         文本消息内容
	 * $chatId          聊天id
	 * $group_name      群里名称
	 **/
	public function textMessage($message, $chatId)
	{

		$text = $message['text'];
		$username = isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'];
		$send_message = '';
		//查单
		$pattern = '/\b[A-Za-z0-9]{16,}\b/';
		$is_order = preg_match($pattern, $text, $matches);
		if ($is_order)
		{
			$order_info = $this->bot_function->getOrder($matches[0]);
			if (!$order_info)
			{
				$send_message = "订单不存在！";
				self::sendMessage($chatId, $send_message);
				exit;
			}
			else
			{

				if ($order_info['business_id'] == 11772)
				{
					$send_message = "\nU : " . $order_info['usdt_amount'];
					self::sendMessage($chatId, $send_message);
					exit;
				}
				switch ($order_info['status'])
				{
					case -1:    //未支付  转发码商
						$bot_group_model = new BotGroup();
						$card_business = $bot_group_model->where(['business_id' => $order_info['card_business_id']])->find();
						if (!$card_business)
						{
							$send_message = "未查询到码商群，请联系管理员绑定码商!";
						}
						else
						{
							$data = [
								'chat_id' => $card_business['chat_id'],
								'text' => $text,
							];
							$result = $this->sendOrderForwardTextMessage($data);
							$result = json_decode($result, true);
							if (isset($result['ok']) && $result['ok'])
							{
								$bot_forward_model = new BotForward();
								$bot_forward_model->bot_group_id = $card_business['id'];
								$bot_forward_model->main_chat_id = $chatId;
								$bot_forward_model->message_id = $message['message_id'];
								$bot_forward_model->text = $text;
								$bot_forward_model->vice_chat_id = $card_business['chat_id'];
								$bot_forward_model->save();
							}
							exit;
						}
					case 1:
						//发送回调请求
						$send_message = "订单成功未回调,商户未返回正确参数“SUCCESS”,请发送给商户技术检查";
						self::sendMessage($chatId, $send_message);
					case 2:
						$send_message = "订单成功已回调";
						//发送消息
						self::sendMessage($chatId, $send_message);
				}
			}
		}
		if ($text == 'qfall' || $text == 'qfms' || $text == 'qfsh')
		{

			if (!isset($this->message_data['message']['reply_to_message']))
			{
				$send_message = "请回复群发信息!";
				self::sendMessage($chatId, $send_message);
			}
			$this->checkOperator($chatId, $username);
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$send_message = "当前群里不可群发消息请前往JQK商户群发送";
				self::sendMessage($chatId, $send_message);
			}

			$where = [];
			$where[] = ['parent_id', '=', $group_info['parent_id']];
			$where[] = ['id', '<>', $group_info['id']];
			//四方群
			if ($text == 'qfms')
			{        //码商群
				$type = 3;
			}
			elseif ($text == 'qfsh')
			{   //四方商户群
				$type = 4;
			}
			else
			{
				$type = 0;              //四方下所有的码商和商户群
			}
			if ($type)
			{
				$where[] = ['type', '=', $type];
			}
			$group_list = $bot_group_model->where($where)->select();
			if (!$group_list)
			{
				$send_message = "无群聊可群发!";
				self::sendMessage($chatId, $send_message);
			}
			$reply = $this->message_data['message']['reply_to_message'];

			$reply_text = isset($reply['caption']) ? $reply['caption'] : (isset($reply['text']) ? $reply['text'] : '');
			$bot_forward_model = new BotForward();
			if (isset($reply['photo']))
			{
				$photo = end($reply['photo']);
				$file_id = $photo['file_id'];
				foreach ($group_list as $key)
				{
					$data = [
						'chat_id' => $key['chat_id'],
						'photo' => $file_id,
						'caption' => $reply_text,
					];
					$this->sendForwardMessage($data);
					$bot_forward_model->main_chat_id = $chatId;
					$bot_forward_model->text = $reply_text;
					$bot_forward_model->vice_chat_id = $key['chat_id'];
					$bot_forward_model->message_id = $message['message_id'];
					$bot_forward_model->save();
				}
				exit;
			}
			else
			{
				foreach ($group_list as $key)
				{
					$bot_forward_model->main_chat_id = $chatId;
					$bot_forward_model->text = $reply_text;
					$bot_forward_model->vice_chat_id = $key['chat_id'];
					$bot_forward_model->message_id = $message['message_id'];
					$bot_forward_model->save();
					$data = [
						'chat_id' => $key['chat_id'],
						'text' => $reply_text,
					];
					$result = $this->sendOrderForwardTextMessage($data);
				}
				exit;
			}
		}
		//查询跑量昨天
		if (strpos($text, 'zrpl') !== false)
		{
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定卡商或商户无法操作',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			if ($group_info['type'] == 3)
			{         //卡商
				$result = $this->bot_function->getCardNum($group_info['business_id'], 2);
			}
			elseif ($group_info['type'] == 4)
			{    //四方商户
				$result = $this->bot_function->getBusinessNum($group_info['business_id'], 2);
			}
			if (!isset($result))
			{
				return;
			}
			$data = [
				'chat_id' => $chatId,
				'text' => '跑量:' . $result['pay_amount'],
			];
			$this->sendForwardTextMessage($data);
		}

		//查询今日跑量
		if (strpos($text, 'jrpl') !== false)
		{
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定卡商或商户无法操作',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			if ($group_info['type'] == 3)
			{         //卡商
				$result = $this->bot_function->getCardNum($group_info['business_id'], 1);
			}
			elseif ($group_info['type'] == 4)
			{    //四方商户
				$result = $this->bot_function->getBusinessNum($group_info['business_id'], 1);
			}
			if (!isset($result))
			{
				return;
			}
			$data = [
				'chat_id' => $chatId,
				'text' => '跑量:' . $result['pay_amount'],
			];
			$this->sendForwardTextMessage($data);
		}

		//查询全部跑量
		if (strpos($text, 'lspl') !== false)
		{
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定卡商或商户无法操作',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			if ($group_info['type'] == 3)
			{         //卡商
				$result = $this->bot_function->getCardNum($group_info['business_id'], 3);
			}
			elseif ($group_info['type'] == 4)
			{    //四方商户
				$result = $this->bot_function->getBusinessNum($group_info['business_id'], 3);
			}
			if (!isset($result))
			{
				return;
			}
			$data = [
				'chat_id' => $chatId,
				'text' => '跑量:' . $result['pay_amount'],
			];
			$this->sendForwardTextMessage($data);
		}

		//USDT实时汇率
		if (substr($text, 0, 1) == 'k' || substr($text, 0, 1) == 'z' || substr($text, 0, 1) == 'w')
		{
			$type = 'all';
			$name = 'OTC商家实时价格';
			$money = str_replace(substr($text, 0, 1), '', $text);
			if (!preg_match('/^\d+$/', $money))
			{
				return;
			}
			if (substr($text, 0, 1) == 'k')
			{
				$type = 'bank';
				$name = '银行卡欧易';
			}
			elseif (substr($text, 0, 1) == 'z')
			{
				$type = 'alipay';
				$name = '支付宝欧易';
			}
			elseif (substr($text, 0, 1) == 'w')
			{
				$type = 'wxPay';
				$name = '微信欧易';
			}
			$result = file_get_contents("https://www.okx.com/v3/c2c/tradingOrders/books?quoteCurrency=CNY&baseCurrency=USDT&side=sell&paymentMethod=$type&userType=all&receivingAds=false&t=1723128802289");
			$result = json_decode($result, true);
			$info = "OTC商家实时价格\n筛选:$name\n";
			foreach ($result['data']['sell'] as $key => $value)
			{
				$info .= $value['price'] . '   ' . $value['nickName'] . "\n";
				if ($key == 10)
				{
					break;
				}
			}
			$hl = ($result['data']['sell'][0]['price']);
			$jg = sprintf('%.2f', ($money / $hl));
			$info .= "币数 ：(" . $money . " ÷ " . $hl . ") = " . $jg . "USDT";

			$data = [
				'chat_id' => $chatId,
				'text' => $info,
			];
			$this->sendForwardTextMessage($data);
		}
		//给卡商加余额  或者商户 + - 可提现金额
		if (substr($text, 0, 3) == '加' || substr($text, 0, 3) == '减')
		{
			$this->checkOperator($chatId, $username);
			$type = (substr($text, 0, 3) == '加') ? 1 : 2;
			$money = substr($text, 3);
			$pattern = '/^(\([^()]+\)|[0-9]+(\.[0-9]+)*)+(\([^()]+\)|[0-9]+(\.[0-9]+)*|\*|\/|\+|\-)*$/';
			if (!preg_match($pattern, $money))
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '金额错误!',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$result = null;
			eval ('$result = ' . $money . ';');
			$money = $result;
			$bot_group_model = new Botgroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$business)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定卡商或商户无法操作',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}

			if (in_array($business['type'], ['1', '2']))
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊为JQK商户群不可执行当前操作！',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$business_type = $business['type'] == 3 ? 1 : 2;
			//更新卡商or商户 额度
			$result = $this->bot_function->cardBusinessQuota($business['business_id'], $money, $type, $business_type);
			if (!$result['code'])
			{
				$data = [
					'chat_id' => $chatId,
					'text' => $result['msg'],
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $result['msg'] . "\n" . "当前额度:" . $result['last_quota'],
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}
		//群内记账
		if (substr($text, 0, 1) == '+' || substr($text, 0, 1) == '-')
		{
			$this->checkOperator($chatId, $username);
			$type = (substr($text, 0, 1) == '+') ? 1 : 2;
			$money = substr($text, 1);
			$pattern = '/^(\([^()]+\)|[0-9]+(\.[0-9]+)*)+(\([^()]+\)|[0-9]+(\.[0-9]+)*|\*|\/|\+|\-)*$/';
			if (!preg_match($pattern, $money))
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '金额错误!',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$result = null;
			eval ('$result = ' . $money . ';');
			$money = $result;
			$bot_group_model = new BotGroup();

			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定卡商或商户无法操作',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}

			$bill_model = new BotBill();
			$bill_model->bot_group_id = $group_info['id'];
			$bill_model->chat_id = $chatId;
			$bill_model->operator = $username;
			$bill_model->type = $type;
			$bill_model->business_id = $group_info['business_id'] ? $group_info['business_id'] : $group_info['parent_id'];
			$bill_model->money = $money;
			if (!$bill_model->save())
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '记账失败请联系管理员',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			$year = date('Y-m-d');
			$startTimestamp = date('Y-m-d 00:00:00');
			$endTimestamp = date('Y-m-d 23:59:59');
			$where = [];
			$where[] = ['bot_group_id', '=', $group_info['id']];
			$where[] = ['chat_id', '=', $group_info['chat_id']];
			$where[] = ['business_id', '=', $group_info['business_id'] ? $group_info['business_id'] : $group_info['parent_id']];
			$where[] = ['create_time', '<', $endTimestamp];
			$where[] = ['create_time', '>', $startTimestamp];
			$list = $bill_model->where($where)->select();
			$send_message = $year . "日小计\n";
			if ($list)
			{
				$rz = 0;
				$xf = 0;

				foreach ($list as $item)
				{
					if ($item['type'] == 1)
					{
						$rz += $item['money'];
					}
					else
					{
						$xf += $item['money'];
					}
					$jj = $item['type'] == 1 ? '+' : '-';
					$send_message .= date('H:i', $item['createtime']) . "    $jj" . $item['money'] . "\n";
				}

				$wxf = $rz - $xf;
				$zj = count($list);
				$send_message .= "总入款    $rz\n";
				$send_message .= "应下发    $rz\n";
				$send_message .= "已下发    $xf\n";
				$send_message .= "未下发    $wxf\n";
				$send_message .= "共计 $zj 笔";
			}
			else
			{
				$send_message = '无数据';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $send_message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}

		if ($text == 'ed')
		{
			$bot_group_model = new BotGroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$business)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息无法查看',
				];
				$this->sendForwardTextMessage($data);
				exit;
			}

			$business_type = $business['type'] == 3 ? 1 : 2;

			$result = $this->bot_function->getQuotaAllow($business['business_id'], $business_type);
			if (!$result['code'])
			{
				$data = [
					'chat_id' => $chatId,
					'text' => $result['msg'],
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $result['msg'] . "\n" . "当前额度:" . $result['last_quota'],//"\n总额:".$result['total']
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}

		//跑量日结
		if ($text == 'rj')
		{
			$bot_group_model = new BotGroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$business)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息无法查看',
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			if ($business['type'] < 3)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊无法查看',
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			$business_type = $business['type'] == 3 ? 1 : 2;
			$result = $this->bot_function->getRj($business['business_id'], $business_type);
			$data = [
				'chat_id' => $chatId,
				'text' => $result['msg'] . "\n" . "总量:  " . $result['total_amount'] . "\n",
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			if (!empty($result['data']))
			{
				foreach ($result['data'] as $key)
				{
					$data['text'] .= "{$key['name']}   : {$key['pay_amount']}\n";
				}
			}
			$this->sendForwardTextMessage($data);
		}

		if (strpos($text, '/admin@' . $this->bot_name) !== false && !$send_message)
		{
			$message = "群聊信息\n";
			$bot_group_model = new BotGroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();
			if ($business)
			{
				if ($business['type'] == 1)
				{        //四方
					$type = '四方';
				}
				elseif ($business['type'] == 2)
				{       //商户
					$type = '商户';
				}
				elseif ($business['type'] == 3)
				{       //卡商
					$type = '卡商';
				}
				else
				{
					$type = '四方商户';               //四方商户
				}
				$business_id = $business['business_id'] ? $business['business_id'] : $business['parent_id'];
			}
			else
			{
				$business_id = '';
				$type = '';
			}
			$usdt = isset($business['usdt']) ? $business['usdt'] : 0;
			$usdt_gears = isset($business['usdt_gears']) ? $business['usdt_gears'] : 1;   //调档
			$quota = isset($business['quota']) ? $business['quota'] : '';
			$quota_status = isset($business['quota_status']) ? $business['quota_status'] == 1 ? '开启' : '关闭' : '';
			// 定义需要发送的消息内容
			$mode = "普通交易";
			$currency = "人民币";
			$source = "欧易";

			// 格式化消息内容
			$message .= "群类型：       $type\n";
			$message .= "绑定信息：   $business_id\n";
			$message .= "调档：           $usdt_gears\n";
			$message .= "价格微调：   $usdt\n";
			$message .= "模式：           $mode\n";
			$message .= "货币：           $currency\n";
			$message .= "信息来源：   $source\n";
			$message .= "额度监控状态：   $quota_status\n";
			$message .= "额度监控阈值：   $quota\n";
			$this->reply_markup = [
				'inline_keyboard' => [
					[
						['text' => '汇率微调', 'callback_data' => 'usdt_hl'],
						['text' => '调档' . $usdt_gears, 'callback_data' => 'usdt_gears'],
					]
				]
			];
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_markup' => json_encode($this->reply_markup)
			];
			$this->sendForwardTextMessage($data);
		}
		//设置操作员
		if (strpos($text, 'szczy') !== false)
		{
			$this->checkOperator($chatId, $username);
			$operator = str_replace('szczy', '', $text);
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->where('type', 'in', '1,2')->find();

			if (!$group_info)
			{
				$message = '当前群里无法设置管理员请前往总群设置';
				$this->sendMessage($chatId, $message);
			}
			$operator_model = new BotOperator();
			$operator_id = $operator_model->where('operator', $operator)->where('business_id', $group_info['parent_id'])->value('id');
			if ($operator_id)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '已绑定该操作员',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$operator_model->operator = $operator;
			$operator_model->business_id = $group_info['parent_id'];
			$operator_model->status = 1;
			if (!$operator_model->save())
			{
				$message = '绑定失败请联系管理员';
			}
			else
			{
				$message = '绑定成功';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}
		//删除操作员
		if (strpos($text, 'scczy') !== false)
		{
			$this->checkOperator($chatId, $username);

			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->where('type', 'in', '1,2')->find();
			if (!$group_info)
			{
				$message = '当前群里无法设置管理员请前往总群设置';
				$this->sendMessage($chatId, $message);
			}

			$operator = str_replace('scczy', '', $text);
			$operator_model = new BotOperator();
			$operator_id = $operator_model->where('operator', $operator)->where('business_id', $group_info['parent_id'])->value('id');
			if (!$operator_id)
			{
				$data = [
					'chat_id' => $operator_id,
					'text' => '未绑定该操作员',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$result = $operator_model->where(['operator' => $operator, 'business_id' => $group_info['parent_id']])->delete();
			if (!$result)
			{
				$message = '删除失败请联系管理员';
			}
			else
			{
				$message = '删除成功';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}

		//删除全部操作员
		if ($text == 'scqbczy')
		{
			$this->checkOperator($chatId, $username);

			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->where('type', 'in', '1,2')->find();
			if (!$group_info)
			{
				$message = '当前群里无法设置管理员请前往总群设置';
				$this->sendMessage($chatId, $message);
			}
			$operator_model = new BotOperator();
			$result = $operator_model->where(['business_id' => $group_info['parent_id']])->delete();
			if (!$result)
			{
				$message = '删除失败请联系管理员';
			}
			else
			{
				$message = '删除成功';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}
		//设置监控额度
		if (strpos($text, 'szjked') !== false)
		{
			$this->checkOperator($chatId, $username);
			$quota_num = str_replace('szjked', '', $text);
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息无法设置监控请先绑定群聊信息',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$group_info->quota = $quota_num;
			if (!$group_info->save())
			{
				$message = '设置失败请联系管理员处理';
			}
			else
			{
				$message = '设置成功';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}
		//监控额度开启
		if ($text == 'jkedkq')
		{
			$this->checkOperator($chatId, $username);
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息无法设置监控请先绑定群聊信息',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			if ($group_info['quota_status'] == 1)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊额度监控已开启',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$group_info->quota_status = 1;
			if (!$group_info->save())
			{
				$message = '设置失败请联系管理员处理';
			}
			else
			{
				$message = '设置成功';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}
		//监控额度关闭
		if ($text == 'jkedgb')
		{
			$this->checkOperator($chatId, $username);
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息无法设置监控请先绑定群聊信息',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			if ($group_info['quota_status'] == -1)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊额度监控已关闭',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$group_info->quota_status = -1;
			if (!$group_info->save())
			{
				$message = '设置失败请联系管理员处理';
			}
			else
			{
				$message = '设置成功';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}

		//获取指定日期记账信息
		if (strpos($text, 'jz') !== false)
		{
			$this->checkOperator($chatId, $username);
			$day = str_replace('jz', '', $text);
			$year = date('Y'); // 获取当前年份

			$startTimestamp = date("$year-$day 00:00:00");
			$endTimestamp = date("$year-$day 23:59:59");
			$bot_group_model = new BotGroup();
			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$group_info)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定无法查看',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$bill_model = new BotBill();
			$where = [];
			$where[] = ['bot_group_id', '=', $group_info['id']];
			$where[] = ['create_time', '<', $endTimestamp];
			$where[] = ['create_time', '>', $startTimestamp];
			$result = $bill_model->where($where)->select()->toArray();
			$send_message = $year . '-' . $day . "日小计\n";
			if ($result)
			{
				$rz = 0;
				$xf = 0;
				foreach ($result as $key => $item)
				{
					if ($item['type'] == 1)
					{
						$rz += $item['money'];
					}
					else
					{
						$xf += $item['money'];
					}
					$send_message .= date('H:i', strtotime($item['create_time'])) . "    " . $item['money'] . "\n";
				}

				$wxf = $rz - $xf;
				$zj = count($result);

				$type = 'bank';
				$result = file_get_contents("https://www.okx.com/v3/c2c/tradingOrders/books?quoteCurrency=CNY&baseCurrency=USDT&side=sell&paymentMethod=$type&userType=all&receivingAds=false&t=1723128802289");
				$result = json_decode($result, true);
				$xf_usdt = 0;
				$wxf_usdt = 0;
				if (isset($result['data']['sell']))
				{
					$bot_group_model = new BotGroup();
					//检查当前商户是否有绑定
					$group_info = $bot_group_model->where("chat_id", $chatId)->field('business_id,usdt,usdt_gears')->find();
					$usdt_hl = isset($group_info['usdt']) ? $group_info['usdt'] : 0;   //汇率微调
					$usdt_gears = isset($group_info['usdt_gears']) ? $group_info['usdt_gears'] : 1;   //usdt用哪一个去计算 获取到的 10个中的键
					$hl = ($result['data']['sell'][$usdt_gears - 1]['price'] + $usdt_hl);
					$yxf_usdt = sprintf('%.2f', ($rz / $hl));
					$xf_usdt = sprintf('%.2f', ($xf / $hl));
					$wxf_usdt = sprintf('%.2f', ($wxf / $hl));
				}
				$send_message .= "总入款    $rz\n";
				$send_message .= "当前币价   $hl\n";
				$send_message .= "应下发    $rz|$yxf_usdt U\n";
				$send_message .= "已下发    $xf|$xf_usdt U\n";
				$send_message .= "未下发    $wxf|$wxf_usdt U\n";
				$send_message .= "共计 $zj 笔";
			}
			else
			{
				$send_message = '无数据';
			}
			$data = [
				'chat_id' => $chatId,
				'text' => $send_message,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}

		if ($text == 'qid')
		{
			$data = [
				'chat_id' => $chatId,
				'text' => '当前群聊ID：' . $chatId,
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}

		if ($text == '/help@' . $this->bot_name)
		{
			$data = [
				'chat_id' => $chatId,
				'text' => "
命令          示例                      描述                
qfall          回复要群发的信息qfall                  群发信息到所有群
qfms        回复要群发的信息qfms                群发信息到码商群
qfsh        回复要群发的信息qfsh                群发信息到商户群
jrpl                  jrpl                  查询跑量 当天
zrpl                 zrpl                  查询跑量 昨天
lspl                 lspl                  查询跑量 全部
k、z、w      k1000               查询欧意实时汇率 k=银行卡 z=支付宝 w=微信
加                   加1000               给卡商或商户增加额度
减                   减1000               给卡商或商户减少额度
+                   +1000              群内记账
-                   -1000              群内记账
ed                  ed                  获取当前群聊的额度
rj                  rj                  跑量日结
szczy             szczy@jqk55555      设置操作员  @jqk55555 请替换为操作员用户号
scczy             scczy@jqk55555      删除操作员  @jqk55555 请替换为操作员用户号
scqbczy           scqbczy             删除全部操作员
szjked            szjked10000         设置监控额度
jkedkq            jkedkq              监控额度开启
jkedgb            jkedgb              监控额度关闭    
jz                  jz09-29             获取指定日期的记账日志
qid               qid             获取当前群聊ID
跨群聊天:回复机器人发送的信息即可",
			];
			$this->sendForwardTextMessage($data);
		}

		if ($text == '/channel@' . $this->bot_name)
		{

			$list = $this->bot_function->getChannel();
			if (!empty($list))
			{
				foreach ($list as &$item)
				{
					$send_message .= $item['name'] . " : " . $item['id'] . "\n";
				}
				unset($item);
			}
			$this->sendMessage($chatId, $send_message);
		}


		if ($text == '/gateway@' . $this->bot_name)
		{
			$send_message = "服务器IP\n8.217.216.132\n接口网关\nhttps://api.jqkpay.top/\n接口文档\nhttps://doc.jqkpay.top/document/api_create\nAPI下单地址\nhttps://api.jqkpay.top/order/create\nAPI查询\nhttps://api.jqkpay.top/order/query\n商户后台\nhttps://bizend.jqkpay.top/";
			$this->sendMessage($chatId, $send_message);
		}

		if ($text == '/tutorial@' . $this->bot_name)
		{
			$arr = [
				"网银转卡 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/bank.zip",
				'手机银行转钱包 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/mbank_to_ewallet.zip',
				'USDT : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/usdt.zip',
				'数字人民币 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/rmb.zip',
				'支付宝UID : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_transfer.zip',
				'支付宝二维码 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_qrcode.zip',
				'支付宝账号 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_account.zip',
				'支付宝批量转账 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_batch.zip',
				'支付宝扫码点单 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_scan_to_order.zip',
				'支付宝钉钉红包 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_dingding.zip',
				'支付宝小荷包 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_xiaohebao.zip',
				'支付宝小荷包2 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_xiaohebao2.zip',
				'支付宝小钱袋 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_xiaoqiandai.zip',
				'支付宝口令红包 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_koulinghongbao.zip',
				'支付宝手机网站 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_wap.zip',
				'支付宝当面付 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_f2f.zip',
				'支付宝订单码 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_order_code.zip',
				'支付宝转账码 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_transfer_code.zip',
				'支付宝AA收款 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/alipay_aa.zip',
				'淘宝直付 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/taobao_zhifu.zip',
				'周转码 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/taobao_daifu.zip',
				'淘宝代付 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/taobao_daifu2.zip',
				'淘宝零钱 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/taobao_lingqian.zip',
				'京东E卡 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/jingdong_eka.zip',
				'京东微信代付 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/jingdong_weixin_daifu.zip',
				'聚合码 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/juhema.zip?4',
				'云闪付 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/yunshanfu.zip',
				'微信赞赏码 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/weixin_praise_code.zip',
				'微信群 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/weixin_group.zip',
				'qq转账 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/qq_transfer.zip',
			];
			$send_message = implode("\n", $arr);
			$this->sendMessage($chatId, $send_message);
		}

		if ($text == '/monitor@' . $this->bot_name)
		{
			$arr = [
				"APP监控 : http://47.99.180.81:36008/jqkpay_v1.0.apk",
				"微信公众号监控 : 
安装包
https://jqkpay.oss-cn-hongkong.aliyuncs.com/jqkpay/微信公众号助手安装包1.1.exe

压缩包
https://jqkpay.oss-cn-hongkong.aliyuncs.com/jqkpay/公众号消息助手1.1.zip",
				// '后台扫码登陆微信监控 : https://jqkstore.oss-cn-hongkong.aliyuncs.com/document/wechat_monitor.zip'
			];
			$send_message = implode("\n", $arr);
			$this->sendMessage($chatId, $send_message);
		}


		//匹配后台问题关键字
		$question_model = new BotQuestion();
		$question = $question_model->where([])->select()->toArray();
		if (empty($question))
		{
			return;
		}
		foreach ($question as &$item)
		{
			$key = explode(',', $item['key']);
			foreach ($key as $k => $value)
			{
				if ($text == $value)
				{
					$send_message = $item['reply'];
					break;
				}
			}
			if ($send_message)
			{
				break;
			}
		}
		unset($item);
		//发送消息
		self::sendMessage($chatId, $send_message);

	}

	//发送请求
	function url_get_contents($Url)
	{
		if (!function_exists('curl_init'))
		{
			die('CURL is not installed!');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		curl_close($ch);
		return json_decode($output, true);
	}

	/**发送消息到相应的群组
	 * $chatID                  聊天id
	 * $send_message            回复的消息内容
	 * $reply_markup            按钮信息
	 * **/
	public function sendMessage($chatId, $send_message)
	{
		$message = [
			'chat_id' => $chatId,
			'text' => urldecode($send_message),
			'disable_web_page_preview' => true
		];
		if ($this->reply_markup)
		{
			$message['reply_markup'] = json_encode($this->reply_markup);
		}
		$url = 'https://api.telegram.org/bot' . $this->token . "/sendMessage";
		self::httpsRequest($url, $message);
		exit;
	}

	// 发送文本消息的函数
	function sendForwardTextMessage($data)
	{
		$url = "https://api.telegram.org/bot" . $this->token . "/sendMessage";
		self::sendRequest($url, $data);
		exit;
	}

	// 查单发送文本消息的函数
	function sendOrderForwardTextMessage($data)
	{
		$url = "https://api.telegram.org/bot" . $this->token . "/sendMessage";
		return self::sendRequest($url, $data);
	}

	/**转发图片消息到对应的群组
	 */
	public function sendForwardMessage($data)
	{
		$url = "https://api.telegram.org/bot" . $this->token . "/sendPhoto";
		return self::sendRequest($url, $data);
	}

	// 发送请求的函数
	function sendRequest($url, $data)
	{
		$options = [
			'http' => [
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data),
			],
		];
		$context = stream_context_create($options);
		return file_get_contents($url, false, $context);
	}

	/**按钮回调消息
	 *  $data           接受到的按钮信息、
	 *  $chat_id        聊天id
	 **/
	public function callbackMessage($message_data, $chat_id)
	{
		$bot_group_model = new BotGroup();
		$business = $bot_group_model->where('chat_id', $chat_id)->find();
		$data = $message_data['data'];
		switch ($data)
		{
			case 'usdt_hl':
				if (!$business)
				{
					$data = [
						'chat_id' => $chat_id,
						'text' => '当前群聊未绑定信息无法操作',
					];
					$this->sendForwardTextMessage($data);
				}
				$usdt_hl = isset($business['usdt']) ? $business['usdt'] : 0;
				$text = '汇率微调 支持在原有汇率得基础上加减汇率当前：' . $usdt_hl;
				$keyboardEncoded = [
					'inline_keyboard' => [
						[
							['text' => '加一分', 'callback_data' => 'add1'],
							['text' => '减一分', 'callback_data' => 'del1'],
						],
						[
							['text' => '群聊信息', 'callback_data' => 'group'],
						]
					]
				];
				$this->editTelegramMessage($chat_id, $message_data['message']['message_id'], $text, $keyboardEncoded);
				break;
			case 'add1':
				if (!$business)
				{
					$data = [
						'chat_id' => $chat_id,
						'text' => '当前群聊未绑定信息无法操作',
					];
					$this->sendForwardTextMessage($data);
				}
				$usdt_hl = isset($business['usdt']) ? $business['usdt'] : 0;
				$usdt_hl += 0.01;
				$bot_group_model->where(['id' => $business['id']])->update(['usdt' => $usdt_hl]);
				$text = '汇率微调 支持在原有汇率得基础上加减汇率当前：' . $usdt_hl;
				$keyboardEncoded = [
					'inline_keyboard' => [
						[
							['text' => '加一分', 'callback_data' => 'add1'],
							['text' => '减一分', 'callback_data' => 'del1'],
						],
						[
							['text' => '群聊信息', 'callback_data' => 'group'],
						]
					]
				];
				;
				$this->editTelegramMessage($chat_id, $message_data['message']['message_id'], $text, $keyboardEncoded);
				break;
			case 'del1':
				if (!$business)
				{
					$data = [
						'chat_id' => $chat_id,
						'text' => '当前群聊未绑定信息无法操作',
					];
					$this->sendForwardTextMessage($data);
				}
				$usdt_hl = isset($business['usdt']) ? $business['usdt'] : 0;
				$usdt_hl = $usdt_hl - 0.01;
				$bot_group_model->where(['id' => $business['id']])->update(['usdt' => $usdt_hl]);
				$text = '汇率微调 支持在原有汇率得基础上加减汇率当前：' . $usdt_hl;
				$keyboardEncoded = [
					'inline_keyboard' => [
						[
							['text' => '加一分', 'callback_data' => 'add1'],
							['text' => '减一分', 'callback_data' => 'del1'],
						],
						[
							['text' => '群聊信息', 'callback_data' => 'group'],
						]
					]
				];
				;
				$this->editTelegramMessage($chat_id, $message_data['message']['message_id'], $text, $keyboardEncoded);
				break;
			case 'group':
				$message = "群聊信息\n";
				if ($business)
				{
					if ($business['type'] == 1)
					{        //商户
						$type = '商户';
					}
					elseif ($business['type'] == 2)
					{       //卡商
						$type = '卡商';
					}
					elseif ($business['type'] == 3)
					{       //四方
						$type = '四方';
					}
					else
					{
						$type = '四方商户';               //四方商户
					}
					$business_id = $business['business_id'] ? $business['business_id'] : $business['parent_id'];
				}
				else
				{
					$business_id = '';
					$type = '';
				}
				$usdt = isset($business['usdt']) ? $business['usdt'] : 0;
				$usdt_gears = isset($business['usdt_gears']) ? $business['usdt_gears'] : 1;   //调档
				$quota = isset($business['quota']) ? $business['quota'] : '';
				$quota_status = isset($business['quota_status']) ? $business['quota_status'] == 1 ? '开启' : '关闭' : '';

				// 定义需要发送的消息内容
				$mode = "普通交易";
				$currency = "人民币";
				$source = "欧易";

				// 格式化消息内容
				$message .= "群类型：       $type\n";
				$message .= "绑定信息：   $business_id\n";
				$message .= "调档：           $usdt_gears\n";
				$message .= "价格微调：   $usdt\n";
				$message .= "模式：           $mode\n";
				$message .= "货币：           $currency\n";
				$message .= "信息来源：   $source\n";
				$this->reply_markup = [
					'inline_keyboard' => [
						[
							['text' => '汇率微调', 'callback_data' => 'usdt_hl'],
							['text' => '调档' . $usdt_gears, 'callback_data' => 'usdt_gears'],
						],
					]
				];
				$this->editTelegramMessage($chat_id, $message_data['message']['message_id'], $message, $this->reply_markup);
				break;
			case 'usdt_gears':
				$usdt_gears = isset($business['usdt_gears']) ? $business['usdt_gears'] : 1;   //调档
				$text = "设置计算规则 默认计算第一个卖家\n当前: $usdt_gears\n数据来源：欧易";
				$keyboardEncoded = [
					'inline_keyboard' => [
						[
							['text' => '1', 'callback_data' => 'gears1'],
							['text' => '2', 'callback_data' => 'gears2'],
							['text' => '3', 'callback_data' => 'gears3'],
							['text' => '4', 'callback_data' => 'gears4'],
							['text' => '5', 'callback_data' => 'gears5'],
						],
						[
							['text' => '6', 'callback_data' => 'gears6'],
							['text' => '7', 'callback_data' => 'gears7'],
							['text' => '8', 'callback_data' => 'gears8'],
							['text' => '9', 'callback_data' => 'gears9'],
							['text' => '10', 'callback_data' => 'gears10'],
						],
						[
							['text' => '群聊信息', 'callback_data' => 'group'],
						]
					]
				];
				foreach ($keyboardEncoded['inline_keyboard'][0] as $key => $value)
				{
					if ($value['text'] == $usdt_gears)
					{
						$keyboardEncoded['inline_keyboard'][0][$key]['text'] = $value['text'] . "✅";
					}
				}
				$this->editTelegramMessage($chat_id, $message_data['message']['message_id'], $text, $keyboardEncoded);
				break;
			//            case 'operator':
//                $company_model = new XgOperator();
//                $company_list = $company_model->selectAll(['chat_id'=>$chat_id])['data'];
//                $text = "操作员信息列表:\n";
//                if($company_list){
//                    foreach($company_list as $key){
//                        $text .=$key['operator']."\n";
//                    }
//                }else{
//                    $text .='暂无信息';
//                }
//                $keyboardEncoded = [
//                    'inline_keyboard' => [
//                        [
//                            ['text' => '群聊信息', 'callback_data' => 'group'],
//                        ]
//                    ]
//                ];
//                $this->editTelegramMessage($chat_id,$message_data['message']['message_id'],$text,$keyboardEncoded);
//                break;
			case 'gears1':
				$this->usdtGears($message_data, $chat_id, 1, $business);
				break;
			case 'gears2':
				$this->usdtGears($message_data, $chat_id, 2, $business);
				break;
			case 'gears3':
				$this->usdtGears($message_data, $chat_id, 3, $business);
				break;
			case 'gears4':
				$this->usdtGears($message_data, $chat_id, 4, $business);
				break;
			case 'gears5':
				$this->usdtGears($message_data, $chat_id, 5, $business);
				break;
			case 'gears6':
				$this->usdtGears($message_data, $chat_id, 6, $business);
				break;
			case 'gears7':
				$this->usdtGears($message_data, $chat_id, 7, $business);
				break;
			case 'gears8':
				$this->usdtGears($message_data, $chat_id, 8, $business);
				break;
			case 'gears9':
				$this->usdtGears($message_data, $chat_id, 9, $business);
				break;
			case 'gears10':
				$this->usdtGears($message_data, $chat_id, 10, $business);
				break;
		}
	}

	public function usdtGears($message_data, $chat_id, $usdt_gears, $business)
	{
		if (!$business)
		{
			$data = [
				'chat_id' => $chat_id,
				'text' => '当前群聊未绑定信息无法操作',
			];
			$this->sendForwardTextMessage($data);
		}
		$business->usdt_gears = $usdt_gears;
		$business->save();
		$text = "设置计算规则 默认计算第一个卖家\n当前: $usdt_gears\n数据来源：欧易";
		$keyboardEncoded = [
			'inline_keyboard' => [
				[
					['text' => '1', 'callback_data' => 'gears1'],
					['text' => '2', 'callback_data' => 'gears2'],
					['text' => '3', 'callback_data' => 'gears3'],
					['text' => '4', 'callback_data' => 'gears4'],
					['text' => '5', 'callback_data' => 'gears5'],
				],
				[
					['text' => '6', 'callback_data' => 'gears6'],
					['text' => '7', 'callback_data' => 'gears7'],
					['text' => '8', 'callback_data' => 'gears8'],
					['text' => '9', 'callback_data' => 'gears9'],
					['text' => '10', 'callback_data' => 'gears10'],
				],
				[
					['text' => '群聊信息', 'callback_data' => 'group'],
				]
			]
		];
		foreach ($keyboardEncoded['inline_keyboard'][0] as $key => $value)
		{
			if ($value['text'] == $usdt_gears)
			{
				$keyboardEncoded['inline_keyboard'][0][$key]['text'] = $value['text'] . "✅";
			}
		}
		foreach ($keyboardEncoded['inline_keyboard'][1] as $key => $value)
		{
			if ($value['text'] == $usdt_gears)
			{
				$keyboardEncoded['inline_keyboard'][1][$key]['text'] = $value['text'] . "✅";
			}
		}
		$this->editTelegramMessage($chat_id, $message_data['message']['message_id'], $text, $keyboardEncoded);
	}

	function editTelegramMessage($chatId, $messageId, $newText, $keyboardEncoded)
	{
		$url = "https://api.telegram.org/bot$this->token/editMessageText";

		$postFields = [
			'chat_id' => $chatId,
			'message_id' => $messageId,
			'text' => $newText,
			'reply_markup' => json_encode($keyboardEncoded)
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	public function checkOperator($chatId, $username)
	{
		$group_model = new BotGroup();
		$group_info = $group_model->where('chat_id', $chatId)->find();
		if (!$group_info)
		{
			$data = [
				'chat_id' => $chatId,
				'text' => '未绑定信息不可操作',
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
			exit;
		}

		if ($group_info['operator_status'] == -1)
		{  //所有人可操作
			return true;
		}

		$business_id = $group_info['parent_id'];

		$admin = ['JQK999999999', 'JQK6666666', 'JQK777777', 'JQK55555', 'JQK188888'];
		if (in_array($username, $admin))
		{
			return true;
		}

		$operator_model = new BotOperator();
		$is_operator = $operator_model->where('business_id', $business_id)->where('operator', '@' . $username)->find();
		if (!$is_operator)
		{
			$data = [
				'chat_id' => $chatId,
				'text' => '非操作员不可操作',
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
			exit;
		}
		return true;
	}

	public static function httpsRequest($url, $data = '')
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if ($data)
		{
			$data_string = json_encode($data);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt(
				$curl,
				CURLOPT_HTTPHEADER,
				array(
					'X-AjaxPro-Method:ShowList',
					'Content-Type: application/json; charset=utf-8',
					'Content-Length: ' . strlen($data_string)
				)
			);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
		}
		$request = curl_exec($curl);
		curl_close($curl);
		$tmp_arr = json_decode($request, true);
		if (is_array($tmp_arr))
		{
			return $tmp_arr;
		}
		else
		{
			return $request;
		}
	}
}
