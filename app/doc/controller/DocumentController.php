<?php
namespace app\doc\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class DocumentController extends BaseController
{
	protected $template = '/document/template';

	public function api_create()
	{
		$assign['include_file'] = 'document/api_create';

		return view($this->template, $assign);
	}

	public function api_notify()
	{
		$assign['include_file'] = 'document/api_notify';

		return view($this->template, $assign);
	}

	public function api_query()
	{
		$assign['include_file'] = 'document/api_query';

		return view($this->template, $assign);
	}

	public function api_account_info()
	{
		$assign['include_file'] = 'document/api_account_info';

		return view($this->template, $assign);
	}

	public function api_sign()
	{
		$assign['include_file'] = 'document/api_sign';

		return view($this->template, $assign);
	}
}