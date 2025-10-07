<?php
namespace app\doc\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class IndexController extends BaseController
{
	public function index()
	{
		Common::http404();

		// dd(app()->getThinkPath() . 'tpl/think_exception.tpl');
		// Common::error('index');
	}
}
