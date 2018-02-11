<?php
namespace app\common\model;

use think\Model;
use think\Db;
class Member extends Model
{
    public function checklogin($data)
    {
        $map['Email'] = $data['Email'];
        if (empty($map['Email'])) {
            $this->error = '账号不能为空';
            return false;
        }
        $result = $this->where($map)->find();
        //dump($result);
        //dump($data);
        if ($result) {
            $result = $result->toArray();
            if ($result['status'] == 0) {
                $this->error = '用户已禁用';
                return false;
            }
            if ($result['password'] != $data['password']) {
                $this->error = '密码错误请重新输入';
                return false;
            }
            //更新用户登录信息，记录最后登录IP和最后登录时间
            $update['login_ip'] = get_client_ip();
            $update['login_time'] = time();
            $this->save($update, array('id' => $result['id']));
            return $result;
        }
    }

    /**
     * 用户进入房间
     * @param $room_id
     * @return $this
     */
    public function comein($room_id, $where = array(), $num = 6)
    {
        $islock = model('room') -> where(array('id' => $room_id))->value('islock');
        $count = $this -> where(array('room_id' => $room_id)) -> count();
        if($count >= $num){
            $this->error = '房间人数已经满了';
            return false;
        }
        if($islock == 1){
            $this->error = '房间锁住了，等一会再来吧';
            return false;
        }
        return $this->where($where)->update(array('online' => 1,'room_id' => $room_id));
    }

    /**
     * 会员退出房间
     * @return $this
     */
    public function comeout($where = array())
    {
        //如果房间里没有人，就把锁打开

        $member = $this->where($where) -> find();
        if($member){
            $member = $member -> toArray();
            $m = $this -> where(array('id' => array('neq', $member['id']),'online' => 1,'room_id' => $member['room_id'])) -> find();
            if(!$m){
                model('room') -> where(array('id' => $member['room_id'])) -> update(array('islock' => 0, 'gamestatus' => 0));
                $this->where(array('room_id' => $member['room_id']))->update(array('room_id' => 0, 'gamestatus' => 0));
            }

        }
        return $this->where($where)->update(array('online' => 0,'lastcomeouttime' => time()));
    }

    /**
     * 获取会员的房卡数量
     */
    public function getcardnum($where = array())
    {
        return (int)$this->where($where)->value('cards');
    }

    /**
     * 获取一个会员在同一房间中的其它会员
     * @param $memberid
     */
    public function getothermember($memberid){
        $db = Db::name('member');
        //查询一个会员在房间中（如果他不在房间中，返回false）
        $member = $db -> where(array('id' => $memberid, 'room_id' => array('gt', 0))) -> find();
        if(!$member){
            $this->error = '会员不在房间中';
            return false;
        }
        $ret = $db -> where(array('id' => array('neq', $memberid), 'room_id' => $member['room_id']))->select();



        return $ret;
    }

    /**
     * 已经准备好的会员
     * @param $where
     * @return $this
     */
    public function gameready($where){
        return $this->where($where) -> update(array('gamestatus' => 1));
    }
    /**
     * 已经摊牌的会员
     * @param $where
     * @return $this
     */
    public function gameshowall($where){

        return $this->where($where) -> update(array('gamestatus' => 2));
    }
    /**
     * 玩家下注
     */
    public function settimes($memeberid , $multiple)
    {
        return $this->where(array('id' =>$memeberid ,'issetmultiple' => 0, 'banker' => array('neq', 1))) ->update(array('multiple' => $multiple));
    }
}
