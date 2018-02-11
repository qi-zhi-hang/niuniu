<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\Loader;
class Pay extends Common
{
	public function modify($return = false){
		$id = intval(input('id'));
		$info = $this->dao-> where(array('id' => $id)) -> find() -> toArray();
		Loader::import('extend.Payment.'.$info['classname']);
		$affclass = $info['classname'];
		$aff = new $affclass();
		$html = $aff -> createform();
		$this->assign('formhtml', $html);
		parent::modify(false);
		return $this -> fetch();
	}
	public function update(){
		Loader::import('extend.Payment.'.input('post.classname'));
		$affclass = input('post.classname');
		$aff = new $affclass();
		$aff -> saveform();
		parent::update();
	}
	
	public function insert($return = false){
		$map['classname'] = input('post.classname');
		$ret = $this->dao -> where($map) -> find();
		if($ret){
			$this->error('请不要重复添加该接口！');return;
		}
		parent::insert();
	}
	
}
