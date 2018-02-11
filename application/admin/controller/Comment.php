<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Comment extends Common
{
	
	//修改商品
	public function modify($return = false)
	{
		$categorydb = model('comment');
		$parentlist = $categorydb -> treegetall(array('id' => intval(input('id'))));
		$this -> assign('parentlist', $parentlist);
		parent::modify(false);
		return $this->fetch();
	}
	
}
