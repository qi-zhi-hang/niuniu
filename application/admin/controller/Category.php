<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Category extends Common
{
	public function index($return = false){
		$this->map = 'pid = 0';
		$db = model('common');
        $user = $db -> _list($this->map, $this->view, $this->order, $this->pagesize, $this->controllername);
		//使用视图获取模板中的数据变量
		$list = $this -> view -> __get('list');
		$categorydb = model('category');
		foreach($list as $k => $v){
			$list[$k]['sub'] = $categorydb -> treeget($v['id']);
		}
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	
	//增加节点
	public function add(){
		$this->assign('pid', input('pid'));
		$categorydb = model('category');
		$parentlist = $categorydb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		return $this->fetch();
	}
	
	/*修改*/
    public function modify ($return = true)
    {
		$categorydb = model('category');
		$parentlist = $categorydb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		parent::modify(false);
		return $this->fetch();
    }
}
