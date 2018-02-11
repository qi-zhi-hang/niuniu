<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/13
 * Time: 15:45
 */
namespace app\game\controller;
use think\Controller;
use think\Session;
use think\Db;
class Result extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}