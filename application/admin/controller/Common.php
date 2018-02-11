<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\Request;
use think\Session;
class Common extends Controller
{
	//数据访问对象
	public $dao;
	public $controllername;
	public $map = array();
	public $order = '';
	public $pagesize = 15;
	public $userinfo;
	/*管理后台入口，在这里做权限认证，没有登录的用户让他跳转到登录页面，登录页面是url('Public/login')*/
	/*Request 依赖注入*/
	public function __construct(Request $request){
		parent::__construct();
		//用户没有登录，让他去登录
		if(!Session::has('userid')){
			//跳转到登录页面
			$this->redirect(url('Login/index'));
		}
		
		//管理员个人信息
		$this->userinfo = Db::name('user') -> where(array('id' => Session::get('userid'))) -> find();

		//权限管理过滤(RBAC权限认证)
		if($this->userinfo['isadministrator'] == 0){
			$roleid = explode(',', trim($this -> userinfo['role_id'], ','));
			$roleid[] = 0;
			$map = array(
			          'id' => array('in', implode(',',$roleid))
				   );
			$level = Db::name('role') -> where($map) -> select();
			//允许用户继承多个角色权限
			$nodeid = array();
			$nodeid[] = 0;
			foreach($level as $k => $v){
				$nodeid = array_merge(explode(',', trim($v['level'], ',')), $nodeid);
			}
			//获取当前用户的所有
			$access = model('role') -> nodetreeget(array('id' => array('in', implode(',',$nodeid))));
			//获取模块名称
			$module = strtolower($request->module());
			//获取控制器名称
			$controller = strtolower($request->controller());
			//获取操作名称
			$action = strtolower($request->action());
			//不存在这个数组元素，说明用户没有权限，通过URL中的模块、控制器、操作的名称映射，来判断用户是否有权限访问
			if(!isset($access[$module][$controller][$action])){
				$this->error('对不起，您没有权限访问！');
				exit;
			}
		}


		$this->assign('userinfo', $this->userinfo);
		//数据库中要管理的表有哪些？有新的表要管理需要在这里增加
		$tables = array(
		    'user',
			'product',
			'category',
			'order',
			'article',
			'node',
			'role',	
			'articleclass',
			'level',
			'pay',
			'member',
			'shipping',
			'ad',
			'comment',
			'redbag'
	    );
		//当前访问的控制器名称是什么

		$this->controllername = strtolower($request -> controller());
		if(in_array($this -> controllername, $tables)){
			$this->dao = model($this->controllername);
			
			//过滤掉POST过来与数据库表字段不对应的数据，避免报错
			//先获取到数据表的字段
			$tableinfo = $this -> dao -> getTableInfo();
			$fields = $tableinfo['fields'];
			foreach($fields as $v){
				if(isset($_POST[$v])){
				    $this->postdata[$v] = $_POST[$v];
				}
			}
		}
	}
	
	/* 实现默认情况下所有数据表的列表 分页*/
    public function index($return = true)
    {
		$db = model('common');
        $user = $db -> _list($this->map, $this->view, $this->order, $this->pagesize, $this->controllername);
		if($return){
			return $this->fetch();
		}
    }
	/*删除*/
	public function delete()
	{
		$id = intval(input('id'));
		$ret = $this->dao -> where(array('id' => $id)) -> delete();
		if($ret){
			$this -> success('删除成功！');
		}else{
			$this->error($this->dao -> getError());
		}
	}
	/*添加*/
	public function add()
	{
		return $this->fetch();
	}
	/*增加数据*/
	public function insert($data = '') 
	{
        $result = $this->dao->save($this->postdata);
		if (false !== $result) 
		{
			$this->success('添加成功', url('index'));
		} else {
			$this->error($this->dao->getError());
		}
    }
    /*显示修改页面*/
    public function modify ($return = true)
    {
    	/*防止注入*/
    	$id  = intval(input('id'));
    	$map['id'] = $id;
    	$info = $this -> dao -> where($map) -> find() -> toArray();
    	$this->assign('info', $info);
		if($return){
			return $this->fetch();
		}
    	
    }
    /*修改数据*/
    public function update()
    {
        if (input('id') && intval(input('id')) > 0) {
            $map['id'] = array(
                'eq',
                intval(input('id'))
            );
            $result = $this->dao -> where($map) -> update($this->postdata);
            if ($result !== false) {
                $this->success('更新成功', url('index'));
            } else {
                $this->error($this->dao->getError());
            }
        } else {
            $this->error('参数错误');
        }
    }

    public function __upload()
    {
		// 获取表单上传文件 例如上传了001.jpg
		$file = request()->file('upfile');
		// 移动到框架应用根目录/public/uploads/ 目录下
		$info = $file->move(ROOT_PATH . 'static' . DS . 'uploads');
		if($info){
			// 成功上传后 获取上传信息
			return DS.'static' . DS . 'uploads'.DS.$info->getSaveName();
		}else{
			// 上传失败获取错误信息
			return $file->getError();
		}
	}
	public function upload(){
		echo $this->__upload();
	}

}
