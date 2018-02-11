<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Article extends Common
{
	
	//文章列表
	public function index($return = false)
	{
		if(input('cid') > 0){
			$this -> map['category_id'] = intval(input('cid'));
		}
		parent::index(false);
		$list = $this->view -> __get('list');
		$categoryDb = Db::name('articleclass');
		foreach($list as $k => $v){
			$list[$k]['classname'] = $categoryDb -> where(array('id' => $v['category_id'])) -> value('classname');
		}
		$this->assign('list', $list);
		return $this->fetch();
	}
	public function add()
	{
		$parentlist = Db::name('articleclass') -> select();
		$this -> assign('parentlist', $parentlist);
		return $this->fetch();
	}
	//修改商品
	public function modify($return = false)
	{
		$parentlist = Db::name('articleclass') -> select();
		$this -> assign('parentlist', $parentlist);
		parent::modify(false);
		return $this->fetch();
	}
}

