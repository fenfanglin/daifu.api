<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\extend\auth\CrossDomainAuth;
use app\model\Business;
use app\model\BusinessMoneyLog;
use app\model\BusinessChannel;
use app\model\Channel;
use app\model\ChannelAccount;

class BusinessController extends AuthController
{
	private $controller_name = '商户';

	/**
	 * 列表
	 */
	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'verify_status|认证' => 'integer',
			'status|状态' => 'integer',
		];

		if (!$this->validate($params, $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$user = $this->getUser();

		$page = intval($params['page'] ?? 1);
		$limit = intval($params['limit'] ?? 10);
		$id = intval($params['id'] ?? NULL);
		$keyword = $params['keyword'] ?? NULL;
		$verify_status = intval($params['verify_status'] ?? NULL);
		$status = intval($params['status'] ?? NULL);
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = Business::field('*');

		$query->where('parent_id', $this->user->id);
		$query->where('type', 3); //类型：1代理 2工作室 3商户

		if ($user->center_id > 0)
		{
			$query->where('center_id', $user->center_id);
		}

		if (!empty($keyword))
		{
			$query->where('username|realname', 'like', '%' . $keyword . '%');
		}
		if (!empty($id))
		{
			$query->where('id', $id);
		}
		if (!empty($status))
		{
			$query->where('status', $status);
		}
		if (!empty($verify_status))
		{
			$query->where('verify_status', $verify_status);
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

		$data = [];
		foreach ($list as $model)
		{
			$tmp = [];
			$tmp['id'] = $model->id;
			$tmp['no'] = $model->no;
			$tmp['username'] = $model->username;
			$tmp['realname'] = $model->realname;
			$tmp['secret_key'] = $model->secret_key;
			// $tmp['phone'] = $model->phone;
			$tmp['allow_withdraw'] = $model->allow_withdraw;
			$tmp['last_login_time'] = $model->last_login_time;
			$tmp['login_count'] = $model->login_count;
			// $tmp['create_time'] = $model->create_time;
			$tmp['update_time'] = $model->update_time;
			$tmp['order_rate'] = (string) $model->order_rate;
			$tmp['commission'] = (string) $model->commission;

			$tmp['status_str'] = isset(Business::STATUS[$model->status]) ? Business::STATUS[$model->status] : '';
			$tmp['card_type_str'] = $model->card_type == 1 ? '人工支付' : '三方转账';
			$tmp['amount_range'] = $model->min_amount . '-' . $model->max_amount;
			$tmp['status_class'] = isset(Business::STATUS_CLASS[$model->status]) ? Business::STATUS_CLASS[$model->status] : '';
			$tmp['status'] = (string) $model->status;
			$tmp['card_type'] = (string) $model->card_type;

			$tmp['verify_status_str'] = isset(Business::VERIFY_STATUS[$model->verify_status]) ? Business::VERIFY_STATUS[$model->verify_status] : '';
			$tmp['verify_status_class'] = isset(Business::VERIFY_STATUS_CLASS[$model->verify_status]) ? Business::VERIFY_STATUS_CLASS[$model->verify_status] : '';
			$tmp['verify_status'] = (string) $model->verify_status;

			$tmp['role_id'] = $model->role_id;
			if ($model->role)
			{
				$tmp['role_name'] = $model->role->name;
			}
			elseif ($model->role_id == -1)
			{
				$tmp['role_name'] = $model->center_id ? '代理所有权限' : '系统所有权限';
			}
			else
			{
				$tmp['role_name'] = '';
			}

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

	/**
	 * 导出
	 */
	public function export()
	{
		$this->writeLog($this->controller_name . '导出');

		$params = input('post.');
		$params['is_export'] = 1;

		$data = $this->_search($params, $is_export = 1);

		$export_value = [
			'id' => '卡商ID',
			'username' => '卡商账号',
			'realname' => '卡商名称',
			'money' => '余额',
			'verify_status_str' => '认证',
			'status_str' => '状态',
			'create_time' => '注册时间',

		];

		Common::exportExcel($data['list'], $export_value);
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
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['username'] = $model->username;
		$data['realname'] = $model->realname;
		$data['phone'] = $model->phone;
		$data['money'] = $model->money;
		$data['google_secret_key'] = $model->google_secret_key;
		$data['login_ip'] = $model->login_ip;
		// $data['last_login_time'] = $model->last_login_time;
		// $data['login_count'] = $model->login_count;
		// $data['create_time'] = $model->create_time;
		// $data['update_time'] = $model->update_time;
		$data['remark'] = $model->remark;
		$data['status'] = $model->status;
		$data['min_amount'] = $model->min_amount;
		$data['max_amount'] = $model->max_amount;
		$data['card_type'] = $model->card_type;
		// $data['channel_id'] = $model->channel_id;
		$data['verify_status'] = $model->verify_status;
		$data['allow_withdraw'] = $model->allow_withdraw;
		$data['order_rate'] = $model->order_rate;
		$data['commission'] = $model->commission;

		// $data['card_business_ids'] = explode(',', $model->card_business_ids);
		if (intval($model->card_type) == 2)
		{
			// $model->channel_id = intval(input('post.channel_id'));
			$data['card_business_ids'] = $model->card_business_ids;
		}
		else
		{
			$data['card_business_ids'] = array_map('intval', explode(',', $model->card_business_ids));
			$data['card_business_ids'] = array_filter($data['card_business_ids']);
		}


		$data['role_id'] = $model->role_id;
		if ($model->role)
		{
			$data['role_id'] = $model->role_id;
		}
		elseif ($model->role_id == -1)
		{
			$data['role_id'] = -1; //所有权限
		}
		else
		{
			$data['role_id'] = '';
		}

		return $this->returnData($data);
	}

	/**
	 * 新增/修改
	 * 后台管理员编辑卡商，添加默认是已认证
	 */
	public function save()
	{
		$rule = [
			'realname|商户名称' => 'require|max:50',
			'status|状态' => 'require|integer',
			'order_rate|订单费率' => 'require|float|>=:0',
			'commission|固定费用' => 'require|float|>=:0',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$user = $this->getUser();

		$no = input('post.no');
		if ($no)
		{
			// 检查内部权限
			$this->checkPermission('edit', true);

			$model = Business::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
		}
		else
		{
			$rule = [
				'username|卡商账号' => 'require|alphaNum|max:50',
				'password|密码' => 'require|min:6|max:50',
			];

			if (!$this->validate(input('post.'), $rule))
			{
				return $this->returnError($this->getValidateError());
			}

			// 检查内部权限
			$this->checkPermission('add', true);

			$model = new Business;
			$model->parent_id = $this->user->id;
			$model->type = 3; //类型：1代理 2工作室 3商户
			$model->username = input('post.username');
			$model->verify_status = 1;
		}


		$model->realname = input('post.realname');
		// $model->phone = input('post.phone');
		// $model->role_id = intval(input('post.role_id'));
		$model->google_secret_key = input('post.google_secret_key');
		$model->login_ip = input('post.login_ip');
		$model->status = intval(input('post.status'));
		$model->min_amount = intval(input('post.min_amount'));
		$model->max_amount = intval(input('post.max_amount'));
		$model->card_type = intval(input('post.card_type'));
		$model->commission = input('post.commission');
		$model->order_rate = input('post.order_rate');
		if (intval(input('post.card_type')) == 2)
		{
			// $model->channel_id = intval(input('post.channel_id'));
			$card_business_ids = input('post.card_business_ids');
			$model->card_business_ids = $card_business_ids;
			// return $model->card_business_ids;

		}
		else
		{
			$card_business_ids = implode(',', array_filter(input('post.card_business_ids')));
			$model->card_business_ids = $card_business_ids;
		}
		if ($password = input('post.password'))
		{
			$model->auth_key = Common::randomStr(6);
			$model->password = Common::generatePassword($password, $model->auth_key);
		}

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "保存：商户编号{$model->id}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 修改密码
	 */
	public function change_password()
	{
		$rule = [
			'no' => 'require|max:50',
			'password|密码' => 'require|min:6|max:50',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		if ($password = input('post.password'))
		{
			$model->auth_key = Common::randomStr(6);
			$model->password = Common::generatePassword($password, $model->auth_key);
		}

		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}

		$this->writeLog($this->controller_name . "修改密码：商户编号{$model->id}");

		return $this->returnSuccess('保存成功');
	}

	/**
	 * 修改商户余额
	 */
	public function save_withdraw()
	{
		$rule = [
			'no' => 'require|max:50',
			'withdraw_type|操作' => 'require|in:1,-1',
			'amount|金额' => 'require|float|>:0',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$business = Business::where('no', $no)->find();
		if (!$business)
		{
			return $this->returnError('无法找到信息');
		}

		$user = $this->getUser();

		$amount = input('post.amount');
		if (input('post.withdraw_type') == -1 && $amount > $business->allow_withdraw)
		{
			return $this->returnError('余额不足');
		}

		$remark = "[{$user->id}] 代理操作";
		if (input('post.remark'))
		{
			$remark .= ': ' . input('post.remark');
		}

		if (input('post.withdraw_type') == -1)
		{
			$amount = -$amount;
		}



		// 更新商户余额
		$res = \app\service\BusinessService::changeAllowWithdraw($business->id, $amount, $type = 3, $item_id = 0, $remark);

		if ($res['code'] != 1)
		{
			return $this->returnError($res['msg']);
		}
		else
		{
			$this->writeLog($this->controller_name . "余额：商户编号{$business->id}，{$amount}元");

			return $this->returnSuccess('保存成功');
		}
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
				$business = Business::where('no', $no)->find();
				if (!$business)
				{
					throw new \Exception('无法找到信息');
				}

				$business->delete();
			}

			\think\facade\Db::commit();

			$this->writeLog($this->controller_name . "删除：{$business->id}");

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
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$model->status = 1;

		if (!$model->save())
		{
			return $this->returnError('失败');
		}

		$this->writeLog($this->controller_name . "启用：商户编号{$model->id}");

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
		$model = Business::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$model->status = -1;

		if (!$model->save())
		{
			return $this->returnError('失败');
		}

		$this->writeLog($this->controller_name . "禁用：商户编号{$model->id}");

		return $this->returnSuccess('成功');
	}
}
