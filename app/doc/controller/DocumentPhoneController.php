<?php
namespace app\doc\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class DocumentPhoneController extends BaseController
{
	protected $template = '/document_phone/template';

	public function api_create()
	{
		$assign['include_file'] = 'document_phone/api_create';

		return view($this->template, $assign);
	}

	public function api_notify()
	{
		$assign['include_file'] = 'document_phone/api_notify';

		return view($this->template, $assign);
	}

	public function api_query()
	{
		$assign['include_file'] = 'document_phone/api_query';

		return view($this->template, $assign);
	}

	public function api_sign()
	{
		$assign['include_file'] = 'document_phone/api_sign';

		return view($this->template, $assign);
	}
}