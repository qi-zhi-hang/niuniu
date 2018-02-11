<?php
/**
 * Created by PhpStorm.
 * User: 13603
 * Date: 2018/2/1
 * Time: 9:18
 */

namespace app\game\controller;
use think\Db;
require_once "./wxpy/lib/WxPay.Data.php";
require_once "./wxpy/lib/WxPay.Api.php";
require_once "./wxpy/lib/WxPay.Notify.php";
class Notify extends \WxPayNotify
{


    public function Queryorder($transaction_id)
    {
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        //Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {

        //Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }

        $res = Db::name('niuorder')->where(array('order_sn'=>$data['out_trade_no'],'status'=>0))->find();
        if(!$res){
            $msg = '无此订单';
            return false;
        }else{
            \think\Session::set('user_id',$res['member_id']);
            Db::name('niuorder')->where(array('order_sn'=>$data['out_trade_no']))->update(array('status'=>1));
            //return true;
        }
        return true;
    }

    public function notifyorder()
    {
        $this->Handle(false);
        $data = $this->GetReturn_code();
        $msg = $this->GetReturn_msg();

       if($data == "SUCCESS" && $msg == 'OK')
       {
           $member_id = \think\Session::get('user_id');
           if($member_id){
               Db::name('member')->where(array('id'=>$member_id))->setInc('money',100);

           }
           echo "<xml> <return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml> ";
       }else{
           var_dump($msg);
       }

    }
}