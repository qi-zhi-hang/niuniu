<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/20
 * Time: 11:32
 */
class Redbag extends Common
{
    public function index($return = false)
    {
        parent::index(false);
        return $this->fetch();
    }
}