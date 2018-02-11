<?php
namespace app\game\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19
 * Time: 18:02
 */
use think\Controller;
class Myhome extends Common
{
    public  function  index()
    {
        //显示个人主页
        return $this->fetch();
    }
}