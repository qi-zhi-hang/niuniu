<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Delivery extends Common
{
	/*商品分类管理*/
   public function index($return = false)
	{
    	$this -> assign('list', array());
		$this -> assign('pages', '');

    	return $this->fetch();
	}
	
	
}
