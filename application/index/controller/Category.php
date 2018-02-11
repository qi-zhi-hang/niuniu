<?php 
namespace app\index\controller;
use think\Controller;
//用模型就不用引入
class Category extends Common
{
	public $map = array();
	public $order = '';
	public $pagesize = 5;
	public function index()
    {
		$this->map['category_id']=intval(input('id'));
		
		
		$this->map['status'] = 1;
		if($this->map['category_id'] == 0){
			unset($this->map['category_id']);
		}else{
			$this->map['category_id'] = array('in', model('category') -> getAllIDBypid(input('id')));
		}
		if(isset($_GET['keywords']) && $_GET['keywords'] != ''){
			$this->map['title'] = array('like', '%'.input('keywords').'%');
			
		}
		$db = model('common');
        $db -> _list($this->map, $this->view, $this->order, $this->pagesize, 'product');
		
		/*全部分类*/
		$categorydb = model('category');
		$categorylist = $categorydb -> treegetall(array('pid' => 0));
		$this -> assign('categorylist', $categorylist);
		return $this->fetch();
    }
}
