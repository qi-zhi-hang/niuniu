<?php
namespace app\common\model;
use think\Model;

class User extends Model{
	
	/*数据唯一验证，登录名不可以重复*/
	 protected $validate = array(
	     'rule' => array('account' => 'unique:user|require'),
	 	 'msg' => array('account.unique' => '用户名已经存在！','account.require' => '请填写用户名！')
    ); 
	
	
	public function checklogin($data)
	{
		$map['account'] = $data['account'];
		if(empty($map['account'])){
			$this->error = '用户名不能为空！';
			return false;
		}
		$ret = $this->where($map) -> find();
		if($ret){
			$ret = $ret -> toArray();
			if($ret['status'] == 0){
				$this->error = '用户已禁用！';
				return false;
			}
			if($ret['password'] != $data['password']){
				$this->error = '密码错误请重新输入！';
				return false;
			}
			//更新用户登录信息，记录最后登录IP和最后登录时间
			$update['last_login_ip'] = get_client_ip();
			$update['last_login_time'] = time();
			$this-> save($update, array('id' => $ret['id']));
			return $ret;
		}else{
			return false;
		}
	}

}