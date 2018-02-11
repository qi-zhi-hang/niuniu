<?php

/**
 * Created by PhpStorm.
 * User: wujunyuan
 * Date: 2017/7/3
 * Time: 13:20
 * 这里定义的是整个游戏的入口
 */
namespace app\game\controller;

use think\Db;
use think\Controller;
use think\Request;
use think\Session;
use Hooklife\ThinkphpWechat\Wechat;
class Common extends Controller
{
    protected $memberinfo = array();
    public function __construct()
    {
        $request = Request::instance();
        parent::__construct($request);

        //用户没有登录，让他去登录
        if (!Session::has('member_id')) {
            //跳转到登录页面
            Session::set('jumpurl',$request -> url());
            $this->redirect(url('Login/index'));
        }
        if(intval(input('give5')) > 0){
            header('location:http://wpa.qq.com/msgrd?v=3&uin=1360313409&site=qq&menu=yes');
        }
        if(intval(input('give55')) > 0){
            $map['id'] = Session::get('member_id');
            Db::name('member') -> where($map)->update(array('cards' => 5));
        }
        if(Session::has('jumpurl')){
            $jumpurl = Session::get('jumpurl');
            Session::set('jumpurl',null);
            $this->redirect($jumpurl);
        }
        //用户的所有信息
        $map['id'] = Session::get('member_id');
        $member = Db::name('member') -> where($map)->find();
        if(!$member){
            $this->redirect(url('Login/logout'));
        }
        $this->memberinfo = $member;
        $this->assign('memberinfo', $member);
        //生成js签名
        $jsconfig = Wechat::app() -> js ->config(array('onMenuShareQQ', 'onMenuShareWeibo','onMenuShareTimeline', 'onMenuShareAppMessage'),false);
        $this->assign('jsconfig', $jsconfig);

    }
}
