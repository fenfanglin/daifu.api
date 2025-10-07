<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\model\Order;
use app\model\ChannelAccount;
use app\model\BusinessRecharge;

class RechargeUsdtController extends AuthController
{
	private $controller_name = 'Usdt';
	
	private $recharge_type = 1; //充值方式：-1后台充值 1Usdt
	private $channel_id = 102; //通道id，用于检查订单是否有重复金额
	private $timeout = 10; //10分钟过期
	
	/**
	 * 充值报错
	 */
	protected function rechargeError($msg)
	{
		Common::writeLog(['params' => input('post.'), 'msg' => $msg], 'recharge_usdt_error');
		
		Common::error($msg);
	}
	
	/**
	 * 提交充值
	 */
	public function pay()
	{
		$rule = [
			'amount|金额'	=> 'require|float|>:0',
			'remark|备注'	=> 'max:255',
		];
		if (!$this->validate(input('post.'), $rule))
		{
			$this->rechargeError($this->getValidateError());
		}
		
		$amount = input('post.amount');
		$remark = input('post.remark');
		
		$usdt_rate = $this->setting->usdt_rate;
		$recharge_account_usdt = $this->setting->recharge_account_usdt; //Usdt充值地址
		
		// 兑换出usdt金额
		$usdt_amount = $amount / $usdt_rate;
		$usdt_amount = number_format($usdt_amount, 2, '.', '');
		
		// 获取可下单金额
		$usdt_amount = $this->getAmount($usdt_amount);
		// 兑换出人民币金额
		$pay_amount = $usdt_amount * $usdt_rate;
		$pay_amount = number_format($pay_amount, 2, '.', '');
		
		// 过期时间
		$expire_time = date('Y-m-d H:i:s', time() + $this->timeout * 60);
		
		$model = new BusinessRecharge();
		$model->business_id			= $this->user->id;
		$model->recharge_type		= $this->recharge_type;
		$model->account_name		= 'Usdt充值账号';
		$model->account				= $recharge_account_usdt;
		$model->account_sub			= substr($model->account, -4);
		$model->post_amount			= $amount;
		$model->pay_amount			= $pay_amount;
		$model->usdt_rate			= $usdt_rate;
		$model->usdt_amount			= $usdt_amount;
		$model->ip					= Common::getClientIp();
		$model->remark				= $remark;
		$model->expire_time			= $expire_time;
		
		$model->status				= -1;
		if (!$model->save())
		{
			$this->rechargeError('生成订单失败');
		}
		
		$this->writeLog("提交充值：{$amount}元，{$usdt_amount}U");
		
		
		$data = [];
		$data['no'] = $model->no;
		$data['pay_url'] = config('app.order_url') . 'recharge/index?id=' . $model->order_no;
		
		return $this->returnData($data);
	}
	
	/**
	* 获取可下单金额
	*/
	private function getAmount($post_amount)
	{
		// 检查金额逻辑
		// 如果没开启随机金额：只检查此金额的充值记录是否存在
		// 如果开始随机金额：
		// 	- 按加随机或者减随机，获取出充值记录未支付未过期，金额在随机金额范围内
		// 	- 按加随机或者减随机，生成随机金额数组[0.01, 0.02, ... , 0.98, 0.99]
		// 	- 找出未存在充值记录数组，返回金额
		
		$recharge_account_usdt = $this->setting->recharge_account_usdt; //Usdt充值地址
		// $account_name = substr($recharge_account_usdt, -4);
		$random_amount = $this->setting->random_amount; //随机金额 -1关闭 1加随机金额 2减随机金额
		// 随机金额 -1关闭 1加随机金额 2减随机金额
		
		if ($random_amount == -1) //随机金额关闭
		{
			// ------------------------------------------------------------------------------------------
			// 检查充值金额重复
			$where = [];
			$where[] = ['recharge_type', '=', $this->recharge_type]; //充值方式：-1后台充值 1Usdt
			$where[] = ['account', '=', $recharge_account_usdt]; //收款账号
			$where[] = ['usdt_amount', '=', $post_amount];
			$where[] = ['expire_time', '>=', date('Y-m-d H:i:s')];
			$where[] = ['status', '=', -1]; //状态：-1未支付 1成功 -2生成订单失败
			
			$check = BusinessRecharge::where($where)->count('id');
			if ($check)
			{
				Common::error('金额被占用，请输入其他金额或可稍等几分钟');
			}
			
			return $post_amount;
		}
		else
		{
			if ($random_amount == 1) //1加随机金额
			{
				$rand_min = min($this->setting->random_amount_min, $this->setting->random_amount_max);
				$rand_max = max($this->setting->random_amount_min, $this->setting->random_amount_max);
			}
			elseif ($random_amount == 2) //2减随机金额
			{
				$rand_min = min(-$this->setting->random_amount_min, -$this->setting->random_amount_max);
				$rand_max = max(-$this->setting->random_amount_min, -$this->setting->random_amount_max);
			}
			
			// ------------------------------------------------------------------------------------------
			// 检查充值金额重复
			// 获取充值未支付未过期，金额在随机金额范围内 => 被占用金额
			$where = [];
			$where[] = ['recharge_type', '=', $this->recharge_type]; //充值方式：-1后台充值 1Usdt
			$where[] = ['account', '=', $recharge_account_usdt]; //收款账号
			$where[] = ['usdt_amount', '>=', $post_amount + $rand_min];
			$where[] = ['usdt_amount', '<=', $post_amount + $rand_max];
			$where[] = ['expire_time', '>=', date('Y-m-d H:i:s')];
			$where[] = ['status', '=', -1]; //状态：-1未支付 1成功 -2生成订单失败
			
			$list = BusinessRecharge::field('usdt_amount')->where($where)->select()->toArray();
			
			
			// 被占用金额
			// $arr_order_amount = array_column($list, 'pay_amount');
			$arr_order_amount = array_column($list, 'usdt_amount');
			
			// 去掉重复
			$arr_order_amount = array_unique($arr_order_amount);
			
			// ------------------------------------------------------------------------------------------
			// 生成随机金额数组
			$arr_random_amount = range($rand_min * 100, $rand_max * 100);
			
			// 随机顺序排列
			// shuffle($arr_random_amount);
			
			// 如果是减随机金额，把随机顺序翻转过来，目的是获取出金额最靠近提交金额
			if ($random_amount == 2) //2减随机金额
			{
				$arr_random_amount = array_reverse($arr_random_amount);
			}
			
			// ------------------------------------------------------------------------------------------
			// 找出可用的金额
			$pay_amount = 0;
			foreach ($arr_random_amount as $random_amount)
			{
				$tmp_amount = $post_amount + ($random_amount / 100);
				$tmp_amount = number_format($tmp_amount, 2, '.', '');
				
				if (!in_array($tmp_amount, $arr_order_amount))
				{
					$pay_amount = $tmp_amount;
					break;
				}
			}
			
			if (!$pay_amount)
			{
				Common::error('金额被占用，请输入其他金额或可稍等1分钟');
			}
			
			return $pay_amount;
		}
	}
}