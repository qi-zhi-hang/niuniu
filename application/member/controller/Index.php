<?php 
namespace app\member\controller;
use think\Controller;
use think\Db;
use think\Validate;
use think\Session;
class Index extends Common
{
	public function index()
	{
		return $this->fetch();
	}
	
}
