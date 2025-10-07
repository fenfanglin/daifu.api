<?php
namespace app\admin\controller;

use app\extend\common\BaseController;

/**
 * 列出系统选项
 * extends BaseController（不用检查权限）
 * extends AuthController（就需要检查权限）
 */
class OptionController extends AuthController
{
	/**
	 * 角色
	 */
	public function role()
	{
		$rule = [
			'center_id' => 'integer',
		];

		if (!$this->validate(input('post.'), $rule))
		{
			return $this->returnError($this->getValidateError());
		}

		$center_id = intval(input('post.center_id'));

		$query = \app\model\Role::field('id, name');

		if (!empty($center_id))
		{
			$query->where('center_id', $center_id);
		}

		$list = $query->order('id asc')->select();
		// var_dump($query->getLastSql());
		// var_dump($query->buildSql(true));

		$data = [];

		$tmp = [];
		$tmp['id'] = -1;
		$tmp['name'] = $center_id > 0 ? '代理所有权限' : '系统所有权限';

		$data[] = $tmp;

		foreach ($list as $value)
		{
			$tmp = [];
			$tmp['id'] = (int)$value['id'];
			$tmp['name'] = $value['name'];

			$data[] = $tmp;
		}

		return $this->returnData($data);
	}

	/**
	 * 商户
	 */
	public function business()
	{
		$query = \app\model\Business::field('id, username');

		$query->where('verify_status', 1); //认证状态：-1待认证 1已认证 2不通过
		$query->where('type', 1); //类型：1商户 2卡商 3四方 4四方商户

		$list = $query->order('id asc')->select();

		$data = [];
		foreach ($list as $value)
		{
			$tmp = [];
			$tmp['id'] = (int)$value['id'];
			$tmp['name'] = $value['username'];

			$data[] = $tmp;
		}

		return $this->returnData($data);
	}

    public function get_usdt_rate()
    {
        $data = $this->setting->usdt_rate;
        return $this->returnData($data);
    }
}
