<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Node extends Common
{
	
	public function index($return = false){
		$this->map = 'pid = 0';
		$db = model('common');
        $user = $db -> _list($this->map, $this->view, $this->order, $this->pagesize, $this->controllername);
		//使用视图获取模板中的数据变量
		$list = $this -> view -> __get('list');
		$nodedb = model('node');
		foreach($list as $k => $v){
			$list[$k]['sub'] = $nodedb -> treeget($v['id']);
		}
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	
	//增加节点
	public function add(){
		$this->assign('pid', input('pid'));
		$nodedb = model('node');
		$parentlist = $nodedb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		return $this->fetch();
	}
	
	/*修改*/
    public function modify ($return = true)
    {
		$nodedb = model('node');
		$parentlist = $nodedb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		parent::modify(false);
		return $this->fetch();
    }
	
}