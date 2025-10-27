<?php
namespace app\business\controller;

use app\extend\common\BaseController;
use app\extend\common\Common;
use app\model\Admin;
use app\model\BotBatch;
use app\model\BotGroup;
use app\model\Business;
use app\service\bot\BotGetMessageService;

class BotBatchController extends AuthController
{
	public $model;
	public function __construct()
	{
		parent::__construct();
		$this->model = new BotBatch();
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
		$type = 2;
		$create_time = $params['create_time'] ?? NULL;

		if ($is_export == 1)
		{
			$limit = Common::EXPORT_MAX_ROWS;
			$page = 0;
		}

		$query = $this->model::field('*');
		if (!empty($keyword))
		{
			$query->where('message', 'like', '%' . $keyword . '%');
		}
		if (!empty($type))
		{
			$query->where('type', $type);
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

		// 类型：1商户 2卡商 3四方 4四方商户 5子账号 6下级卡商
		$user = $this->user->type == 5 ? $this->user->parent : $this->user;

		$query->where('business_id', '=', $user->id);
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
			$tmp['type'] = $this->model::TYPE[$model->type];
			$tmp['receive'] = (string) $model->receive;
			$tmp['message'] = $model->message;
			$tmp['photo'] = $model->photo;
			$tmp['create_time'] = $model->create_time;
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
	 * 新增/修改
	 */
	public function save()
	{
		$rule = [
			'message|消息' => 'require',
		];
		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}
		//        $this->checkPermission('add', true);
		$model = new BotBatch();
		$type = input('post.business_type');

		$group_model = new BotGroup();
		$photo = input('post.photo');
		$message = input('post.message');
		$bot_message = new BotGetMessageService(['bot_token' => '7608921219:AAGy8oDjlr11SzYKfcucmjNWgropQNr8Was'], []);

		// 类型：1商户 2卡商 3四方 4四方商户 5子账号 6下级卡商
		$user = $this->user;

		if ($type == 1)
		{
			$model->receive = '所有群聊';
			$list = $group_model->where('parent_id', '=', $user->id)->select();
		}
		elseif ($type == 2 && !input('post.card_business_ids'))
		{
			$model->receive = '所有工作室';
			$list = $group_model->where('parent_id', '=', $user->id)->where('type', '=', 2)->select();
		}
		elseif ($type == 3 && !input('post.card_business_ids'))
		{
			$model->receive = '所有商户';
			$list = $group_model->where('parent_id', '=', $user->id)->where('type', '=', 3)->select();
		}
		else
		{
			$model->receive = implode(',', input('post.card_business_ids'));
			$list = $group_model->where('parent_id', '=', $user->id)->where('business_id', 'in', $model->receive)->select();
		}
		if (!$list)
		{
			return $this->returnError('无群聊可群发');
		}
		foreach ($list as $key => $value)
		{
			if ($photo)
			{
				$data = [
					'chat_id' => $value['chat_id'],
					'photo' => $photo,
					'caption' => $message,
				];
				$bot_message->sendForwardMessage($data);
			}
			else
			{
				$data = [
					'chat_id' => $value['chat_id'],
					'text' => $message,
				];
				$bot_message->sendOrderForwardTextMessage($data);
			}
		}

		//默认系统后台
		$model->business_id = $user->id;
		$model->type = 2;
		$model->message = $message;
		$model->photo = $photo;
		if (!$model->save())
		{
			return $this->returnError('保存失败');
		}
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
		\think\facade\Db::startTrans();
		try
		{

			foreach ($ids as $no)
			{
				$model = $this->model::where('no', $no)->find();
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

	public function get_business()
	{
		// 类型：1商户 2卡商 3四方 4四方商户 5子账号 6下级卡商
		$user = $this->user;

		$where = [];
		$where[] = ['type', '=', '3'];
		$where[] = ['parent_id', '=', $user->id];

		$list = BotGroup::where($where)->field('business_id,name')->select();
		$data = [];
		foreach ($list as $value)
		{
			$tmp = [];
			$tmp['id'] = (int) $value['business_id'];
			$tmp['name'] = $value['name'];

			$data[] = $tmp;
		}
		return $this->returnData($data);
	}

	public function get_card_business()
	{
		// 类型：1商户 2卡商 3四方 4四方商户 5子账号 6下级卡商
		$user = $this->user;

		$where = [];
		$where[] = ['type', '=', '2'];
		$where[] = ['parent_id', '=', $user->id];
		$list = BotGroup::where($where)->field('business_id,name')->select();
		$data = [];
		foreach ($list as $value)
		{
			$tmp = [];
			$tmp['id'] = (int) $value['business_id'];
			$tmp['name'] = $value['name'];

			$data[] = $tmp;
		}
		return $this->returnData($data);
	}
}
