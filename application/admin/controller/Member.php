<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Member extends Common
{
	//显示增加会员的页面
	public function add()
	{
		$parentlist = Db::name('level') -> select();
		$this -> assign('levellist', $parentlist);
		return $this->fetch();
	}
	
	//修改会员
	public function modify($return = false)
	{
		$parentlist = Db::name('level') -> select();
		$this -> assign('levellist', $parentlist);
		parent::modify(false);
		return $this->fetch();
	}
	
	
	public function insert($data = '')
	{
		$this->postdata['password'] = md5($_POST['password']);
		parent::insert();
	}	
	
	public function update()
	{
		if(intval(input('rate')) > 0){
			$ratearr = array();
			$rate = ceil(intval(input('rate'))/10);
			for($i = 1; $i < 11; $i++){
				if($i <= $rate){
					$ratearr[] = 1;
				}else{
					$ratearr[] = 0;
				}
			}
			shuffle($ratearr);
			$this->postdata['ratearr'] = serialize($ratearr);
		}else{
			$this->postdata['ratearr'] = '';
		}
		//if(intval(input('rate')) == 0){
			$this->postdata['rate'] = intval(input('rate'));
		//}
		if(intval(input('cards')) == 0){
			unset($this->postdata['cards']);
		}
		parent::update();
	}
	
}
