<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Index extends Common
{
	public function index($return = false)
	{
		$productdb = Db::name('product');
		$data['productsum'] = (int)$productdb -> count();
		$orderdb = Db::name('order');
		$data['ordersum'] = (int)$orderdb -> count();
		$memberdb = Db::name('member');
		$data['membersum'] = (int)$memberdb -> count();
		$articledb = Db::name('article');
	    $data['articlesum'] = (int)$articledb -> count();
		$this->assign($data);
		/*今日区间*/
		$begintime=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endtime=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		$memberlist = Db::name('member') -> where("reg_time < $endtime and reg_time > $begintime") -> limit(5) -> select();
		$this->assign('memberlist', $memberlist);
		return $this->fetch();
	}
}
