<?php
namespace app\common\model;
use think\Model;
use think\Db;
class Comment extends Model
{
	public function treeget($pid=0,$deep=0,$arr=array()){
		$re=$this->where(array('pid'=>$pid))->select();
		if($re!==false){
			$deep++;
			foreach($re as $k=>$v){
				$v['deep']=$deep;
				$arr[]=$v -> toArray();
				$arr=$this->treeget($v['id'],$deep,$arr);
			}
			return $arr;
		}
	}
	//获取一个树结构数据，默认获取所有
	public function treegetall($map)
	{
		$re= Db::name('comment') -> where($map) -> select();

		$ret = array();
		if($re!==false){
			foreach($re as $k=>$v){
				$v['deep'] = 0;
				$ret[$k] = $v;
				$ret[$k]['sub']=$this->treeget($v['id']);
			}
			return $ret;
		}
		return false;
	}
}
