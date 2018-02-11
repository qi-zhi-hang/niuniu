<?php
namespace app\common\model;
use think\Model;
use think\Db;
class Role extends Model
{
	/*递归得到权限数组，用于判断一个角色或者多个角色的用户有没有权限*/
	public function nodetreeget($map, $pid = 0){
		$map['pid'] = $pid;
		$arr = array();
		$re=Db::name('node')->where($map)->select();
		if($re!==false){
			foreach($re as $k=>$v){
				$pid = $v['id'];
				$arr[$v['name']] = $this->nodetreeget($map, $pid);
			}			
		}
		return $arr;
	}

}