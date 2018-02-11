<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\Request;
class Nav extends Common
{
	
	public function __construct(Request $request)
	{
		parent::__construct($request);
		$this->dao = model($this->controllername);
		//过滤掉POST过来与数据库表字段不对应的数据，避免报错
		//先获取到数据表的字段
		$tableinfo = $this -> dao -> getTableInfo();
		$fields = $tableinfo['fields'];
		foreach($fields as $v){
			if(isset($_POST[$v])){
				$this->postdata[$v] = $_POST[$v];
			}
		}
	}
	
	
	public function index($return = false)
	{
		$this->map = 'pid = 0';
		$db = model('common');
        $user = $db -> _list($this->map, $this->view, $this->order, $this->pagesize, $this->controllername);
		//使用视图获取模板中的数据变量
		$list = $this -> view -> __get('list');
		$nodedb = model('nav');
		foreach($list as $k => $v){
			$list[$k]['sub'] = $nodedb -> treeget($v['id']);
		}
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	
	//增加
	public function add(){
		$this->assign('pid', input('pid'));
		$nodedb = model('nav');
		$parentlist = $nodedb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		return $this->fetch();
	}
	
	/*修改*/
    public function modify ($return = true)
    {
		$nodedb = model('nav');
		$parentlist = $nodedb -> treegetall(array('pid' => 0));
		$this -> assign('parentlist', $parentlist);
		parent::modify(false);
		return $this->fetch();
    }
	
}