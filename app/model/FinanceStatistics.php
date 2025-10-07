<?php
namespace app\model;

use app\extend\common\BaseModel;

class FinanceStatistics extends BaseModel
{
	protected $table = 'pay_finance_statistics';
	
	// 自动生成no参数
	protected $generate_no = false;
	
	// 检查参数是否重复
	protected $unique_field = [];
	
	
	public function business()
	{
		return $this->belongsTo(Business::class, 'business_id', 'id');
	}
	
	public function subBusiness()
	{
		return $this->belongsTo(Business::class, 'sub_business_id', 'id');
	}
}