<?php
namespace app\common\model;

use think\Model;
use think\Db;
use think\Session;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/6
 * Time: 15:43
 */
class Room extends Model
{
    //在模型中获取当前房间的人数,方便控制器调用
    public function get_num()
    {
        $db = model('member');
        $room = $this->find();
        if (!$room) {
            $this->error = '房间不存在！';
            return false;
        }
        $room = $room->toArray();
        $num = $db->where(array('room_id' => $room['id']))->count();
        return $num;
    }

    /**
     * 获取房间中的所有会员
     * @return int|string
     */
    public function getmember($where = array())
    {
        $room = $this->where($where)->find();
        if (!$room) {
            $this->error = '房间不存在！';
            return false;
        }
        $room = $room->toArray();
        $map['room_id'] = $room['id'];
        $member = Db::name('member')->where(array('room_id' => $room['id']))->select();
        return $member;
    }

    public function gameinit($where = array(), $num = 10)
    {

        $room = $this->where($where)->find();
        if (!$room) {
            $this->error = '房间不存在！';
            return false;
        }
        $room = $room->toArray();
        if($room['gamestatus'] != 4){
            //游戏进行中，不能重置
            return false;
        }

        $lastwinnerid = (int)model('member') -> where(array('room_id' => $room['id'], 'typemultiple' => 10, 'gamestatus' => array('gt', 0))) -> order('pairet desc') -> value('id');
        $rule = unserialize($room['rule']);
        if($lastwinnerid > 0){

            $rule['lastwinnerid'] = $lastwinnerid;

        }
        $rule = serialize($rule);

        $this-> accounttemp($room['id']);
        $map['room_id'] = $room['id'];
        Db::name('member')->where(array('room_id' => $room['id']))->update(array('tanpai' => '','pai' => '', 'gamestatus' => 0));
        model('room')->where(array('id' => $room['id']))->update(array('taipaitime' => time(),'islock' => 0, 'gamestatus' => 0, 'rule' => $rule));
        if ($room['room_cards_num'] <= 0 && $room['playcount'] >= $num) {
            $this->error = '房卡耗完了';
            $this->account($room['id']);
            model('room')->where(array('id' => $room['id']))->update(array('playcount' => 0, 'gamestatus' => 0));
            return false;
        }

        if ($room['room_cards_num'] > 0 && $room['playcount'] >= $num) {
            model('room')->where(array('id' => $room['id']))->setDec('room_cards_num', 1);
            model('room')->where(array('id' => $room['id']))->update(array('playcount' => 1, 'gamestatus' => 0));
            //这里10局完了
            //$this->account($room['id']);
        }else{
            model('room')->where(array('id' => $room['id']))->setInc('playcount', 1);
        }

        return true;
    }


    /**
     * 创建房间
     * @param $memberid
     * @param $rule
     * @return $this|bool|int|mixed|string
     */
    public function roomcreate($memberid, $rule = array())
    {
        $memberdb = model('member');
        $cards = $memberdb->getcardnum(array('id' => $memberid));

        if ($cards == 0) {
            //没有房卡
            $this->error = '当前会员没有房卡。';
            return false;
        }
        //看看用户能不能开一个房间
        //用户要开的房间是什么类型呢，他要多少房卡
        $roomtype = explode(':', $rule['gamenum']);
        if ($roomtype[1] > $cards) {
            $this->error = '房卡不够，请重新选择！';
            return false;
        }

        $room = $this->where(array('member_id' => $memberid))-> order('id desc') ->find();
        //会员的房间存在了，不要再创建了
        if (true) {
            $data['member_id'] = $memberid;
            $data['open_time'] = time();
            $data['rule'] = serialize($rule);
            $data['room_cards_num'] = $roomtype[1] -1 ;
            $data['playcount'] = 1;

            //房间号重复没有关系，好看就行了，A开头
            $data['room_num'] = 'A' . rand(10000, 99999);
            $ret = $this->save($data);
            if ($ret) {
                //扣除会员房卡数量
                model('member')->where(array('id' => $memberid))->setDec('cards', $roomtype[1]);
                //成功后返回房间的ID，注意这不是房间号

                //产生一个文件锁，弥补游戏中没有事务的不足
                if(!is_dir(LOCK_FILE_PATH)){
                    mkdir(LOCK_FILE_PATH, 511, true);
                }
                if (!file_exists(LOCK_FILE_PATH.$this ->id)) {
                    $fp = fopen(LOCK_FILE_PATH.$this ->id, "w");
                    fclose($fp);
                }
                return $this ->id;
            } else {
                return false;
            }
        } else {
            $update['room_cards_num'] = $roomtype[1] + $room['room_cards_num'];
            $update['playcount'] = 1;
            $update['rule'] = serialize($rule);
            $update['open_time'] = time();
            $ret = $this->where(array('id' => $room['id']))->update($update);
            if ($ret !== false) {
                model('member')->where(array('id' => $memberid))->setDec('cards', $roomtype[1]);
                return $room['id'];
            } else {
                return false;
            }
        }
    }

    /**
     * 发现房间中有一个会员未准备就返回false
     * @param array $where
     * @return bool
     */
    public function getgamestatus($where = array())
    {
        $room = $this->where($where)->find();
        if (!$room) {
            $this->error = '房间不存在！';
            return false;
        }
        $room = $room->toArray();
        $map['room_id'] = $room['id'];
        $member = Db::name('member')->where(array('room_id' => $room['id']))->select();
        $status = 0;
        foreach ($member as $v) {
            if ($v['gamestatus'] >= 1) {
                $status++;
            }
        }
        if (($status > 1 && $room['starttime'] <= time()) || $status == count($member)) {
            return true;
        }
        return false;
    }

    /**
     * 设置庄家返回一个庄家的设定结果，前端可能要写动画，到时返回前端一个数组，其中包含抢庄会员的ID，最后一个ID是庄家，前端遍历时可以每次高亮一个会员头像，做成抽奖的样式，这要求数据在返回时打乱，其实就是生成一个打乱的ID集合，确定最后一个是庄家，返回结果时把其它ID的会员的banker改成0，这样庄家就确定下来了
     * @param $roomid
     * @return array
     */
    public function setbanker($roomid)
    {
        $bankerid = array();
        $id = array();
        $room = $this->where(array('id' => $roomid)) -> find();
        if(!$room){
            $this->error = '房间不存在';
        }

        $room = $room -> toArray();
        if($room['gamestatus'] != 2){
            //$ret[] = model('member') -> where(array('room_id' => $roomid, 'gamestatus' => 1, 'banker' => 1)) -> value('id');
            return false;
        }
        $bankermultiple = model('member') -> where(array('room_id' => $roomid, 'gamestatus' => 1,'banker' => 1)) -> max('multiple');
        $allmember = model('member') -> where(array('room_id' => $roomid, 'gamestatus' => 1)) -> select();
        $issetbanker = 0;
        if($allmember){
            foreach($allmember as $k => $v){
                $member = $v->toArray();
                if($member['banker'] == 1 && $bankermultiple == $member['multiple']){
                    //抢庄的会员

                    $bankerid[] = $member['id'];
                }else{
                    //不抢庄的会员
                    $id[] = $member['id'];
                }
                if($v['issetbanker'] == 1){
                    $issetbanker ++;
                }
            }
        }else{
            $this->error = '房间没有人';
            return false;
        }
        //不只有两个人
        //dump(count($bankerid));
        //截止时间未到，有人没有抢并且已经有人抢了，这时不生成庄家
        if(count($allmember) > ($issetbanker) && (int)$room['qiangtime'] - time() > 0){
            //有人抢
            $this->error = '抢庄中';
            return false;
        }

        if(count($bankerid) > 0){
            //有人抢庄，把抢庄的人ID打乱，最后一个是庄家
            shuffle($bankerid);
            $ret = $bankerid;
        }else{
            //没有人抢庄，把所有人ID打乱，最后一个是庄家
            shuffle($id);
            $ret = $id;
        }
        //取最后一个ID
        $lastid = $ret[(count($ret)-1)];
        //把其它不是庄家的banker改成0
        model('member') -> where(array('id' => array('neq', $lastid), 'room_id' => $roomid)) -> update(array('banker' => 0));
        model('member') -> where(array('id' => array('eq', $lastid), 'room_id' => $roomid)) -> update(array('banker' => 1));
        $time = time();

        $lastwinnerid = $lastid;
        $rule = unserialize($room['rule']);
        if($lastwinnerid > 0){
            $rule['lastwinnerid'] = $lastwinnerid;
        }
        $rule = serialize($rule);
        $this->where(array('id' => $room['id'])) -> update(array('rule' => $rule,'gamestatus' => 3,'qiangtime' => $time - 1,'taipaitime' => $time + 25,'xiazhutime' => $time + 10, 'setbanker' => serialize($ret)));
        //返回结果





        return $ret;
    }

    public function bankerexist($roomid)
    {
        return model('member')->where(array('room_id' => $roomid, 'banker' => 1))->select();
    }

    /**
     *会员输赢后进行 金额扣除
     */
    public function account($roomid)
    {
        //游戏的结算方法，十局之后从日志表中读取统计结果进行结算，这里需要一个日志表，用来记录每局游戏的结果，结算后清空
        $memberdb = model('member');
        $result = Db::name('moneydetailtemp')->where(array('room_id' => $roomid))->select();
        Db::name('moneydetailrank') -> where(array('room_id' => $roomid)) -> delete();
        foreach ($result as $k => $v) {
            //每一局都要改变玩家的金币数量
            $memberdb->where(array('id' => $v['member_id']))->setInc('money', $v['num']);
            $mymoney = $memberdb->where(array('id'=>$v['member_id']))->field('money')->find();
            if($mymoney['money'] < 0 ){
                $memberdb->where(array('id'=>$v['member_id']))->update(array('money'=>0));
            }
            unset($v['id']);
            //转存到moneydetail表
            Db::name('moneydetail')->insert($v);
            Db::name('moneydetailrank')->insert($v);
        }
        //删除临时记录，再开始玩十局要重新累计
        Db::name('moneydetailtemp')->where(array('room_id' => $roomid))->delete();
    }

    public function accounttemp($roomid){
        $roomdb = model('room');
        $memberdb = model('member');
        $moneydetailtempdb = model('moneydetailtemp');
        //从房间中查询得到当前游戏的规则内容，是一个序列化的数组，要反序列
        $rule = $roomdb -> where(array('id' => $roomid)) -> value('rule');
        $rule = unserialize($rule);
        //底分
        $score = $rule['score'];
        //获到庄家的信息
        $banker = $memberdb -> where(array('room_id' => $roomid, 'banker' => 1, 'gamestatus' => 2)) -> find();
        if($banker){
            $banker = $banker -> toArray();
        }
        //牌型倍数
        //a:4:{s:5:"score";s:1:"1";s:5:"types";s:1:"1";s:4:"rule";s:1:"1";s:7:"gamenum";s:4:"20:2";}
        $rulemultiple[1] = array(
            0 => 1,
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 1,
            6 => 1,
            7 => 1,
            8 => 2,
            9 => 2,
            10 => 3,
            11 => isset($rule['types'][1])?$rule['types'][1]:1,
            12 => isset($rule['types'][2])?$rule['types'][2]:1,
            13 => isset($rule['types'][3])?$rule['types'][3]:1,
        );
        $rulemultiple[2] = array(
            0 => 1,
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 1,
            6 => 1,
            7 => 2,
            8 => 2,
            9 => 3,
            10 => 4,
            11 => isset($rule['types'][1])?$rule['types'][1]:1,
            12 => isset($rule['types'][2])?$rule['types'][2]:1,
            13 => isset($rule['types'][3])?$rule['types'][3]:1,
        );
        $typemultiple = $rulemultiple[$rule['rule']];
        $member = $memberdb->where(array('room_id' => $roomid, 'banker' => 0, 'gamestatus' => 2))->select();

        $jinbi = array(array(),array());
        $win = array();
        foreach ($member as $k => $v) {

            $v = $v -> toArray();
            $data = array();
            $data['room_id'] = $v['room_id'];
            $data['time'] = time();

            if($v['pairet'] < $banker['pairet']){
                //庄赢
                $data['reason'] = '做庄赢了【'.$v['nickname'].'】';
                $data['member_id'] = $banker['id'];
                //赢了多少呢，闲家倍数*㽵家倍数*底分
                $data['num'] = $v['multiple'] * $banker['multiple'] * $score * $typemultiple[$banker['typemultiple']];
                $moneydetailtempdb -> insert($data);
//dump($v['multiple'] .'*'. $banker['multiple'] .'*'. $score .'*'. $typemultiple[$banker['typemultiple']]);
                if(!isset($win[$banker['id']])){
                    $win[$banker['id']] = 0;
                    $win[$banker['id']] += $data['num'];
                }else{
                    $win[$banker['id']] += $data['num'];
                }

                $data['member_id'] = $v['id'];
                $data['reason'] = '输给庄家【'.$banker['nickname'].'】';
                $data['num'] = $data['num']*-1;
                $moneydetailtempdb -> insert($data);
                if(!isset($win[$v['id']])){
                    $win[$v['id']] = 0;
                    $win[$v['id']] += $data['num'];
                }else{
                    $win[$v['id']] += $data['num'];
                }
                //组合庄家赢了的金币数据
                $jinbidata = array();
                $jinbidata[] = $v['id'];
                $jinbidata[] = $banker['id'];
                $jinbidata[] = abs($data['num']);
                $jinbi[0][] = $jinbidata;


            }else{
                //闲赢
                $data['reason'] = '赢了庄家【'.$banker['nickname'].'】';
                $data['member_id'] = $v['id'];
                //赢了多少呢，闲家倍数*㽵家倍数*底分
                $data['num'] = $v['multiple'] * $banker['multiple'] * $score * $typemultiple[$v['typemultiple']];
                $moneydetailtempdb -> insert($data);
                //dump($v['multiple'] .'*'. $banker['multiple'] .'*'. $score .'*'. $typemultiple[$banker['typemultiple']]);
                if(!isset($win[$v['id']])){
                    $win[$v['id']] = 0;
                    $win[$v['id']] += $data['num'];
                }else{
                    $win[$v['id']] += $data['num'];
                }

                $data['member_id'] = $banker['id'];
                $data['reason'] = '做庄输了【'.$v['nickname'].'】';
                $data['num'] = $data['num']*-1;
                $moneydetailtempdb -> insert($data);

                if(!isset($win[$banker['id']])){
                    $win[$banker['id']] = 0;
                    $win[$banker['id']] += $data['num'];
                }else{
                    $win[$banker['id']] += $data['num'];
                }


                //组合闲家赢了的金币数据
                $jinbidata = array();
                $jinbidata[] = $banker['id'];
                $jinbidata[] = $v['id'];
                $jinbidata[] = abs($data['num']);
                $jinbi[1][] = $jinbidata;

            }
        }
        $jinbi[2] = $win;
        //这里入库，建议把数据保存在room一个字段中，新建一个字段
        $save['jinbi'] = serialize($jinbi);
        $this->where(array('id' => $roomid)) -> update($save);


    }
//获取排名数据
    public function getrankinglist($room){
        //$moneydetailtempdb = model('moneydetailrank');
        $list = Db::name('moneydetailrank') -> alias('d') -> where(array('d.room_id' => $room)) -> group('member_id') ->field('member_id,sum(num) as money,m.nickname') -> join('__MEMBER__ m', 'm.id = d.member_id', 'left') -> order('money desc') -> select();
        return $list;
    }
    //获取分数数据
    public function getranking($room){
        $ret = array();
        $moneydetailtempdb = model('moneydetailtemp');
        $list = $moneydetailtempdb -> where(array('room_id' => $room)) -> group('member_id') ->field('member_id,sum(num) as money') ->select();
        foreach($list as $k => $v){
            $ret[$v['member_id']] = $v['money'];
        }
        return $ret;
    }
    //获取玩家的金币数量，列出来
    public function get_first($room)
    {
        $ret = array();
        $momeydetailtempdb = model('moneydetailtemp');
        $list = $momeydetailtempdb ->where(array('room_id' => $room)) -> group('member_id') ->field('member_id,sum(num), as money') ->select();
        foreach($list as $k =>$v){
            $ret[$v['member_id']] = $v['money'];
        }
        return $ret;
    }

}
