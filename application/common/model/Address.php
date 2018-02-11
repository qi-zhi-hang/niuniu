<?php
namespace app\common\model;
use think\Model;
class Address extends Model{
	/*数据唯一验证，登录名不可以重复*/
	protected $validate = array(
	    'rule' => array('name' => 'require', 'area' => 'require'),
	 	'msg' => array('name' => '请输入收货人姓名！', 'area' => '请输入详细地址！')
    );
}