<?php
namespace app\game\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19
 * Time: 16:49
 */
use think\Controller;

class Redbag extends Common
{
    public  function  index()
    {
        //显示红包页面
        return $this->fetch();
    }
    //接收红包数量
    public function getdata()
    {
        $num = intval(input('post.roomcard'));
        $result =  model('redbag') -> create_bag($this -> memberinfo['id'] , $num);
        if($result){
            $this->success('创建成功',url('share',array('id'=>$result)));
        }else{
            $error = model('redbag') -> getError();
            $this->error($error);
        }
    }
    //分享房卡红包
    public function share()
    {
        $redid = input('id');
        $member = model('redbag') -> baginfo($redid);
        $this->assign('member' ,$member);
        return $this->fetch();
    }

    //打开红包
    public function open()
    {
        $redid = input('id');
        $minfo = model('redbag')-> open_bag($this->memberinfo['id'] , $redid);
        if($minfo){
            $this->success('领取成功');
        }else{
            $this->error(model('redbag') ->getError());
        }
    }
    //显示红包房卡记录,我收到的
    public function history()
    {
        model('common') -> _list(array('receive_id' => $this->memberinfo['id']), $this->view, 'create_time desc', 10, 'redbag');
        return $this->fetch();
    }

    //显示红包房卡记录，我发出的
    public function historysended()
    {
        model('common') -> _list(array('create_id' => $this->memberinfo['id']), $this->view, 'create_time desc', 10, 'redbag');
        return $this->fetch();
    }
}