<?php

/**
 * Created by PhpStorm.
 * User: wujunyuan
 * Date: 2017/7/3
 * Time: 13:19
 * 游戏大厅控制器，继承Common，作用是从Common中统一控制用户登录
 */
namespace app\game\controller;

use think\Db;
use think\Controller;

class Index extends Common
{
    /**
     * 显示游戏大厅界面
     */
    public function index()
    {
        //model('room') -> accounttemp(2);
        //echo '这里游戏大厅';
        return $this->fetch();
    }
    public function index9()
    {
        //model('room') -> accounttemp(2);
        //echo '这里游戏大厅';
        return $this->fetch();
    }
    public function index69()
    {
        //model('room') -> accounttemp(2);
        //echo '这里游戏大厅';
        return $this->fetch();
    }
}
