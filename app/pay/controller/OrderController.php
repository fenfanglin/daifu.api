<?php

namespace app\pay\controller;

use app\extend\common\Common;
use app\model\Channel;
use app\model\ChannelAccount;
use app\model\Business;
use app\model\BusinessChannel;
use app\model\Order;
use app\service\BusinessService;
use app\service\api\Jinqianbao;

class OrderController extends AuthController
{
	// 以下参数都在控制器__init()生成
	private $params; //接口请求参数
	private $business; //商户对象

	private $secret_key; //商户密钥

	private $usdt_rate; //USDT汇率
	private $system_rate; //系统费用费率

	private $sub_business_id = 0; //商户id
	private $sub_business_rate = 0; //四方商户提现费率
	private $card_business_ids = false; //卡商ids

	/**
	 * 初始化
	 */
	private function __init()
	{
		$this->writeLog(); //写入操作日志

		$this->checkParams(); //初步验证参数，获取$this->params

		$this->checkBusiness(); //用户检测，获取$this->business

		$this->checkSign($this->params, $this->secret_key); //验证签名

		$this->setData(); //生成其他参数
	}

	/**
	 * 下单
	 */
	public function create()
	{
		$this->__init();
		$data = $this->params;

		Common::writeLog($data, 'order_create');

		// 检查商户订单号是否重复
		$check = Order::checkOutTradeNo($data['out_trade_no']);
		if (!$check)
		{
			$this->orderError('重复订单号');
		}

		// 工作室类型：1人工转账 2三方转账
		if ($this->business->card_type == 1)
		{
			// 获取工作室
			$card_business = $this->getCardBusiness();
		}
		else
		{
			// 获取收款账号
			$channel_account = $this->getChannelAccount();
			$card_business = $channel_account->cardBusiness ?? NULL;

			if (!$card_business)
			{
				$this->orderError('无可用工作室3');
			}
		}


		$amount = $data['amount'];


		$agent_commission = $this->business->parent->commission ?? 0;
		$agent_order_rate = $this->business->parent->order_rate ?? 0;
		$agent_order_fee = number_format($agent_order_rate * $amount, 4, '.', '');

		$card_commission = $card_business->commission ?? 0;
		$card_order_rate = $card_business->order_rate ?? 0;
		$card_order_fee = number_format($card_order_rate * $amount, 4, '.', '');

		$business_commission = $this->business->commission ?? 0;
		$business_order_rate = $this->business->order_rate ?? 0;
		$business_order_fee = number_format($business_order_rate * $amount, 4, '.', '');

		$info = [
			'agent_commission' => $agent_commission,
			'agent_order_rate' => $agent_order_rate,
			'agent_order_fee' => $agent_order_fee,
			'card_commission' => $card_commission,
			'card_order_rate' => $card_order_rate,
			'card_order_fee' => $card_order_fee,
			'business_commission' => $business_commission,
			'business_order_rate' => $business_order_rate,
			'business_order_fee' => $business_order_fee,
		];

		$order = new Order();
		$order->account_type = $data['account_type'];
		$order->business_id = $this->business->parent->id;
		$order->sub_business_id = $this->business->id;
		$order->card_business_id = $card_business->id ?? 0;
		$order->amount = $amount;
		$order->agent_commission = $agent_commission;
		$order->agent_order_rate = $agent_order_rate;
		$order->agent_order_fee = $agent_order_fee;
		$order->card_commission = $card_commission;
		$order->card_order_rate = $card_order_rate;
		$order->card_order_fee = $card_order_fee;
		$order->business_commission = $business_commission;
		$order->business_order_rate = $business_order_rate;
		$order->business_order_fee = $business_order_fee;
		$order->out_trade_no = $data['out_trade_no'];
		$order->ip = Common::getClientIp();
		$order->notify_url = $data['notify_url'];
		$order->attach = $params['attach'] ?? '';
		$order->remark = $params['remark'] ?? '';
		$order->info = json_encode($info, JSON_UNESCAPED_UNICODE);
		$order->status = -1;

		if ($data['account_type'] == 1) //银行卡
		{
			$order->bank = $data['bank'];
			$order->branch = $data['branch'];
			$order->account_name = $data['account_name'];
			$order->account = $data['account'];
		}
		elseif ($data['account_type'] == 2) //USDT
		{
			$order->account = $data['account'];
			$order->usdt_rate = $this->usdt_rate;
			$order->usdt_amount = number_format($data['amount'] / $this->usdt_rate, '2', '.', '');
		}
		elseif ($data['account_type'] == 3) //支付宝
		{
			$order->account_name = $data['account_name'];
			$order->account = $data['account'];
		}

		if (!$order->save())
		{
			$this->orderError('生成订单失败');
		}

		if ($this->business->parent->id == 30306)
		{
			if ($data['account_type'] == 1) //银行卡
			{
				$temp = [
					'channelCode' => 'bank',
				];
			}
			elseif ($data['account_type'] == 4) //数字人民币
			{
				$temp = [
					'channelCode' => 'digital',
				];
			}
			elseif ($data['account_type'] == 3) //支付宝
			{
				$temp = [
					'channelCode' => 'alipay',
				];
			}
			$jinqianbao = new Jinqianbao();
			$jinqianbao->create($temp);
		}

		// 工作室类型：1人工转账 2三方转账
		if ($this->business->card_type == 2)
		{
			if (!$channel_account)
			{
				$this->orderError('三方信息不存在');
			}

			$channel_id = $channel_account->channel_id ?? NULL;

			$order->channel_id = $channel_id;
			$order->channel_account_id = $channel_account->id;
			$order->save();

			if ($channel_id == 1) //瞬达通
			{
				$config = [
					'mchid' => $channel_account->mchid ?? '',
					'appid' => $channel_account->appid ?? '',
					'key_secret' => $channel_account->key_secret ?? '',
				];

				if (!$config['mchid'] || !$config['appid'] || !$config['key_secret'])
				{
					$order->status = -2;
					$order->save();

					$this->orderError('工作室参数不正确');
				}

				$service = new \app\service\api\ShundatongService($config);

				$data = [
					'out_trade_no' => $order->out_trade_no,
					'amount' => $order->amount,
					'account_type' => $order->account_type,
					'account' => $order->account,
					'account_name' => $order->account_name,
					'bank' => $order->bank,
				];

				$res = $service->create($data);

				if (!isset($res['status']) || $res['status'] != 'SUCCESS')
				{
					$order->status = -2;
					$order->save();

					$this->orderError($res['msg'] ?? '下单失败');
				}
			}
			elseif ($channel_id == 2) //鼎薪通
			{
				// $config = [
				// 	'mchid' => $channel_account->mchid ?? '',
				// 	'appid' => $channel_account->appid ?? '',
				// 	'key_id' => $channel_account->key_id ?? '',
				// 	'key_secret' => $channel_account->key_secret ?? '',
				// ];

				// if (!$config['mchid'] || !$config['appid'] || !$config['key_id'] || !$config['key_secret'])
				// {
				// 	$order->status = -2;
				// 	$order->save();

				// 	$this->orderError('工作室参数不正确');
				// }

				// $service = new \app\service\api\DingxintongService($config);

				// $data = [
				// 	'out_trade_no' => $order->out_trade_no,
				// 	'amount' => $order->amount,
				// 	'account_type' => $order->account_type,
				// 	'account' => $order->account,
				// 	'account_name' => $order->account_name,
				// 	'bank' => $order->bank,
				// ];

				// $res = $service->create($data);

				// if (!isset($res['status']) || $res['status'] != 'SUCCESS')
				// {
				// 	$order->status = -2;
				// 	$order->save();

				// 	$this->orderError($res['msg'] ?? '下单失败');
				// }

				$order->api_status = -1; //下单状态：-1未下单 1成功 -2失败
				$order->save();
			}
		}

		// 扣商户余额--订单金额
		$remark = "扣{$order->amount}订单金额";
		BusinessService::changeAllowWithdraw($order->sub_business_id, -$order->amount, 2, $order->id, $remark);

		// 扣商户余额--订单费用
		$remark = "扣{$info['business_commission']}固定费用，{$info['business_order_fee']}订单费用";
		BusinessService::changeAllowWithdraw($order->sub_business_id, -($info['business_commission'] + $info['business_order_fee']), 1, $order->id, $remark);

		$return_data = [];
		$return_data['order_no'] = $order->order_no;
		$return_data['out_trade_no'] = $order->out_trade_no;
		$return_data['amount'] = $order->amount;

		if ($data['account_type'] == 2)
		{
			$return_data['usdt_amount'] = $order->usdt_amount;
			$return_data['usdt_rate'] = $order->usdt_rate;
		}

		return $this->returnData($return_data);
	}

	/**
	 * 初步验证参数，获取$this->params
	 */
	private function checkParams()
	{
		$params = input('post.');
		$rule = [
			'mchid|商户编号' => 'require|integer|>:0',
			'account_type|收款类型' => 'require|integer|in:1,2,3,4',
			'out_trade_no|商户订单号' => 'require|alphaNum|max:30',
			'amount|金额' => 'require|float|>:0',
			'notify_url|回调连接' => 'require|max:255',
			'timestamp|下单时间' => 'require|integer|>:0',
			'attach|附加数据' => 'max:255',
			'sign|签名' => 'require',
		];

		$message = [
			'mchid' => '商户编号不正确',
			'account_type' => '收款类型不正确',
			'out_trade_no' => '商户订单号不正确',
			'amount' => '金额不正确',
			'notify_url' => '回调连接不正确',
			'timestamp' => '下单时间不正确',
			'sign' => '签名不正确',
		];

		if (input('post.account_type') == 1)
		{
			$rule['bank|银行名称'] = 'require|max:50';
			$rule['branch|银行支行'] = 'require|max:50';
			$rule['account_name|账户名称'] = 'require|max:50';
			$rule['account|银行卡号'] = 'require|max:20';
		}
		elseif (input('post.account_type') == 2)
		{
			$rule['account|钱包地址'] = 'require|max:34';
		}
		elseif (input('post.account_type') == 3)
		{
			$rule['account_name|账户名称'] = 'require|max:50';
			$rule['account|支付宝账号'] = 'require|max:50';
		}
		elseif (input('post.account_type') == 4)
		{
			$rule['account_name|姓名'] = 'require|max:50';
			$rule['account|账号'] = 'require|max:50';
		}

		if (!$this->validate($params, $rule, $message))
		{
			$this->orderError($this->getValidateError());
		}

		if (strpos(input('post.notify_url'), 'http://') !== 0 && strpos(input('post.notify_url'), 'https://') !== 0)
		{
			$this->orderError('回调连接不正确2');
		}

		$this->params = $params;
	}

	/**
	 * 用户检测，获取$this->business
	 */
	private function checkBusiness()
	{
		$business = Business::where(['id' => $this->params['mchid']])->find();

		if (!$business)
		{
			$this->orderError('商户编号不正确！');
		}

		if (trim($business->api_ip))
		{
			$ip = $_SERVER['REMOTE_ADDR']; // 默认使用 REMOTE_ADDR (可能是 8.219.0.224)

			// 优先级高的可信头（针对阿里云 CDN）
			$trustedHeaders = [
				'HTTP_ALI_CDN_REAL_IP',     // 阿里云 CDN 真实 IP
				'HTTP_CF_CONNECTING_IP',    // Cloudflare（备用）
				'HTTP_X_FORWARDED_FOR',     // 通用代理头，取第一个 IP
			];

			foreach ($trustedHeaders as $key)
			{
				if (!empty($_SERVER[$key]))
				{
					if ($key === 'HTTP_X_FORWARDED_FOR')
					{
						$proxyIps = explode(',', $_SERVER[$key]);
						$ip = trim($proxyIps[0]); // 取第一个 IP 作为客户端 IP
					}
					else
					{
						$ip = trim($_SERVER[$key]);
					}
					break;
				}
			}

			$api_ip = explode("\n", $business->api_ip);

			if (count($api_ip) > 0 && !in_array($ip, $api_ip))
			{
				return $this->orderError("ip不在白名单: {$ip}");
			}
		}

		//类型：1代理 2工作室 3商户
		if (!in_array($business->type, [3]))
		{
			$this->orderError('mchid不是商户id');
		}

		if ($business->status != 1)
		{
			$this->orderError('商户账号已禁用');
		}

		if ($business->verify_status != 1)
		{
			$this->orderError('商户账号未通过认证');
		}

		if ($business->allow_withdraw <= 0)
		{
			$this->orderError('商户余额不足');
		}

		if ($business->parent->status != 1)
		{
			$this->orderError('商户账号已禁用2');
		}

		if ($business->parent->verify_status != 1)
		{
			$this->orderError('商户账号未通过认证2');
		}

		// 代理余额低于额度不能下单
		if ($this->setting->cannot_order_less_than < 0 && $business->parent->money <= $this->setting->cannot_order_less_than)
		{
			$this->orderError('商户余额不足');
		}

		if (isset($this->params['amount']))
		{
			if ($this->params['amount'] < $business->min_amount || $this->params['amount'] > $business->max_amount)
			{
				$this->orderError("订单金额只能在{$business->min_amount} ~ {$business->max_amount}区间");
			}
		}

		$this->business = $business;
		$this->secret_key = $business->secret_key;
	}


	/**
	 * 生成其他参数
	 */
	private function setData()
	{

		// 获取USDT汇率
		$this->usdt_rate = $this->getUsdtRate();

		// 系统费用费率
		$this->system_rate = $this->business->parent->order_rate;
		$this->commission = $this->business->parent->commission;
		$this->sub_business_id = $this->business->id;
		$this->sub_business_rate = $this->business->order_rate;
		$this->card_business_ids = Business::where('order_status', 1)->where('status', 1)->where('id', 'in', $this->business->card_business_ids)->column('id');
		sort($this->card_business_ids);
	}

	/**
	 * 获取USDT汇率
	 */
	private function getUsdtRate()
	{
		// USDT汇率类型 1自动 2手动
		if ($this->business->usdt_rate_type == 1)
		{
			// 系统实时汇率
			return $this->setting->usdt_rate;
		}
		else
		{
			// 商户手动汇率
			return $this->business->usdt_rate;
		}
	}

	/**
	 * 获取工作室账号（人工转账）
	 */
	private function getCardBusiness()
	{
		$card_business_ids = $this->card_business_ids;
		$parent_id = $this->business->parent_id;

		// \think\facade\Db::startTrans();
		try
		{

			$where = [];
			$where[] = ['id', 'in', $card_business_ids];
			$where[] = ['parent_id', '=', $parent_id];
			$where[] = ['type', '=', 2]; //类型：1代理 2工作室 3商户
			$where[] = ['status', '=', 1];

			// is_use是否已下单 1是 0否（用于轮询下单）
			$card_business = Business::where($where)->where('is_use', 0)->find();

			if (!$card_business) //如果未找到匹配，复原下单状态
			{
				Business::where($where)->save(['is_use' => 0]);

				// 再次获取商户所有匹配金额的工作室，未下单状态
				$card_business = Business::where($where)->where('is_use', 0)->find();
			}

			if (!$card_business)
			{
				throw new \Exception('无可用工作室');
			}

			$card_business->is_use = 1;
			if (!$card_business->save())
			{
				throw new \Exception('无可用工作室2');
			}

			// \think\facade\Db::commit();

			return $card_business;

		}
		catch (\Exception $e)
		{

			// \think\facade\Db::rollback();

			$this->orderError($e->getMessage());

		}
	}

	/**
	 * 获取三方转账长啊后（三方转账）
	 */
	private function getChannelAccount()
	{
		$card_business_ids = $this->business->card_business_ids;
		$business_id = $this->business->parent_id;

		// \think\facade\Db::startTrans();
		try
		{

			$where = [];
			$where[] = ['business_id', '=', $business_id];
			$where[] = ['card_business_id', 'in', $card_business_ids];
			$where[] = ['status', '=', 1];

			// is_use是否已下单 1是 0否（用于轮询下单）
			$account = ChannelAccount::where($where)->where('is_use', 0)->find();

			if (!$account) //如果未找到匹配，复原下单状态
			{
				ChannelAccount::where($where)->save(['is_use' => 0]);

				// 再次获取商户所有匹配金额的工作室，未下单状态
				$account = ChannelAccount::where($where)->where('is_use', 0)->find();
			}

			if (!$account)
			{
				throw new \Exception('无可用工作室');
			}

			$account->is_use = 1;
			if (!$account->save())
			{
				throw new \Exception('无可用工作室2');
			}

			// \think\facade\Db::commit();

			return $account;

		}
		catch (\Exception $e)
		{

			// \think\facade\Db::rollback();

			$this->orderError($e->getMessage());

		}
	}


	/**
	 * 查询订单
	 */
	public function query()
	{
		$this->writeLog(); //写入操作日志

		$params = input('post.');

		$rule = [
			'mchid|商户编号' => 'require|integer|>:0',
			'out_trade_no|商户订单号' => 'require|alphaNum|max:30',
			'timestamp|查询时间' => 'require|integer|>:0',
			'sign|签名' => 'require',
		];

		$message = [
			'mchid' => '商户编号不正确',
			'out_trade_no' => '商户订单号不正确',
			'timestamp' => '查询时间不正确',
			'sign' => '签名不正确',
		];

		if (!$this->validate($params, $rule, $message))
		{
			$this->orderError($this->getValidateError());
		}

		$this->params = $params;

		$this->checkBusiness(); //用户检测，获取$this->business

		$this->checkSign($params, $this->secret_key); //验证签名

		$where = [];
		$where[] = ['out_trade_no', '=', $params['out_trade_no']];
		//类型：1代理 2工作室 3商户
		$where[] = ['sub_business_id', '=', $this->params['mchid']];
		$order = Order::where($where)->find();
		if (!$order)
		{
			$this->orderError('不存在订单号');
		}

		$data = [];
		$data['order_no'] = $order->order_no;
		$data['out_trade_no'] = $order->out_trade_no;
		$data['amount'] = $order->amount;
		if ($order->account_type == 2) //USDT收款
		{
			$data['usdt_amount'] = $order->usdt_amount;
			$data['usdt_rate'] = $order->usdt_rate;
		}
		$data['attach'] = $order->attach;
		$data['remark'] = $order->remark;
		$data['pay_remark'] = $order->pay_remark;
		$data['image_url'] = $order->image_url ?? '';
		$data['success_time'] = strtotime($order->success_time);
		$data['notify_time'] = strtotime($order->last_notify_time);
		$data['is_notify'] = 0;

		// 状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败
		if ($order->status == -1)
		{
			$data['status'] = 'NOTPAY';
			$data['status_msg'] = '未支付';
		}
		elseif ($order->status == 1)
		{
			$data['status'] = 'SUCCESS';
			$data['status_msg'] = '成功，未回调';
		}
		elseif ($order->status == 2)
		{
			$data['status'] = 'SUCCESS';
			$data['is_notify'] = 1;
			$data['status_msg'] = '成功，已回调';
		}
		elseif ($order->status == -2)
		{
			$data['status'] = 'FAIL';
			$data['is_notify'] = $data['notify_time'] > 0;
			$data['status_msg'] = '支付失败';
		}
		else
		{
			$data['status'] = 'ERROR';
			$data['status_msg'] = '生成订单失败';
		}

		Common::writeLog([
			'params' => input('post.'),
			'return_data' => $data,
		], 'order_query');

		return $this->returnData($data, '成功');
	}

	/**
	 * 查询账号信息
	 */
	public function account_info()
	{
		$this->writeLog(); //写入操作日志

		$params = input('post.');

		$rule = [
			'mchid|商户编号' => 'require|integer|>:0',
			'timestamp|查询时间' => 'require|integer|>:0',
			'sign|签名' => 'require',
		];

		$message = [
			'mchid' => '商户编号不正确',
			'timestamp' => '查询时间不正确',
			'sign' => '签名不正确',
		];

		if (!$this->validate($params, $rule, $message))
		{
			$this->orderError($this->getValidateError());
		}

		$this->params = $params;

		$this->checkBusiness(); //用户检测，获取$this->business

		$this->checkSign($params, $this->secret_key); //验证签名

		$data = [];
		$data['mchid'] = $this->business->id;
		$data['balance'] = $this->business->allow_withdraw;

		return $this->returnData($data, '成功');
	}
}
