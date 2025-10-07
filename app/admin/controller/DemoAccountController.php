<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\model\DemoAccount;
use app\model\DemoParam;
use app\model\Channel;
use app\model\SystemBank;

class DemoAccountController extends AuthController
{
	private $controller_name = '演示账号';
	
	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'status|状态' => 'integer',
			'out_trade_no|商户订单号' => 'alphaNum',
			'card_business_id|卡商' => 'integer',
			'channel_id|通道' => 'integer',
			// 'success_time|成功时间' => 'dateFormat:Y-m-d H:i:s',
			// 'create_time|下单时间' => 'dateFormat:Y-m-d H:i:s',
		];
		
		if (!$this->validate($params, $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$user = $this->user;
		
		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$status = intval($params['status'] ?? NULL);
		$business_id = $params['business_id'] ?? NULL;
		$channel_id = intval($params['channel_id'] ?? NULL);
		$success_time = $params['success_time'] ?? NULL;
		
		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}
		
		$query = DemoAccount::field('*');
		
		if (!empty($business_id))
		{
			$query->where('business_id', $business_id);
		}
		if (!empty($channel_id))
		{
			$query->where('channel_id', $channel_id);
		}
		if (!empty($status))
		{
			$query->where('status', $status);
		}
		
		$query->order('id desc');
		// var_dump($query->getLastSql());
		// var_dump($query->buildSql(true));
		
		$list = $query->paginate([
					'list_rows' => $limit,
					'page' => $page,
				]);
		
		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			// $tmp['id'] = $model->id;
			$tmp['no'] = $model->no;
			$tmp['account_title'] = $model->account_title;
			$tmp['account_name'] = $model->account_name;
			$tmp['account'] = $model->account;
			
			$tmp['status_str'] = isset(DemoAccount::STATUS[$model->status]) ? DemoAccount::STATUS[$model->status] : '';
			$tmp['status_class'] = isset(DemoAccount::STATUS_CLASS[$model->status]) ? DemoAccount::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string)$model->status;
			
			$tmp['channel_id'] = $model->channel_id;
			$tmp['channel_name'] = $model->channel ? $model->channel->name : '';
			
			$tmp['business_id'] = $model->business_id;
			
			$tmp['system_bank_id'] = $model->system_bank_id;
			$tmp['system_bank_name'] = $model->systemBank ? $model->systemBank->name : '';
			
			$data['list'][] = $tmp;
		}
		
		if (empty($data))
		{
			$data['list'] = [];
		}
		
		$data['total'] = $query->count();
		
		return $data;
	}
	
	/**
	 * 列表
	 */
	public function list()
	{
		$params = input('post.');
		
		$data = $this->_search($params);
		
		return $this->returnData($data);
	}
	
	// /**
	//  * 导出
	//  */
	// public function export()
	// {
	// 	$params = input('post.');
	// 	$params['is_export'] = 1;
		
	// 	$data = $this->_search($params, $is_export = 1);
		
	// 	$export_value = [
	// 		'out_trade_no' => '商户单号',
	// 		'post_amount' => '发起金额',
	// 		'pay_amount' => '交易金额',
	// 		'usdt_amount' => 'Usdt金额',
	// 		'channel_name' => '支付通道',
	// 		'account_name' => '账号名称',
	// 		'account' => '账号',
	// 		'fee' => '费用',
	// 		'create_time' => '下单时间',
	// 		'success_time' => '成功时间',
	// 		'status_str' => '状态',
	// 	];
		
	// 	Common::exportExcel($data['list'], $export_value);
	// }
	
	/**
	* 查看
	*/
	public function view()
	{
		$rule = [
			'no' => 'require',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$no = input('post.no');
		$model = DemoAccount::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}
		
		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['account_name'] = $model->name;
		$data['account'] = $model->account;
		$data['status'] = $model->status;
		
		$data['channel_id'] = $model->channel_id;
		$data['business_id'] = $model->business_id;
		$data['system_bank_id'] = $model->system_bank_id;
		
		
		return $this->returnData($data);
	}
	
	/**
	 * 生成测试订单
	 */
	public function save()
	{
		$this->writeLog('保存' . $this->controller_name);
		
		$rule = [
			'business_id|商户' => 'require|integer|>:0',
			'channel_id|通道' => 'require|integer|>:0',
			'account_name|账号名称' => 'require|max:50',
			'account|收款账号' => 'require|max:50',
			'status|状态' => 'require|in:-1,1',
		];
		
		if (in_array(input('post.channel_id'), [101, 107])) //网银转账，云闪付 => 需要输入银行
		{
			$rule['system_bank_id|银行'] = 'require|integer|>:0';
			
		}
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$no = input('post.no');
		if ($no)
		{
			// 检查内部权限
			$this->checkPermission('edit', true);
			
			$order = DemoAccount::where('no', $no)->find();
			if (!$order)
			{
				return $this->returnError('无法找到信息');
			}
		}
		else
		{
			// 检查内部权限
			$this->checkPermission('add', true);
			
			$order = new DemoAccount;
		}
		
		
		$channel = Channel::where('id', input('post.channel_id'))->find();
		$channel_rate = $channel->rate;
		
		if (in_array(input('post.channel_id'), [101, 107])) //网银转账，云闪付 => 需要输入银行
		{
			$system_bank = SystemBank::where('id', input('post.system_bank_id'))->find();
			$account_title = $system_bank->name;
		}
		else
		{
			$account_title = $channel->title;
		}
		
		$order->business_id = input('post.business_id');
		$order->channel_id = input('post.channel_id');
		$order->system_bank_id = input('post.system_bank_id');
		$order->account_title = $account_title;
		$order->account_name = input('post.account_name');
		$order->account = input('post.account');
		$order->status = input('post.status');
		
		if (!$order->save())
		{
			return $this->returnError('保存失败');
		}
		
		
		// // --------------------------------------------------------------------------------------------------------
		// // 保存测试参数
		
		// $business_id = DemoParam::getInfo('business_id');
		// if (!in_array($order->business_id, $business_id))
		// {
		// 	$business_id[] = $order->business_id;
		// 	DemoParam::saveInfo('business_id', $business_id);
		// }
		
		
		return $this->returnSuccess('保存成功');
	}
	
	/**
	 * 启用
	 */
	public function enable()
	{
		$this->writeLog('启用' . $this->controller_name);
		
		$rule = [
			'no' => 'require',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$no = input('post.no');
		$model = DemoAccount::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}
		
		$model->status = 1;
		
		if (!$model->save())
		{
			return $this->returnError('失败');
		}
		
		return $this->returnSuccess('成功');
	}
	
	/**
	 * 禁用
	 */
	public function disable()
	{
		$this->writeLog('禁用' . $this->controller_name);
		
		$rule = [
			'no' => 'require',
		];
		
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		
		$no = input('post.no');
		$model = DemoAccount::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}
		
		$model->status = -1;
		
		if (!$model->save())
		{
			return $this->returnError('失败');
		}
		
		return $this->returnSuccess('成功');
	}
}