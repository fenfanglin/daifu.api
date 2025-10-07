<?php
namespace app\business\controller;

use app\extend\common\BaseController;

/**
 * 列出系统选项
 * extends BaseController（不用检查权限）
 * extends AuthController（就需要检查权限）
 */
class OptionController extends AuthController
{

	/**
	 * 卡商列表
	 */
	public function card_business()
	{
		$query = \app\model\Business::field('id, realname');

		$query->where('verify_status', 1); //认证状态：-1待认证 1已认证 2不通过
		$query->where('type', 2); //类型：1代理 2工作室 3商户
		$query->where('parent_id', $this->user->id);

		$list = $query->order('id asc')->select();

		$data = [];
		foreach ($list as $value)
		{
			$tmp = [];
			$tmp['id'] = (int)$value['id'];
			$tmp['name'] = $value['realname'];

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
