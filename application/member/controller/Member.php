<?php
namespace app\member\controller;
use think\Controller;
use think\Db;
use think\Validate;
use think\Session;
class Member extends Controller
{
	public function update()
	{
		
		$memberid = Session('member_id');

		$data['Email'] = input('get.email');
		$data['nickname'] = input('get.nickname');
		$data['password'] = md5(input('get.password'));
		$data['tel'] = input('get.tel');
		
		$result = Db::name('member')->where(array('id' => $memberid))->update($data);
		
		if($result){
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}
}