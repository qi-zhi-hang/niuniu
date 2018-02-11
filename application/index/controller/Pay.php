<?php
namespace app\index\controller;
use think\Controller;
use think\Loader;
use think\Db;
class Pay extends Common
{
	// 支付类型列表
	public function index(){
		if(input('id') && intval(input('id')) > 0){
			$id = intval(input('id'));
			$orderdb = model('order');
			$this->orderinfo = $orderdb -> where(array('id' => $id)) -> find() -> toArray();
			if($this->orderinfo['status'] == 2){
				$this->error('该订单已经完成支付，请不要重复操作');exit;
			}
			$paymentList = Db::name('pay') -> where(array('status' => 1)) -> select();
			if(count($paymentList) == 1){
				//更新订单支付类型
				$orderdb -> where(array('id' => $id)) -> data(array('payment_id' => $paymentList[0]['id'])) -> update();
				$this->pay($id);
			}
			
			if(input('post.pay_id')){
				$orderdb -> where(array('id' => $id)) -> data(array('payment_id' => input('post.pay_id'))) -> update();
				$this->pay($id);
			}
			
			$this->assign('paymentList', $paymentList);
			
			return $this->fetch();
		}else{
			$this->error('参数错误、订单错误或者不存在');
		}
	}
	//发起支付
	public function pay($orderid){
		$orderdb = Db::name('order');
		$this->orderinfo = $orderdb -> where(array('id' => $orderid)) -> find();
		$payment = Db::name('pay') -> where(array('status' => 1, 'id' => $this->orderinfo['payment_id'])) -> find();
		Loader::import('extend.Payment.'.$payment['classname']);
		$affclass = $payment['classname'];
		$aff = new $affclass();
		$html = $aff -> pay($orderid);
		$this->assign('html', $html);
	}
	
	//异步回调
	public function notify(){
		$code = input('get.code');
		Loader::import('extend.Payment.'.$code);
		$affclass = new $code();
		$mes = $affclass->callback();
	}
}