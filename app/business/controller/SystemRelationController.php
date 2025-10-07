<?php
namespace app\business\controller;

use app\extend\common\Common;
use app\service\SystemRelationService;
use app\model\Business;
use app\model\JQK_Business;

class SystemRelationController extends AuthController
{
	private $controller_name = '单点系统';

	/**
	 * 获取绑定JQK
	 */
	public function get_bind_jqk()
	{
		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->where('parent_id', $this->user->id)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		// 获取代付绑定JQK系统的工作室id
		$jqk_business_id = SystemRelationService::getDaifuBindingJqkBusinessId($model->id);

		// 检查JQK卡商ID是否存在
		$model_jqk = JQK_Business::where('id', $jqk_business_id)->find();
		if ($model_jqk)
		{
			$data = [
				'jqk_business_id' => $model_jqk->id,
				'jqk_business_name' => $model_jqk->realname,
			];
		}
		else
		{
			// 代付商户解绑JQK商户
			SystemRelationService::daifuBusinessUnbindingJqkBusiness($model->id);

			$data = [
				'jqk_business_id' => '',
				'jqk_business_name' => '',
			];
		}

		return $this->returnData($data);
	}

	/**
	 * 绑定JQK
	 */
	public function bind_jqk()
	{
		$this->writeLog($this->controller_name . '绑定代付');

		$rule = [
			'no' => 'require',
			'jqk_business_id' => 'require|integer|>:0',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->where('parent_id', $this->user->id)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		$parent_jqk_business_id = SystemRelationService::getDaifuBindingJqkBusinessId($this->user->id);
		if (!$parent_jqk_business_id)
		{
			return $this->returnError('您代理账号未绑定JQK商户/四方账号');
		}

		// 检查JQK卡商ID是否存在
		$model_jqk = JQK_Business::where('id', input('post.jqk_business_id'))->find();
		if (!$model_jqk)
		{
			return $this->returnError('JQK卡商ID不存在');
		}

		if ($model_jqk->parent_id != $parent_jqk_business_id)
		{
			return $this->returnError('JQK卡商ID不属于您下级账号');
		}

		// 类型：1商户 2卡商 3四方 4四方商户 5卡商子账号 6下级卡商
		if (!in_array($model_jqk->type, [2]))
		{
			return $this->returnError('JQK卡商ID不是卡商账号');
		}

		// 获取代付绑定JQK系统的工作室id
		$check = SystemRelationService::getDaifuBindingJqkBusinessId($model->id);
		if ($check > 0)
		{
			return $this->returnError('不能重复绑定');
		}

		// 获取JQK绑定代付系统的卡商id
		$check = SystemRelationService::getJqkBindingDaifuBusinessId($model_jqk->id);
		if ($check > 0)
		{
			return $this->returnError('JQK卡商ID已绑定其他商户');
		}

		// 代付商户绑定JQK商户
		$check = SystemRelationService::daifuBusinessBindingJqkBusiness($model->id, input('post.jqk_business_id'));
		if ($check == false)
		{
			return $this->returnError('绑定失败');
		}

		return $this->returnSuccess('成功');
	}

	/**
	 * 解绑JQK
	 */
	public function unbind_jqk()
	{
		$this->writeLog($this->controller_name . '解绑代付');

		$rule = [
			'no' => 'require',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$no = input('post.no');
		$model = Business::where('no', $no)->where('parent_id', $this->user->id)->find();
		if (!$model)
		{
			return $this->returnError('无法找到信息');
		}

		// 代付商户解绑JQK商户
		$check = SystemRelationService::daifuBusinessUnbindingJqkBusiness($model->id);
		if ($check == false)
		{
			return $this->returnError('解绑失败');
		}

		return $this->returnSuccess('成功');
	}
}