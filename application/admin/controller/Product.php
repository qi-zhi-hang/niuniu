<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Product extends Common
{
	//商品列表
	public function index($return = false)
	{
		parent::index(false);
		$list = $this->view -> __get('list');
		$categoryDb = Db::name('category');
		foreach($list as $k => $v){
			$list[$k]['category_name'] = $categoryDb -> where(array('id' => $v['category_id'])) -> value('title');
		}
		$this->assign('list', $list);
		return $this->fetch();
	}
	 
	//显示增加商品的页面
	public function add()
	{
		$categorydb = model('category');
		$parentlist = $categorydb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		return $this->fetch();
	}
	
	//修改商品
	public function modify($return = false)
	{
		$categorydb = model('category');
		$parentlist = $categorydb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		parent::modify(false);
		return $this->fetch();
	}
	
	//增加商品
	public function insert($data = '')
	{
		if(isset($_POST['mimg_url'])){
			if(count($_POST['mimg_url']) > 1){
				$this->postdata['mimg_url'] = implode(',', $_POST['mimg_url']);
			}else{
				$this->postdata['mimg_url'] = $_POST['mimg_url'][0];
			}
		}
		parent::insert();
	}
	//更新商品
	public function update()
	{
		
		if(isset($_POST['mimg_url'])){
			if(count($_POST['mimg_url']) > 1){
				$this->postdata['mimg_url'] = implode(',', $_POST['mimg_url']);
			}else{
				$this->postdata['mimg_url'] = $_POST['mimg_url'][0];
			}
		}
		parent::update();
	}
}
