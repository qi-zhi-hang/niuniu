<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class User extends Common
{
	public function modify($return = false)
	{
		//所有的role的用户列表
		$rolelist = Db::name('role') -> select();
		$this->assign('rolelist', $rolelist);
		//调用父级的modify方法
		parent::modify(false);
		$info = $this->view -> __get('info');
		$managelist = explode(',', $info['role_id']);
		$this->assign('managelist', $managelist);
		//dump($managelist);
		return $this->fetch();
	}

	public function update()
	{
		//用户权限
		//拼接头尾带‘,’的字符串，以便数据库进行精确的模糊查询
		$_POST['role_id'] = ','.implode(',', $_POST['roleid']).',';
		//为什么销毁？生成的sql语句会报错，因为字段不存在
		unset($_POST['roleid']);
		if(isset($_POST['password']) && $_POST['password'] == ''){
			unset($_POST['password']);
		}
		parent::update();

	}

}