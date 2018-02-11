<?php 
namespace app\member\controller;
use think\Controller;
use think\Db;
use think\Validate;
use think\Session;
use think\Cookie;
use think\Request;
class Login extends Controller
{
public function doregister()
	{
		$data['Email'] = $_POST['email'];
		$data['password'] = md5($_POST['password']);
		$data['username'] = $data['Email'];
		$data['reg_time'] = time();
		$data['nickname'] = $_POST['nickname'];
		if(input('password') != input('repassword')){
    			$this->error('两次输入的密码不一致，请重新输入。'.input('repassword'));

		}
		$result = model('member')->validate([
			'Email' => 'email',
			'username' =>'unique:member'
			],
			[
			'email' => '邮箱格式不正确',
			'username'=>'用户已存在',
			])
			->save($data);
			
		if($result){
			Session::set('member_id' , model('member') -> id);
			$this->success('注册成功');
			
		}else{
			$this->error("注册失败" .model('member')->geterror());
		}
		
	}
	public function regout()
	{
		Session::set('member_id' , null);
		$this->success('退出成功');
	}
	public function doLogin()
	{	
		
		$member = model('Member');
		$data['Email'] = input('Email');
		$data['password'] = md5(input('password'));
		$result = $member ->checklogin($data);
		//dump($result);
		if($result){
			
			Session::set('member_id' , $result['id']);
			if(input('post.remember')=='on'){
				Cookie::set('Email' , $data['Email'] ,time()+3600);
				Cookie::set('password' , input('password') , time()+3600);
				Cookie::set('remember' , 'on' , time()+3600);
			}
			
			$this->success('登陆成功');
			
		}else{
			$this->error($member->getError());
		}
		
	}
	
	
}