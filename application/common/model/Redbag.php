<?php
namespace app\common\model;
use think\Model;
use think\Session;
use think\Db;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 15:08
 */
class Redbag extends Model
{
    /**
     * 创建红包，分享房卡
     * @param $memberid
     * @param $num
     * @return $this|bool|int|mixed|string
     */
    public function create_bag($memberid ,$num)
    {
        if(intval($num) === 0){
            $this->error = '房卡数量必须大于0';
            return false;
        }
        $memberdb = model('member');
        $cards = $memberdb->where(array('id' => $memberid))->find();
        $cards = $cards->toArray();
        if($cards['cards'] > 0 && $cards['cards'] >= $num){
            //可以分享房卡
            $data['cards_num'] = $num;
            $data['create_time'] = time();
            $data['create_id'] = $memberid;
            $ret = $this->save($data);
            if($ret){
                $cardsnum['cards'] =  $memberdb -> where(array('id' => $memberid)) -> setDec('cards', $num);
                return $this -> id;
            }else{
                return false;
            }
        }else{
            $this->error ='您的房卡不足！';
            return false;
        }
    }
    /**
     *打开房卡红包
     *
     */
    public function open_bag($memberid , $id)
    {
        $this -> backredbag($id);
        //查询当前红包的状态
        $redbag = $this->where(array('id' => $id)) -> find();
        if($redbag['status'] == 1){
            $this->error = '红包已被领取';
            return false;
        }
        //24小时内未被领取，可以领取
        if($redbag['status'] == -1){
            $this->error = '红包已经过期';
            return false;
        }
        $data['receive_id'] = $memberid;
        $data['status'] = 1;
        $data['receive_time'] = time();
        $ret = $this -> where(array('id' => $id)) -> update($data);
        $memberdb = model('member');
        if($ret !== false){
            $ret = $memberdb -> where(array('id' => $memberid)) -> setInc('cards', $redbag['cards_num']);
            if($ret){
                return true;
            }else{
                $this->error = $memberdb -> getError();
                return false;
            }

        }
    }
    public function backredbag($id)
    {
        $redbag = $this->where(array('id' => $id)) -> find();
        //房卡未领取，超过24小时，房卡失效
        if($redbag){
            $redbag = $redbag -> toArray();
        }
        $time = strtotime($redbag['create_time']) + 24 *3600;
        if($redbag['status'] == 0 && time() > $time){
            $data['status'] = -1;//退回
            $result =  model('redbag')->where(array('id' => $id))->find();
            $cards = $result['card_num'];
           /* $member = model('member')->where(array('id' => $result['create_id']))->find();
            $cardsnum = $cards + $member['cards'];
            $ret = $member->update($cardsnum);*/
            $ret = model('member') -> where(array('id' => $result['create_id'])) -> setInc('cards', $cards);
            if($ret){
                return true;
            }else{
                return false;
            }
        }

    }
    public function baginfo($id)
    {
        $result = $this->alias('r') -> join('__MEMBER__ m','r.create_id=m.id','left') -> Field('r.*,m.nickname,m.photo') -> where(array('r.id' => $id)) -> find();
        if($result){
            return $result -> toArray();
        }else{
            return false;
        }
    }

}