<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\Request;
class Config extends Common
{
	public function __construct(Request $request)
	{
		parent::__construct($request);
		$this->pagesize = 30;
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
	//保存配置的值
	public function save(){
		$data = $_POST;
		$flag = true;
		foreach($data as $k => $v){
			$ret = $this-> dao -> where(array('name' => $k)) -> update(array('value' => $v));
			if($ret === false){
				$flag = false;
			}
		}
		if($flag){
			$this -> success('保存成功！');
		}else{
			$this->error('保存失败！请联系管理员！');
		}
	}
}