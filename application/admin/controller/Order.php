<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Order extends Common
{
	/*查看订单商品的明细*/
	public function modify($return = true)
	{
		//商品详细
		if(input('id') && intval(input('id')) > 0){
			$id = intval(input('id'));
			$list = Db::name('sale') -> where(array('order_id' => $id)) -> select();
			$this->assign('list', $list);
		}else{
			$this->error('订单ID错误!');
		}
		
		parent::modify(false);
    	return $this->fetch();
	}
	
	//查询订单列表
	public function index($return = false){
		parent::index(false);
		$list = $this->view -> __get('list');
		$categoryDb = Db::name('member');
		foreach($list as $k => $v){
			$list[$k]['member_nickname'] = $categoryDb -> where(array('id' => $v['member_id'])) -> value('nickname');
		}
		$this->assign('list', $list);
		return $this->fetch();
	}
}
