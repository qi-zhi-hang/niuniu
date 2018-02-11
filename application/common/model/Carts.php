<?php
namespace app\common\model;
use think\Model;
use think\Db;
class Carts extends Model
{
	//总价
	public function gettoall($id)
	{
		return Db::name('carts')->where(array('member_id' => $id))->sum('total_money');
	}
	//总数
	public function getnum($id)
	{
		return Db::name('carts')->where(array('member_id' => $id))->sum('product_num');
	}
	//获取购物车的商品列表
	public function getList($id)
	{
		$array = [];
		$list = $this-> where(array('member_id' => $id)) -> select();
		foreach($list as $key => $val){
			$array[] = $val -> toArray();
		}
		return $array;
	}
	//判断购物车商品是否存在
	public function product_exists($member_id , $product_id)
	{	$re = $this->where(array('member_id' => $member_id ,'product_id' =>$product_id))->find();
		if($re){
			return $re;
		}else{
			return false;
		}
	}
}