<?php
/**
 * Created by PhpStorm.
 * User: 13603
 * Date: 2018/1/31
 * Time: 9:04
 */

namespace app\game\controller;

use think\Db;

class Weixinpay extends Common {

    public function index()
    {

        require_once "./wxpy/lib/WxPay.Data.php";
        require_once "./wxpy/lib/WxPay.JsApiPay.php";
        require_once "./wxpy/lib/WxPay.Config.php";
        $tools = new \JsApiPay();
        $input = new \WxPayUnifiedOrder();
        $openid = $this->memberinfo['openid'];

        if(!$openid){
            $openid = $tools->GetOpenid();
            if(!$openid){
                return false;
            }
        }
        $username = Db::name('member')->where(array('openid'=>$openid))->field('nickname')->find();
        if(!$username){
            $username['nickname'] = '无名称';
        }
        $order_sn = \WxPayConfig::MCHID.date("YmdHis");
        $input->SetBody("会员充值");
        //$input->SetAttach("会员充值");
        $input->SetOut_trade_no($order_sn);
        $input->SetTotal_fee("1");
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("会员充值");
        $input->SetNotify_url("http://niuniu.pabupa.wang/game.php/notify/notifyorder");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        if($jsApiParameters){
            $data = array();
            $data['openid'] = $openid;
            $data['order_sn'] = $order_sn;
            $data['status'] = 0;
            $data['money'] = 1;
            $data['add_time'] = time();
            $data['username'] = $username['nickname'];
            $data['member_id'] = $this->memberinfo['id'];
            Db::name('niuorder')->insert($data);
        }
        $this->assign('jsApiParameters',$jsApiParameters);
        return $this->fetch();
    }


}