<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Role extends Common
{
	
	public function access ()
	{

		if(input('post.id') && intval(input('post.id')) > 0){
			//拼接头尾带‘,’的字符串，以便数据库进行精确的模糊查询
			/*
			where like '%10%'100,110%,10,%,10,100,1000,*/
		
			$_POST['level'] = ','.implode(',',$_POST['nodeid']).',';
			unset($_POST['nodeid']);

			parent::update();

		}else{
			//调用节点model中node.php的模型
			$nodedb = model('node');

			//获取结构树
			$data = $nodedb -> treeget();
			parent::modify(false);
			//dump($data);
			$info = $this->view -> __get('info');
			$nodelist = explode(',', $info['level']);
			$this->assign('nodelist', $nodelist);

			$this->assign('list', $data);
			return $this->fetch();
		}
	}
}