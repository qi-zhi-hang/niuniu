<?php
namespace app\member\controller;
use think\Controller;
use think\Db;
use think\Validate;
use think\Session;
class Common extends \app\index\controller\Common
{
	public function __construct()
	{
		parent::__construct();
		if(!Session::has('member_id')){
			//重定向，指定的url
            
		}
	}
}