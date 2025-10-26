<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\model\BusinessChannel;
use app\model\ChannelAccount;
use app\model\Channel;
use app\model\Order;
use app\model\ESOrder;

class ChannelShundatongController extends AuthController
{
	private $controller_name = '瞬达通';

	private $channel_id = 1;

	/**
	 * 初始化
	 */
	public function __construct()
	{
		parent::__construct();

		// 类型：1代理 2工作室 3商户
		if (!in_array($this->user->type, [1, 2]))
		{
			Common::error('此功能不开放给商户');
		}
	}

	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'status|状态' => 'integer',
			'card_business_id|工作室' => 'integer',
		];

		if (!$this->validate($params, $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$keyword = $params['keyword'] ?? NULL;
		$status = intval($params['status'] ?? NULL);
		$create_time = $params['create_time'] ?? NULL;
		$card_business_id = intval($params['card_business_id'] ?? NULL);

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = ChannelAccount::field('*');

		$query->where('channel_id', $this->channel_id);

		// 根据商户类型生成查询条件
		setChannelWhere($this->user, $query);

		if (!empty($keyword))
		{
			$query->where('mchid|appid', 'like', '%' . $keyword . '%');
		}
		if (!empty($status))
		{
			$query->where('status', $status);
		}
		if (!empty($card_business_id))
		{
			$query->where('card_business_id', $card_business_id);
		}
		if (!empty($create_time[0]) && $create_time[0] > 0)
		{
			$_begin_time = date('Y-m-d H:i:s', strtotime($create_time[0]) - 1);
			$query->where('create_time', '>', $_begin_time);
		}
		if (!empty($create_time[1]) && $create_time[1] > 0)
		{
			$_end_time = date('Y-m-d H:i:s', strtotime($create_time[1] . ' +1 second'));
			$query->where('create_time', '<', $_end_time);
		}

		$query->order('id desc');
		// var_dump($query->getLastSql());
		// var_dump($query->buildSql(true));

		$list = $query->paginate([
			'list_rows' => $limit,
			'page' => $page,
		]);

		$channel_account_ids = [];

		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			$tmp['id'] = $model->id;
			$tmp['no'] = $model->no;
			$tmp['mchid'] = $model->mchid;
			$tmp['appid'] = $model->appid;
			$tmp['key_id'] = $model->key_id;
			$tmp['key_secret'] = $model->key_secret;
			$tmp['balance'] = $model->balance;
			$tmp['remark'] = $model->remark;
			$tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;
			$tmp['order_num'] = $model->order_num;


			$tmp['status_str'] = ChannelAccount::STATUS[$model->status] ?? '';
			$tmp['status_class'] = ChannelAccount::STATUS_CLASS[$model->status] ?? '';
			$tmp['status'] = (string) $model->status;

			$tmp['channel_id'] = $model->channel_id;
			$tmp['channel_name'] = $model->channel->name ?? '';

			$tmp['card_business_id'] = $model->card_business_id;
			$tmp['card_business_realname'] = $model->cardBusiness->realname ?? '';

			// if (config('es.is_active') == false)
			// {
			// 	$key = 'business_data_channel_account_' . $model->id;

			// 	if ($_data = $this->global_redis->get($key))
			// 	{
			// 		$tmp['today_success_amount'] = isset($_data['today_success_amount']) ? $_data['today_success_amount'] : 0;
			// 		$tmp['today_success_order'] = isset($_data['today_success_order']) ? $_data['today_success_order'] : 0;
			// 		$tmp['today_total_order'] = isset($_data['today_total_order']) ? $_data['today_total_order'] : 0;
			// 		$tmp['today_success_rate'] = isset($_data['today_success_rate']) ? $_data['today_success_rate'] : 0;
			// 		$tmp['yesterday_success_amount'] = isset($_data['yesterday_success_amount']) ? $_data['yesterday_success_amount'] : 0;
			// 		$tmp['yesterday_success_order'] = isset($_data['yesterday_success_order']) ? $_data['yesterday_success_order'] : 0;
			// 		$tmp['yesterday_total_order'] = isset($_data['yesterday_total_order']) ? $_data['yesterday_total_order'] : 0;
			// 		$tmp['yesterday_success_rate'] = isset($_data['yesterday_success_rate']) ? $_data['yesterday_success_rate'] : 0;
			// 	}
			// 	else
			// 	{
			// 		$_where = [];
			// 		$_where[] = ['channel_account_id', '=', $model->id];
			// 		$_where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];

			// 		$_where_total = [];
			// 		$_where_total[] = ['channel_account_id', '=', $model->id];
			// 		$_where_total[] = ['create_time', '>', date('Y-m-d 23:59:59', strtotime('-1 day'))];

			// 		// 交易总额
			// 		$tmp['today_success_amount'] = DB2Order::where($_where)->where('status', '>', 0)->sum('pay_amount');

			// 		// 交易笔数
			// 		$tmp['today_success_order'] = DB2Order::where($_where)->where('status', '>', 0)->count('id');

			// 		// 交易笔数
			// 		$tmp['today_total_order'] = DB2Order::where($_where_total)->count('id');

			// 		// 成功率
			// 		if (!$tmp['today_total_order'])
			// 		{
			// 			$tmp['today_success_rate'] = 0;
			// 		}
			// 		else
			// 		{
			// 			$tmp['today_success_rate'] = round($tmp['today_success_order'] / ($tmp['today_total_order']) * 100, 2);
			// 		}

			// 		$key2 = 'business_data_channel_account_' . $model->id . '_' . date('Ymd', strtotime('-1 day'));
			// 		$_data2 = $this->global_redis->get($key2);
			// 		if ($_data2 && date('H') > 1)
			// 		{
			// 			$tmp['yesterday_success_amount'] = isset($_data2['yesterday_success_amount']) ? $_data2['yesterday_success_amount'] : 0;
			// 			$tmp['yesterday_success_order'] = isset($_data2['yesterday_success_order']) ? $_data2['yesterday_success_order'] : 0;
			// 			$tmp['yesterday_total_order'] = isset($_data2['yesterday_total_order']) ? $_data2['yesterday_total_order'] : 0;
			// 			$tmp['yesterday_success_rate'] = isset($_data2['yesterday_success_rate']) ? $_data2['yesterday_success_rate'] : 0;
			// 		}
			// 		else
			// 		{
			// 			$_where = [];
			// 			$_where[] = ['channel_account_id', '=', $model->id];
			// 			$_where[] = ['success_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
			// 			$_where[] = ['success_time', '<', date('Y-m-d')];

			// 			$_where_total = [];
			// 			$_where_total[] = ['channel_account_id', '=', $model->id];
			// 			$_where_total[] = ['create_time', '>', date('Y-m-d 23:59:59', strtotime('-2 day'))];
			// 			$_where_total[] = ['create_time', '<', date('Y-m-d')];

			// 			// 交易总额
			// 			$tmp['yesterday_success_amount'] = DB2Order::where($_where)->where('status', '>', 0)->sum('pay_amount');

			// 			// 交易笔数
			// 			$tmp['yesterday_success_order'] = DB2Order::where($_where)->where('status', '>', 0)->count('id');

			// 			// 交易笔数
			// 			$tmp['yesterday_total_order'] = DB2Order::where($_where_total)->count('id');

			// 			// 成功率
			// 			if (!$tmp['yesterday_total_order'])
			// 			{
			// 				$tmp['yesterday_success_rate'] = 0;
			// 			}
			// 			else
			// 			{
			// 				$tmp['yesterday_success_rate'] = round($tmp['yesterday_success_order'] / ($tmp['yesterday_total_order']) * 100, 2);
			// 			}

			// 			$_data2 = [];
			// 			$_data2['yesterday_success_amount'] = $tmp['yesterday_success_amount'] ?? 0;
			// 			$_data2['yesterday_success_order'] = $tmp['yesterday_success_order'] ?? 0;
			// 			$_data2['yesterday_total_order'] = $tmp['yesterday_total_order'] ?? 0;
			// 			$_data2['yesterday_success_rate'] = $tmp['yesterday_success_rate'] ?? 0;

			// 			$this->global_redis->set($key2, $_data2, 86400);
			// 		}

			// 		$_data['today_success_amount'] = $tmp['today_success_amount'];
			// 		$_data['today_success_order'] = $tmp['today_success_order'];
			// 		$_data['today_total_order'] = $tmp['today_total_order'];
			// 		$_data['today_success_rate'] = $tmp['today_success_rate'];
			// 		$_data['yesterday_success_amount'] = $tmp['yesterday_success_amount'];
			// 		$_data['yesterday_success_order'] = $tmp['yesterday_success_order'];
			// 		$_data['yesterday_total_order'] = $tmp['yesterday_total_order'];
			// 		$_data['yesterday_success_rate'] = $tmp['yesterday_success_rate'];

			// 		$this->global_redis->set($key, $_data, getDataCacheTimeMin());
			// 	}
			// }

			$data['list'][] = $tmp;

			$channel_account_ids[] = $model->id;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}
		// 待处理订单数
		$where = [];
		$where[] = ['business_id', '=', $this->user->id];
		$data['info']['order_wating'] = Order::where($where)->where('api_status', '=', -1)->count('id');

		// $data['info']['sql'] = var_dump(Order::getLastSql());
		//  正在处理订单数
		$where[] = ['api_status', '=', 1];
		$where[] = ['status', '=', -1];
		$data['info']['order_doing'] = Order::where($where)->count('id');
		// if (config('es.is_active') == true)
		// {
		// 	$sign = md5(json_encode($channel_account_ids));
		// 	$cache_key = "business_data_channel_account_{$sign}";

		// 	if ($_data = $this->global_redis->get($cache_key))
		// 	{
		// 		foreach ($data['list'] as $key => $value)
		// 		{
		// 			$tmp = $_data[$value['id']] ?? [];

		// 			$value['today_success_amount'] = $tmp['today_success_amount'] ?? 0;
		// 			$value['today_success_order'] = $tmp['today_success_order'] ?? 0;
		// 			$value['today_total_order'] = $tmp['today_total_order'] ?? 0;
		// 			$value['today_success_rate'] = $tmp['today_success_rate'] ?? 0;
		// 			$value['yesterday_success_amount'] = $tmp['yesterday_success_amount'] ?? 0;
		// 			$value['yesterday_success_order'] = $tmp['yesterday_success_order'] ?? 0;
		// 			$value['yesterday_total_order'] = $tmp['yesterday_total_order'] ?? 0;
		// 			$value['yesterday_success_rate'] = $tmp['yesterday_success_rate'] ?? 0;

		// 			$data['list'][$key] = $value;
		// 		}
		// 	}
		// 	else
		// 	{
		// 		$es = new ESOrder;
		// 		$res = $es->account_stats($channel_account_ids);

		// 		foreach ($data['list'] as $key => $value)
		// 		{
		// 			$tmp = $res[$value['id']] ?? [];

		// 			$value['today_success_amount'] = $tmp['today_success_amount'] ?? 0;
		// 			$value['today_success_order'] = $tmp['today_success_order'] ?? 0;
		// 			$value['today_total_order'] = $tmp['today_total_order'] ?? 0;
		// 			$value['today_success_rate'] = $tmp['today_success_rate'] ?? 0;
		// 			$value['yesterday_success_amount'] = $tmp['yesterday_success_amount'] ?? 0;
		// 			$value['yesterday_success_order'] = $tmp['yesterday_success_order'] ?? 0;
		// 			$value['yesterday_total_order'] = $tmp['yesterday_total_order'] ?? 0;
		// 			$value['yesterday_success_rate'] = $tmp['yesterday_success_rate'] ?? 0;

		// 			$data['list'][$key] = $value;
		// 		}

		// 		$this->global_redis->set($cache_key, $res, getDataCacheTimeMin());
		// 	}
		// }

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
		$model = ChannelAccount::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		// 检查信息是否属于商户
		$res = checkAccountBelongBusiness($this->user, $model);
		if (!isset($res['status']) || $res['status'] != true)
		{
			return $this->returnError($res['msg'] ?? '信息不属于商户0');
		}

		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['mchid'] = $model->mchid;
		$data['appid'] = $model->appid;
		$data['key_id'] = $model->key_id;
		$data['key_secret'] = $model->key_secret;
		$data['balance'] = $model->balance;
		$data['remark'] = $model->remark;
		$data['status'] = $model->status;

		$data['channel_id'] = $model->channel_id;
		$data['card_business_id'] = $model->card_business_id;

		$data['is_auth_when_edit_account'] = $this->user->is_auth_when_edit_account;

		return $this->returnData($data);
	}

	/**
	 * 新增/修改
	 */
	public function save()
	{
		$rule = [
			'mchid|商户ID' => 'require|alphaNum|max:50',
			'appid|APPID' => 'require|alphaNum|max:50',
			// 'key_id|密钥ID' => 'require|alphaNum|max:50',
			'key_secret|密钥' => 'require|max:255',
			'remark|备注' => 'max:50',
			'status|状态' => 'require|integer',
		];

		// 类型：1代理 2工作室 3商户
		if (in_array($this->user->type, [1, 2]))
		{
			$rule['card_business_id|工作室'] = 'require|integer|>:0';
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

			$model = ChannelAccount::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}

			// 检查信息是否属于商户
			$res = checkAccountBelongBusiness($this->user, $model);
			if (!isset($res['status']) || $res['status'] != true)
			{
				return $this->returnError($res['msg'] ?? '信息不属于商户0');
			}
		}
		else
		{
			// 检查内部权限
			$this->checkPermission('add', true);

			$model = new ChannelAccount;
			$model->channel_id = $this->channel_id;

			// 收款账号设置商户信息
			setAccountBusinessInfo($this->user, $model);
		}

		$model->mchid = input('post.mchid');
		$model->appid = input('post.appid');
		$model->key_id = input('post.key_id');
		$model->key_secret = input('post.key_secret');
		$model->remark = input('post.remark');
		$model->status = intval(input('post.status'));
		$model->card_business_id = intval(input('post.card_business_id'));


		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "保存：{$model->name}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 删除
	 */
	public function delete()
	{
		$rule = [
			'ids' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$ids = input('post.ids');
		if (!is_array($ids))
		{
			$ids = [$ids];
		}

		$user = $this->getUser();

		\think\facade\Db::startTrans();
		try
		{

			foreach ($ids as $no)
			{
				$model = ChannelAccount::where('no', $no)->find();
				if (!$model)
				{
					throw new \Exception('无法找到信息');
				}

				if ($user->center_id > 0 && $model->center_id != $user->center_id)
				{
					throw new \Exception('安全拦截');
				}

				$model->delete();
			}

			\think\facade\Db::commit();

			$this->writeLog($this->controller_name . "删除：{$model->name}");

			return $this->returnSuccess('ok');

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			return $this->returnError($e->getMessage());

		}
	}

	/**
	 * 启用
	 */
	public function enable()
	{
		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = ChannelAccount::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$model->status = 1;

		if (!$model->save())
		{
			return $this->returnError('失败');
		}

		$this->writeLog($this->controller_name . "启用：{$model->name}");

		return $this->returnSuccess('成功');
	}

	/**
	 * 禁用
	 */
	public function disable()
	{
		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = ChannelAccount::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$model->status = -1;

		if (!$model->save())
		{
			return $this->returnError('失败');
		}

		$this->writeLog($this->controller_name . "禁用：{$model->name}");

		return $this->returnSuccess('成功');
	}
}