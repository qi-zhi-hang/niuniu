<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\Session;
use think\app\common\model\User;
use think\captcha;
class Login extends Controller
{
	
	private $config  = array(
		// 验证码字符集合
		'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY', 
		// 验证码字体大小(px)
		'fontSize' => 18, 
		// 是否画混淆曲线
		'useCurve' => true, 
		 // 验证码图片高度
		'imageH'   => 38,
		// 验证码图片宽度
		'imageW'   => 156, 
		// 验证码位数
		'length'   => 4, 
		// 验证成功后是否重置        
		'reset'    => true
	);
	/*登录页面*/
    public function index()
    {
    					//定义一个userid
        if(Session::has('userid')){
            //重定向，指定的url
            $this->redirect(url('Index/index'));
        }
		return $this -> fetch('index');
    }
	/*登录认证*/
    public function dologin()
    {
		if(!$this->verify_check(input('verify'))){
			$this -> error('验证码错误！');
		}
		$user = model('User');
        $data['account'] = input('account');
        $data['password'] = md5(input('admin_pass'));
        $ret = $user -> checklogin($data);
        if($ret){
			//设置会话标识
			//dump($ret['id']);
			Session::set('userid',$ret['id']); 

			//设置$_SESSION['userid'] = $ret['id'];

            $this->success('登录成功！',url('Member/index'));
        }else{
            $this->error($user ->getError());
        }

    }
	
	
	function verify_check($value)
	{
		$captcha = new \think\captcha\Captcha($this->config);
		return $captcha->check($value, 'glp');
	}

	
	
	//验证码
	public function verify(){
		
		$captcha = new \think\captcha\Captcha($this->config);
        return $captcha->entry('glp');
	}
	//退出登录
	public function logout(){
		Session::delete('userid');
		$this->success('成功退出登录！',url('Login/index'));
	}
	
}
