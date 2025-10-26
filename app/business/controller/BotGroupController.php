<?php
namespace app\business\controller;

use app\extend\common\BaseController;
use app\extend\common\Common;
use app\model\Admin;
use app\model\BotGroup;
use app\model\BusinessChannel;
use app\model\ChannelAccount;
use app\model\DB2Order;
use app\model\Business;
use app\model\Channel;
use app\model\Order;
use app\service\BusinessService;

class BotGroupController extends AuthController
{
	/**
	 * 初始化
	 */
	public function __construct()
	{
		parent::__construct();
	}

	protected function _search($params = [], $is_export = 0)
	{
		$rule = [
			'page' => 'integer|min:1',
			'limit' => 'integer',
			'status|状态' => 'integer',
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

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		// 类型：1商户 2卡商 3四方 4四方商户 5子账号 6下级卡商
		$user = $this->user->type == 5 ? $this->user->parent : $this->user;

		$query = BotGroup::field('*');
		if (!empty($keyword))
		{
			$query->where('name|business_id|chat_id', 'like', '%' . $keyword . '%');
		}
		if (!empty($status))
		{
			$query->where('status', $status);
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
		$query->where('parent_id', '=', $user->id);
		$query->where('type', 'in', '2,3');
		$query->order('id desc');

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
			$tmp['token'] = $model->token;
			$tmp['name'] = $model->name;
			$tmp['chat_id'] = $model->chat_id;
			$tmp['type'] = BotGroup::TYPE[$model->type];
			$tmp['parent_id'] = $model->parent_id;
			$tmp['business_id'] = $model->business_id;
			$tmp['quota'] = $model->quota;
			$tmp['quota_status'] = BotGroup::QUOTA_STATUS[$model->quota_status];
			$tmp['usdt'] = $model->usdt;
			$tmp['usdt_gears'] = $model->usdt_gears;
			$tmp['operator_status'] = BotGroup::OPERATOR_STATUS[$model->operator_status];
			$tmp['status'] = (string) $model->status;
			$tmp['create_time'] = $model->create_time;
			$data['list'][] = $tmp;
		}

		if (empty($data))
		{
			$data['list'] = [];
		}

		$data['total'] = $query->count();
		$groupType = [];
		foreach (BotGroup::BUSINESS_TYPE as $key => $value)
		{
			if ($user->type == 1 && $key == 4)
			{
				continue;
			}
			$groupType[$key]['id'] = $key;
			$groupType[$key]['name'] = $value;
		}
		$data['groupType'] = array_values($groupType);
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
		$model = BotGroup::where('no', $no)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$data = [];
		$data['id'] = $model->id;
		$data['no'] = $model->no;
		$data['name'] = $model->name;
		$data['chat_id'] = $model->chat_id;
		$data['type'] = $model->type;
		$data['parent_id'] = $model->parent_id;
		$data['business_id'] = $model->business_id;
		$data['quota'] = $model->quota;
		$data['quota_status'] = $model->quota_status;
		$data['usdt'] = $model->usdt;
		$data['usdt_gears'] = $model->usdt_gears;
		$data['operator_status'] = $model->operator_status;
		$data['status'] = $model->status;
		return $this->returnData($data);
	}

	/**
	 * 新增/修改
	 */
	public function save()
	{
		$rule = [
			'name|群名称' => 'require|max:50',
			'chat_id|TG群聊id' => 'require|max:50',
			'business_id|商户ID' => 'require|integer',
			'status|状态' => 'require|integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		if ($no)
		{
			// 检查内部权限
			$this->checkPermission('edit', true);

			$model = BotGroup::where('no', $no)->find();
			if (!$model)
			{
				return $this->returnError('无法找到信息');
			}
			//TG群聊ID 只允许绑定同一类型的群
			$where = [];
			$where[] = ['no', '<>', $no];
			$where[] = ['chat_id', '=', input('post.chat_id')];
			$true_group = BotGroup::where($where)->find();
			if ($true_group)
			{
				return $this->returnError('TG群聊已绑定其他类型请重新选择');
			}

			$where = [];
			$where[] = ['no', '<>', $no];
			$where[] = ['business_id', '=', input('post.business_id')];
			$true_group = BotGroup::where($where)->find();
			if ($true_group)
			{
				return $this->returnError('商户号已存在请重新填写!！');
			}
		}
		else
		{
			$model = new BotGroup();
			//TG群聊ID 只允许绑定同一类型的群
			$where = [];
			$where[] = ['chat_id', '=', input('post.chat_id')];
			$true_group = BotGroup::where($where)->find();
			if ($true_group)
			{
				return $this->returnError('TG群聊已绑定其他类型请重新选择');
			}

			$where = [];
			$where[] = ['business_id', '=', input('post.business_id')];
			$true_group = BotGroup::where($where)->find();
			if ($true_group)
			{
				return $this->returnError('商户号已存在请重新填写！');
			}

		}

		$business = Business::where([['id', '=', input('post.business_id')], ['status', '=', 1], ['parent_id', '=', $this->user->id]])->field('type')->find();
		if (!$business)
		{
			return $this->returnError('商户或工作室不存在！');
		}
		$model->type = $business['type'];

		// 类型：1商户 2卡商 3四方 4四方商户 5子账号 6下级卡商
		$user = $this->user->type == 1 ? $this->user : $this->user->parent;

		$model->name = input('post.name');
		$model->parent_id = $user->id;
		$model->chat_id = input('post.chat_id');
		$model->business_id = input('post.business_id');
		$model->quota = input('post.quota');
		$model->quota_status = input('post.quota_status');
		$model->usdt = input('post.usdt');
		$model->usdt_gears = input('post.usdt_gears');
		$model->operator_status = input('post.operator_status');
		$model->status = intval(input('post.status'));
		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}
		return $this->returnSuccess('保存成功');
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
		$model = BotGroup::where('no', $no)->find();
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
		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = BotGroup::where('no', $no)->find();
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


		\think\facade\Db::startTrans();
		try
		{

			foreach ($ids as $no)
			{
				$model = BotGroup::where('no', $no)->find();
				if (!$model)
				{
					throw new \Exception('无法找到信息');
				}
				$model->delete();
			}

			\think\facade\Db::commit();

			return $this->returnSuccess('ok');

		}
		catch (\Exception $e)
		{

			\think\facade\Db::rollback();

			return $this->returnError($e->getMessage());

		}
	}

}
