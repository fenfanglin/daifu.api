<?php

namespace app\service\bot;

use app\admin\controller\BotHandleController;
use app\model\BotBill;
use app\model\BotForward;
use app\model\BotGroup;
use app\model\BotBatch;
use app\model\BotOperator;
use app\model\Business;
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
			//处理普通文本信息
			if (isset($this->message_data["message"]["text"]))
			{
				$message = $this->message_data["message"];
				$this->textMessage($message, $chatId);
			}
		}
		//返回菜单选项
		if (isset($this->message_data['callback_query']))
		{
			$chatId = $this->message_data["callback_query"]['message']["chat"]["id"];
			//群验证
			// $this->checkOperator($chatId, $this->message_data['callback_query']['from']['username']);
			$this->callbackMessage($this->message_data["callback_query"], $chatId);
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

		//U地址确认
		$pattern = '/^[A-Za-z0-9]{34}\s[0-9]+[A-Za-z]$/';
		$is_usdt = preg_match($pattern, $text, $matche);
		if ($is_usdt)
		{
			$usdt = $matche[0];
			$usdt = explode(" ", $usdt)[0];
			$global_redis = Common::global_redis();
			$key = $chatId . $usdt;
			$u_num = $global_redis->get($key) ?? 0;
			$data = [
				'chat_id' => $chatId,
				'text' => "地址：" . $usdt . "\n该地址群里出现次数：" . ($u_num + 1),
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$global_redis->set($key, ($u_num + 1));
			$this->sendForwardTextMessage($data);
		}

		$pattern = '/^[A-Za-z0-9]{34}$/';
		$is_usdt = preg_match($pattern, $text, $matc);
		if ($is_usdt)
		{
			$usdt = $matc[0];
			$global_redis = Common::global_redis();
			$key = $chatId . $usdt;
			$u_num = $global_redis->get($key) ?? 0;
			$data = [
				'chat_id' => $chatId,
				'text' => "地址：" . $usdt . "\n该地址群里出现次数：" . ($u_num + 1),
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$global_redis->set($key, ($u_num + 1));
			$this->sendForwardTextMessage($data);
		}

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
				switch ($order_info['status'])
				{
					case -1:    //未支付  转发码商
						self::sendMessage($chatId, '订单未支付');
						exit;
					case -2:    //未支付  转发码商
						self::sendMessage($chatId, '支付失败');
						exit;
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


		if (substr($text, 0, 3) == 'bd-')
		{
			$dl = str_replace(substr($text, 0, 3), '', $text);
			$model = new BotGroup();
			$group = $model->where('business_id', $dl)->find();
			if ($group)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '此商户已经绑定过了：' . $group['business_id'],
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			$group = $model->where('chat_id', $chatId)->find();
			if ($group)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊已经绑定过了：' . $group['business_id'],
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			$model->name = $username;
			$model->chat_id = $chatId;
			$model->business_id = $dl;
			$model->status = 1;
			$data = [
				'chat_id' => $chatId,
				'text' => '绑定成功'
			];
			if (!$model->save())
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '绑定失败'
				];
			}
			$this->sendForwardTextMessage($data);
			exit;
		}

		if (substr($text, 0, 5) == 'scbd')
		{
			$model = new BotGroup();
			$group = $model->where('chat_id', $chatId)->find();
			if (!$group)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息',
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			$model->where('id', $group->id)->delete();
			$data = [
				'chat_id' => $chatId,
				'text' => '清空完成'
			];
			$this->sendForwardTextMessage($data);
			exit;
		}

		// 		if () {
//             $mone = eval("return $text;");

		//             $bot_group_model = new Botgroup();
// 			$business = $bot_group_model->where('chat_id', $chatId)->find();
// 			$usdt_hl = $business ? $business['usdt'] : 0;

		//             $result = file_get_contents("https://www.okx.com/v3/c2c/tradingOrders/books?quoteCurrency=CNY&baseCurrency=USDT&side=sell&paymentMethod=all&userType=all&receivingAds=false&t=1723128802289");
// 			$result = json_decode($result, true);
// 			$info = "OTC商家实时价格\n筛选:OTC商家实时价格\n";
// 			foreach ($result['data']['sell'] as $key => $value)
// 			{
// 				$info .= $value['price'] + $usdt_hl . '   ' . $value['nickName'] . "\n";
// 				if ($key == 10)
// 				{
// 					break;
// 				}
// 			}
// 			$hl = ($result['data']['sell'][0]['price']);
// 			$jg = sprintf('%.2f', ($money / $hl));
// 			$info .= "币数 ：(" . $money . " ÷ " . $hl . ") = " . $jg . "USDT";

		// 			$data = [
// 				'chat_id' => $chatId,
// 				'text' => $info,
// 			];
// 			$this->sendForwardTextMessage($data);
//         }

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
					'text' => '当前群聊不可执行当前操作！',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}
			$business_type = 2;
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
			$this->sendForwardTextMessageTwo($data);
			$model = Business::where('id', $business['business_id'])->find();
			if ((int) $result['last_quota'] < 5000)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => "尊敬的【" . $model->realname . "】vip客户\n您现在的可用余额【" . $result['last_quota'] . "】\n已经低于【5000】\n为了不影响您的正常代付,请尽快充值!",
					// 	'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}

		}

		$pattern = '/^(\([^()]+\)|[0-9]+(\.[0-9]+)*)+(\([^()]+\)|[0-9]+(\.[0-9]+)*|\*|\/|\+|\-)*$/';
		if (preg_match($pattern, $text))
		{

			$result = null;
			eval ('$result = ' . $text . ';');
			$money = $result;

			$data = [
				'chat_id' => $chatId,
				'text' => $text . ' = ' . number_format($money, 2),
				'reply_to_message_id' => $this->message_data['message']['message_id']
			];
			$this->sendForwardTextMessage($data);
		}


		//群内记账
		if (substr($text, 0, 1) == '+' || substr($text, 0, 1) == '-')
		{
			// 			$this->checkOperator($chatId, $username);
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

			// 			$group_info = $bot_group_model->where('chat_id', $chatId)->find();
// 			if (!$group_info)
// 			{
// 				$data = [
// 					'chat_id' => $chatId,
// 					'text' => '当前群聊未信息无法操作',
// 					'reply_to_message_id' => $this->message_data['message']['message_id']
// 				];
// 				$this->sendForwardTextMessage($data);
// 			}

			$bill_model = new BotBill();
			// 			$bill_model->bot_group_id = $group_info['id'];
			$bill_model->chat_id = $chatId;
			$bill_model->operator = $username;
			$bill_model->type = $type;
			//$bill_model->business_id = $group_info['business_id'] ? $group_info['business_id'] : $group_info['parent_id'];
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
			// 			$where[] = ['bot_group_id', '=', $group_info['id']];
			$where[] = ['chat_id', '=', $chatId];
			//$where[] = ['business_id', '=', $group_info['business_id'] ? $group_info['business_id'] : $group_info['parent_id']];
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
					$send_message .= date('H:i:s', strtotime($item['create_time'])) . "    $jj" . $item['money'] . "\n";
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

		if ($text == 'ye')
		{
			$bot_group_model = new Botgroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();
			if (!$business)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '未绑定信息',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
			$model = Business::where('id', $business->business_id)->find();
			switch ($model->type)
			{
				case 1:
					$data = [
						'chat_id' => $chatId,
						'text' => '代理余额：' . $model->money,
						'reply_to_message_id' => $this->message_data['message']['message_id']
					];
				case 2:
					$data = [
						'chat_id' => $chatId,
						'text' => '工作室可提现金额：' . $model->allow_withdraw,
						'reply_to_message_id' => $this->message_data['message']['message_id']
					];
				case 3:
					$data = [
						'chat_id' => $chatId,
						'text' => '商户余额：' . $model->allow_withdraw,
						'reply_to_message_id' => $this->message_data['message']['message_id']
					];
					break;
			}
			$this->sendForwardTextMessageTwo($data);

			if ((int) $model->allow_withdraw < 5000)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => "尊敬的【" . $model->realname . "】vip客户\n您现在的可用余额【" . $model->allow_withdraw . "】\n已经低于【5000】\n为了不影响您的正常代付,请尽快充值!",
					// 	'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
			}

			exit;

		}

		if (substr($text, 0, 2) == 'hl')
		{
			$type = substr($text, 2, 1) == '+' ? 1 : 2;
			$num = substr($text, 3);
			$bot_group_model = new BotGroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();

			if (!$business)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '当前群聊未绑定信息无法操作',
				];
				$this->sendForwardTextMessage($data);
			}
			$usdt_hl = isset($business['usdt']) ? $business['usdt'] : 0;

			if ($type == 1)
			{
				$usdt_hl += (float) $num;
				$bot_group_model->where(['id' => $business['id']])->update(['usdt' => $usdt_hl]);
			}
			else
			{
				$usdt_hl -= (float) $num;
				$bot_group_model->where(['id' => $business['id']])->update(['usdt' => $usdt_hl]);
			}
			$data = [
				'chat_id' => $chatId,
				'text' => '设置成功当前汇率' . $usdt_hl,
			];
			$this->sendForwardTextMessage($data);

		}

		if ($text == 'bdxx')
		{

			$bot_group_model = new Botgroup();
			$business = $bot_group_model->where('chat_id', $chatId)->find();

			if ($business)
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '绑定信息：' . $business['business_id'],
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];

				$this->sendForwardTextMessage($data);
				exit;
			}
			else
			{
				$data = [
					'chat_id' => $chatId,
					'text' => '未绑定',
					'reply_to_message_id' => $this->message_data['message']['message_id']
				];
				$this->sendForwardTextMessage($data);
				exit;
			}
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
k、z、w      k1000               查询欧意实时汇率 k=银行卡 z=支付宝 w=微信
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

	// 发送文本消息的函数
	function sendForwardTextMessageTwo($data)
	{
		$url = "https://api.telegram.org/bot" . $this->token . "/sendMessage";
		self::sendRequest($url, $data);
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
		$admin = ['JQK999999999', 'JQK6666666', 'JQK777777', 'JQK55555', 'JQK188888', 'JQK181818', 'hhllip', 'HongLeongPay', 'HongLeongPay99', 'pao668868'];
		if (in_array($username, $admin))
		{
			return true;
		}

		$data = [
			'chat_id' => $chatId,
			'text' => '无操作权限',
			'reply_to_message_id' => $this->message_data['message']['message_id']
		];
		$this->sendForwardTextMessage($data);
		exit;
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
