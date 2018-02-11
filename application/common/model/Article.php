<?php
namespace app\common\model;
use think\Model;
use think\Db;
class Article extends Model
{
	/*数据唯一验证，登录名不可以重复*/
	 protected $validate = array(
	     'rule' => array('title' => 'require'),
	 	 'msg' => array('title.require' => '请填写标题！')
    ); 
}