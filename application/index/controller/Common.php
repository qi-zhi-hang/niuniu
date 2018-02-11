<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use think\Db;
class Common extends Controller
{
	public function __construct()
	{
		parent::__construct();
		if(Session::has('member_id')){
			$this->assign('islogin', true);
			$info = Db::name('member')->where(array('id' =>Session::get('member_id')))->find();
			$this->assign('memberinfo', $info);
		}else{
			$this->assign('islogin' , false);
		}
		//查询导航
		$navlist = Db::name('nav')->where(array('pid' => 0)) ->order('sort') -> select();
		foreach($navlist as $k => $v){
			//查询子导航
			$navlist[$k]['sub'] = Db::name('nav') -> order('sort') -> where(array('pid' => $v['id'])) -> select();
		}
		$this->assign('navlist', $navlist);
		
		$adlist = Db::name('ad')->where(array('status' => 1)) -> select();
		$this->assign('adlist', $adlist);
		
		// 推荐和置顶商品
		$toplist = Db::name('product') -> where(array('is_top' => 1)) -> limit(2) -> select();
		$this->assign('toplist', $toplist);
		$reclist = Db::name('product') -> where(array('is_rec' => 1)) -> limit(3) -> select();
		$this->assign('reclist', $reclist);
		
		$categorylist = Db::name('category') -> where(array('status' => 1, 'pid' => 0)) -> select();
		
		$this->assign('categorylist', $categorylist);
		$catelist = Db::name('category') -> where(array('status' => 1, 'pid' => 0)) -> limit(5) -> select();
		foreach($catelist as $k => $v){
			$allcid = model('category') -> getAllIDBypid($v['id']);
			$catelist[$k]['product'] = Db::name('product') -> where(array('status' => 1, 'category_id' => array('in', implode(',', $allcid)))) -> limit(8) -> select();
		}
		
		
		
		$this->assign('catelist', $catelist);
	} 
	
	
	
	public function getRegion(){
		$father_id = isset($_POST['fid'])?intval($_POST['fid']):0;
		$data = Db::name('Region')->where(array('father_id'=>$father_id))->select();
		$str = '<option value="">-请选择-</option>';
		foreach($data as $k => $v){
			$str .= '<option value="'.$v['region_id'].'">'.$v['region_name'].'</option>';
		}
		if(count($data)>0){
			$str = '<select style="width:auto; display:inline; margin-right:8px;" class="form-control" id="select" datatype="*" onchange="sendRegion(this)" name="region[]">'.$str.'</select>';
		}else{
			$str='';
		}
		echo $str;
	}
	
	public function setRegion(){
		
		
		$area = explode(',', trim($this->_post('area'),','));
		
		$ret = "";
		foreach($area as $skey => $sid){
			$father_id = $skey == 0 ? 0 : $area[$skey - 1];
			$data = Db::name('Region')->where(array('father_id'=>$father_id))->select();
			$str = '<option value="">-请选择-</option>';
			foreach($data as $k => $v){
				$selectstr = "";
				if($sid == $v['region_id']){
					$selectstr = 'selected="selected"'; 
				}
				$str .= '<option '.$selectstr.' value="'.$v['region_id'].'">'.$v['region_name'].'</option>';
			}
			if(count($data)>0){
				$str = '<select style="width:auto; display:inline; margin-right:8px;" class="form-control" id="select" datatype="*" onchange="sendRegion(this)" name="region[]">'.$str.'</select>';
			}else{
				$str='';
			}
			$ret .= $str;
		}
		
		echo $ret;
		
		
	}
	
	
	
}