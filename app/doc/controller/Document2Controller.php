<?php
namespace app\doc\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class Document2Controller extends BaseController
{
	protected $template = '/document2/template';

	public function api_notify()
	{
		$assign['include_file'] = 'document2/api_notify';

		return view($this->template, $assign);
	}

	public function api_query()
	{
		$assign['include_file'] = 'document2/api_query';

		return view($this->template, $assign);
	}

	public function api_sign()
	{
		$assign['include_file'] = 'document2/api_sign';

		return view($this->template, $assign);
	}
}