<?php
/**
 * 九人牛
 * Created by PhpStorm.
 * User: wujunyuan
 * Date: 2017/7/3
 * Time: 13:26
 */

namespace app\game\controller;

use think\Controller;
use think\Loader;
use think\Db;
class Douniuplay extends Common
{
    private $gamelockfile;
    public function __construct()
    {
        parent::__construct();
        //文件锁路径
        define("LOCK_FILE_PATH", ROOT_PATH."tmp/lock/");

        $this->workermanurl = 'http://127.0.0.1:2121';
        $this->workermandata['type'] = 'publish';
        $this->workermandata['content'] = '';
        $this->workermandata['to'] = 0;
        //加载斗牛类
        Loader::import('extend.Game.douniu');
        //创建一个斗牛实例
        $this->douniu = new \douniu(array());
    }

    /**
     * 游戏中使用的文件锁
     * @param $roomid
     * @return bool
     */
    private function gamelock($roomid){
        //锁住不让操作，保证原子性
        $this -> gamelockfile = fopen(LOCK_FILE_PATH.$roomid, "r");
        if (!$this -> gamelockfile) {
            $this->error('锁住了');
            return false;
        }
        flock($this -> gamelockfile, LOCK_EX);
    }

    /**
     * 游戏中使用的文件锁,解锁
     * @param $roomid
     * @return bool
     */
    private function gameunlock($roomid){
        //锁住不让操作，保证原子性
        flock($this -> gamelockfile, LOCK_UN);
        fclose($this -> gamelockfile);
    }


    /**
     * 创建房间
     * 底分：score【1,3,5,10,20】
     * 规则、牌型倍数：rule【1,2】，types【1,2,3】
     * 房卡游戏局数：gamenum【10:1,20:2】
     * 固定上庄：openroom【0,100,300,500】
     */
    public function roomcreate()
    {
        $rule['score'] = input('post.score');
        $rule['types'] = isset($_POST['types']) ? $_POST['types'] : array();
        $rule['rule'] = input('post.rule');
        $rule['gamenum'] = input('post.gamenum');
        $rule['gametype'] = input('post.gametype');
        if (input('post.openroom')) {
            $rule['openroom'] = input('post.openroom');
        }

        $roomdb = model('room');
        $ret = $roomdb->roomcreate($this->memberinfo['id'], $rule);

        if ($ret) {
            model('member')->comein($ret, array('id' => $this->memberinfo['id']));
            //exit(url('index', array('room_id' => $ret)));
            $this->success('创建成功', url('index', array('room_id' => $ret)));
        } else {
            $this->error($roomdb->getError());
        }
    }

    /**
     * @param $url
     * @param string $data
     * @param string $method
     * @param string $cookieFile
     * @param string $headers
     * @param int $connectTimeout
     * @param int $readTimeout
     * @return mixed
     */
    private function curlRequest($url, $data = '', $method = 'POST', $cookieFile = '', $headers = '', $connectTimeout = 30, $readTimeout = 30)
    {
        $method = strtoupper($method);

        $option = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $readTimeout
        );

        if ($data && strtolower($method) == 'post') {
            $option[CURLOPT_POSTFIELDS] = $data;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $option);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect: '));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function sendmsg()
    {
        $memberid = $this->memberinfo['id'];
        $roommember = model('room')->getmember(array('id' => $this->memberinfo['room_id']));
        foreach ($roommember as $v) {
            if ($memberid != $v['id']) {
                //发给除了当前会员之外的房间中所有人
                $this->workermandata['to'] = $v['id'];
                $data['info'] = input('post.data');
                $data['from'] = $memberid;
                $data['type'] = 1;
                $data['msgid'] = input('post.id');
                $this->workermandata['content'] = json_encode($data);
                echo $this->curlRequest($this->workermanurl, $this->workermandata);
            }
        }
    }


    private function workermansend($to, $data)
    {
        $this->workermandata['to'] = $to;
        $this->workermandata['content'] = $data;
        return $this->curlRequest($this->workermanurl, $this->workermandata);
    }


    /**
     * 显示游戏界面
     * @return mixed
     */
    public function index()
    {
        //进入房间的ID
        $room_id = input('room_id');
        if ($room_id > 0) {
            $db = model('member');
            $ret = $db->comein($room_id, array('id' => $this->memberinfo['id']),9);
            if ($ret === false && $this -> memberinfo['room_id'] != $room_id) {
                $this->error($db->getError());
            }
            //$this->memberinfo['room_id'] = $room_id;
            $room = model('room')->where(array('id' => $room_id))->find();
            if (!$room) {
                $this->error('房间不存在啊！！！');
            }
            $room = $room->toArray();
            $this->assign('gamerule', unserialize($room['rule']));
            $this->assign('room', $room);
        } else {
            $this->error('迷路了，找不到房间！！！');
        }
        if ($room['playcount'] == 0) {
            if (input('room_id')) {
                $roomid = input('room_id');
            } else {
                $roomid = $this->memberinfo['room_id'];
            }
            $list = model('room')->getrankinglist($roomid);
            $this->assign('list', $list);
            $this->assign('room', $room);
            return $this->fetch('result:index');
        }
        $this->assign('rand', time());
        return $this->fetch();
    }

    /**
     *进入房间
     */
    public function comein()
    {

        $db = model('member');
        $room_id = input('room_id');
        if ($room_id) {
            $ret = $db->comein($room_id, array('id' => $this->memberinfo['id']),9);
            $this->allmember();
            if ($ret) {
                $this->success('成功进入房间');
            } else {
                $this->error($db->getError());
            }
        } else {
            $this->error('迷路了，找不到房间！！！');
        }

    }

    public function comeout()
    {
        //进入房间的ID
        $memberid = input('memberid') ? input('memberid') : $this->memberinfo['id'];

        $db = model('member');
        $ret = $db->comeout(array('id' => $memberid));
        //$this->allmember();
        if ($ret) {
            $this->success('有人断线');
        } else {
            $this->error('错误');
        }

    }

    /**
     * 通知一个会员更新他自己的玩家界面
     */
    public function allmember()
    {

        $db = model('member');

        //不知道什么原因会使得会员的牌是空的，然后状态又是摊牌状态，这样会出错，所以把状态改成0
        $db -> where(array('pai' => '', 'gamestatus' => 2)) -> update(array('gamestatus' => 0));

        //会员进入房间时通知所有人更新玩家
        $allmember = model('room')->getmember(array('id' => $this->memberinfo['room_id']));

        $room = model('room')->where(array('id' => $this->memberinfo['room_id']))->find();

        if ($room) {
            $room = $room->toArray();
            $room['rule'] = unserialize($room['rule']);

            $room['taipaitime'] = (int)$room['taipaitime'] - time();
            $room['starttime'] = (int)$room['starttime'] - time();
            $room['qiangtime'] = (int)$room['qiangtime'] - time();
            $room['xiazhutime'] = (int)$room['xiazhutime'] - time();
            if ($room['taipaitime'] < 0) {
                $room['taipaitime'] = 0;
            }
            if ($room['starttime'] < 0) {
                $room['starttime'] = 0;
            }
            if ($room['qiangtime'] < 0) {
                $room['qiangtime'] = 0;
            }
            if ($room['xiazhutime'] < 0) {
                $room['xiazhutime'] = 0;
            }

        }


        if (!$allmember) {
            return;
        }

        $ranking = model('room')->getranking($room['id']);


        //通知所有会员更新界面
        foreach ($allmember as $v) {

            $ret = $db->getothermember($v['id']);
            foreach ($ret as $key => $val) {
                if (isset($ranking[$val['id']])) {
                    $ret[$key]['money'] = $ranking[$val['id']];
                } else {
                    $ret[$key]['money'] = 0;
                }
                if ($val['gamestatus'] == 2) {
                    $ret[$key]['pai'] = unserialize($val['pai']);
                    //if($ret[$key]['pai']){
                    $ret[$key]['info'] = $this->douniu->getniuname($ret[$key]['pai']);
                    //}

                } else {
                    $ret[$key]['pai'] = array(0, 0, 0, 0, 0);
                    $ret[$key]['info'] = '未知';
                }
            }
            $start = model('room')->getgamestatus(array('id' => $v['room_id']));
            $return['start'] = $start;
            $return['data'] = $ret;
            $return['room'] = $room;
            if (isset($ranking[$v['id']])) {
                $return['money'] = $ranking[$v['id']];
            } else {
                $return['money'] = 0;
            }

            $return['issetbanker'] = $v['issetbanker'];
            $return['multiple'] = $v['multiple'];
            $return['banker'] = unserialize($room['setbanker']);
            $return['isbanker'] = $v['banker'];
            $return['issetmultiple'] = $v['issetmultiple'];
            $return['playcount'] = $room['playcount'] - 1;
            $return['type'] = 4;
            $return['gamestatus'] = $v['gamestatus'];
            //如果会员摊牌状态，通知前端更新
            if ($return['gamestatus'] == 2) {
                $return['pai'] = unserialize($v['pai']);
                //if($return['pai']){
                $return['info'] = $this->douniu->getniuname($return['pai']);
                //}

            } elseif ($return['gamestatus'] == 1) {
                $return['pai'] = unserialize($v['tanpai']);
                //$return['pai'] = array($return['pai'][0], $return['pai'][1], $return['pai'][2], 0, 0);
            } else {
                $return['pai'] = array();

            }
            $this->workermansend($v['id'], json_encode($return));
        }
    }

    /**
     * 闲家下注
     */
    public function setmultiple()
    {
        $room = model('room')->where(array('id' => $this->memberinfo['room_id']))->find();
        if ($room) {
            $room = $room->toArray();


        }
        model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('setbanker' => ''));
        $multiple = intval(input('multiple'));
        model('member')->settimes($this->memberinfo['id'], $multiple);
        model('member')->where(array('id' => $this->memberinfo['id']))->update(array('issetmultiple' => 1));

        if(time() >= $room['xiazhutime']){
            model('member')->where(array('room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('neq', 0), 'banker' => 0, 'issetmultiple' => 0))->update(array('issetmultiple' => 1));
        }

        //所有闲家都下注了，就直接开牌
        $unmultiple = (int)model('member')->where(array('room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('neq', 0), 'banker' => 0, 'issetmultiple' => 0))->count();
        if ($unmultiple == 0) {
            model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('taipaitime' => time() + 15, 'gamestatus' => 4));

        }
        $this->allmember();
    }

    /**
     * 设置庄家
     */
    public function setbanker()
    {
        $multiple = intval(input('multiple'));
        if ($multiple > 0) {
            //model('member')->settimes($this->memberinfo['id'], $multiple);
            model('member')->where(array('id' => $this->memberinfo['id'], 'issetbanker' => 0))->update(array('multiple' => $multiple));
            model('member')->where(array('id' => $this->memberinfo['id'], 'gamestatus' => 1))->update(array('banker' => 1));
        }
        model('member')->where(array('id' => $this->memberinfo['id']))->update(array('issetbanker' => 1));
        model('room')->setbanker($this->memberinfo['room_id']);
        $this->allmember();
    }


    public function gamestart(){
        $gameinit = 0;
        $map = array('room_id' => $this->memberinfo['room_id']);
        $allmember = Db::name('member') -> where($map) -> select();
        foreach ($allmember as $v) {
            if ($v['gamestatus'] == 1) {
                //发现有人未准备，游戏不开始
                $gameinit++;
            }
        }
        $starttime = (int)model('room')->where(array('id' => $this->memberinfo['room_id']))->value('starttime');
        //人齐了，或者人不齐时间到了，两者其一满足就发牌，开始游戏
        if (($gameinit > 1 && $starttime <= time()) || ($gameinit == count($allmember) && $gameinit > 1)) {
            model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('starttime' => time(), 'gamestatus' => 1));
            //这里发牌
            $this->init();
        }
    }

    public function gameready()
    {

        $islock = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('islock');

        if ($islock == 1 && $this->memberinfo['gamestatus'] < 1) {
            $this->error('游戏进行中，不允许加入');
        }
        if ($this->memberinfo['room_id'] == 0) {
            //都没有进房间，开始什么呀，有毛病
            $this->error('都还没有进房间呢');
        }
        $ret = true;
        if(input('gameready') == 1){
            $ret = model('member')->gameready(array('gamestatus' => 0, 'id' => $this->memberinfo['id']));
        }

        $gameinit = 0;
        //所有准备好的人数
        $roomdb = model('room');
        $map = array('room_id' => $this->memberinfo['room_id']);
        $allmember = Db::name('member') -> where($map) -> select();
        foreach ($allmember as $v) {
            if ($v['gamestatus'] == 1) {
                //发现有人未准备，游戏不开始
                $gameinit++;
            }
        }
        if ($ret) {
            //两个准备就开始倒计时
            if ($gameinit == 2) {
                //两个人参与时有两种情况
                if ($gameinit == count($allmember)) {
                    //房间里就两个人，马上就开始了
                    model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('starttime' => time(), 'gamestatus' => 1));
                } else {
                    //房间里还有其他人，超过两个了，再等5秒
                    model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('starttime' => time() + 5, 'gamestatus' => 1));
                }

            }
        }

        //游戏可以开始了，通知房间中所有会员
        $starttime = (int)model('room')->where(array('id' => $this->memberinfo['room_id']))->value('starttime');
        //人齐了，或者人不齐时间到了，两者其一满足就发牌，开始游戏
        if (($gameinit > 1 && $starttime <= time()) || ($gameinit == count($allmember) && $gameinit > 1)) {
            //这里发牌,锁住
            $this->gamelock($this->memberinfo['room_id']);
            $this->init();
            $this->gameunlock($this->memberinfo['room_id']);
        }
        if ($ret) {

            $this->allmember();
            $this->success('准备好了');
        } else {
            $this->error(model('member')->getError());
        }
    }

    /**
     * 这里要引入斗牛类了
     * 开始游戏，洗牌，发牌生成N副牌
     * 这里是把数据直接传回前端的
     */
    private function init()
    {

        model('member')->where(array('room_id' => $this->memberinfo['room_id']))->update(array('issetbanker' => 0, 'issetmultiple' => 0, 'banker' => 0, 'multiple' => 1));
        //查询房间中所有会员， 这个动作是最后一个准备游戏的会员触发的
        $allmember = model('room')->getmember(array('id' => $this->memberinfo['room_id']));
        //遍历所有会员，每人发一副牌，算好牌型，然后把数据存到数据库中的member表的pai字段
        $memberdb = model('member');
        $playcount = (int)model('paihistory')->where(array('room_id' => $this->memberinfo['room_id']))->max('playcount') + 1;
        $playcountroom = (int)model('room')->where(array('id' => $this->memberinfo['room_id']))->max('playcount');
        $paiarr = array();
        $pairetarr = array();
        $memberrate = 0;
        foreach ($allmember as $v) {
            if ($v['gamestatus'] == 1 && $v['pai'] == '') {
                if($v['ratearr'] != ''){
                    //是否要得胜，当后台设置得胜率时使用
                    $ratearr = unserialize($v['ratearr']);
                    $rate = $ratearr[$playcountroom - 1];
                }else{
                    $rate = 0;
                }
                if($rate == 1){
                    $memberrate = $v['id'];
                }
                $paidata = array();
                $pai = $this->douniu->create();
                $data['pai'] = serialize($pai);
                $data['typemultiple'] = $this->douniu->getniuname($pai);
                $data['tanpai'] = serialize(array(0, 0, 0, 0, 0));
                $map['id'] = $v['id'];

                //写入牌的大小
                $data['pairet'] = $this->douniu->ret($pai);
                $history['pai'] = serialize($pai);
                $history['member_id'] = $this->memberinfo['id'];
                $history['room_id'] = $this->memberinfo['room_id'];
                $history['pairet'] = $this->douniu->ret($pai);
                $history['create_time'] = time();
                $history['playcount'] = $playcount;
                $paidata['map'] = $map;
                $paidata['data'] = $data;
                $paidata['history'] = $history;
                $paidata['pairet'] = $data['pairet'];

                $pairetarr[$v['id']] = $data['pairet'];

                $paiarr[$v['id']] = $paidata;


            }

        }
        arsort($pairetarr);

        if($memberrate > 0){
            //作弊
            $memberratepai = $paiarr[$memberrate]['data'];
            $paiarr[$memberrate]['data'] = $paiarr[key($pairetarr)]['data'];
            $paiarr[key($pairetarr)]['data'] = $memberratepai;
            //dump($paiarr);
            //dump($pairetarr);
        }
        foreach($paiarr as $k => $v){
            dump($v['map']['id'].'---'.$v['data']['pairet']);
            model('paihistory')->insert($v['history']);
            $memberdb->where($v['map'])->update($v['data']);
        }

        //摊牌时间
        $time = time();
        model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('islock' => 1, 'qiangtime' => $time + 15, 'taipaitime' => $time + 30, 'gamestatus' => 2));

        $this->tombi();
        $this->mingup();
        $this->freeup();
        $this->fixedup();
        $this->niuup();
        $this->allmember();
    }

    /**
     * 摊牌
     **/
    public function showall()
    {
        $roomdb = model('room');
        $roomgamestatus = $roomdb->where(array('id' => $this->memberinfo['room_id']))->value('gamestatus');
        if ($roomgamestatus == 0) {
            $this->error('游戏还没有开始');
        }
        if ($this->memberinfo['room_id'] == 0) {
            //都没有进房间，开始什么呀，有毛病
            $this->error('都还没有进房间呢');
        }
        $ret = model('member')->gameshowall(array('gamestatus' => 1, 'id' => $this->memberinfo['id']));
        $gameshowall = true;
        //所有准备好的人数

        $map = array('room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('neq', 0));
        $allmember = model('member')->where($map)->select();
        foreach ($allmember as $v) {
            $v = $v->toArray();
            if ($v['gamestatus'] != 2) {
                //发现有人未摊牌
                $gameshowall = false;
            }
        }
        if ($gameshowall) {
            model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('taipaitime' => time()));
            model('member')->gameshowall(array('gamestatus' => 1, 'room_id' => $this->memberinfo['room_id']));
        }
        $taipaitime = (int)model('room')->where(array('id' => $this->memberinfo['room_id']))->value('taipaitime');
        if ($taipaitime - time() <= 0) {

            model('member')->gameshowall(array('gamestatus' => 1, 'room_id' => $this->memberinfo['room_id']));
            $gameshowall = true;
        }
        //dump($gameshowall);
        $this->allmember();
        //游戏可以开始了，通知房间中所有会员
        if ($gameshowall) {

            //游戏结束
            $this->theend();
        }

        if ($ret) {
            $this->success('处理正确');
        } else {
            $this->error(model('member')->getError());
        }
    }

    /**
     * 一局结束，这里要重新来一局
     */
    public function theend()
    {
        //通知前端显示再来一局的准备按钮,这里要计算游戏结果
        //计算出游戏结果后，初始化，牌的数据和牌型全改为原始状态
        //查询房间中所有会员， 这个动作是最后一个准备游戏的会员触发的
        //通知前端显示排名
        $room = model('room')->where(array('id' => $this->memberinfo['room_id']))->find();
        if ($room) {
            $room = $room->toArray();
        }


        $rule = unserialize($room['rule']);
        $this->gamelock($this->memberinfo['room_id']);
        model('room')->gameinit(array('id' => $this->memberinfo['room_id']),12);
        $this->gameunlock($this->memberinfo['room_id']);
        $gamenum = explode(':', $rule['gamenum']);


        $this->allmember();
        /*通知前端显示金币动画*/
        $allmember = model('room')->getmember(array('id' => $this->memberinfo['room_id']));
        if ($allmember) {
            $rank['end'] = 0;
            if (($room['playcount'] == $gamenum[0] && $room['room_cards_num'] == 0) || $room['playcount'] == 0) {
                $rank['end'] = 1;
            }
            $jinbi = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('jinbi');
            foreach ($allmember as $k => $v) {
                $rank['data'] = unserialize($jinbi);
                $rank['type'] = 999;
                $this->workermansend($v['id'], json_encode($rank));
            }
        }

        /*通知金币动画结束*/
    }


    private function setshownum($num, $room_id)
    {
        //$num = 4;
        $allmember = model('room')->getmember(array('id' => $room_id));
        foreach ($allmember as $k => $v) {
            $pai = unserialize($v['pai']);
            $ret = array(0, 0, 0, 0, 0);
            for ($i = 0; $i < $num; $i++) {
                $ret[$i] = $pai[$i];
            }
            model('member')->where(array('id' => $v['id']))->update(array('tanpai' => serialize($ret)));
        }
    }


    //牛牛上庄，第一局抢庄，玩家牌最大的下局是庄家
    public function niuup()
    {
        $rule = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('rule');
        $rule = unserialize($rule);

        if ($rule['gametype'] == 1) {
            $this->setshownum(3, $this->memberinfo['room_id']);
        }


        //规则为1是牛牛上庄，如果lastwinnerid同时存在，那么lastwinnerid就是上庄的会员ID
        if ($rule['gametype'] == 1 && isset($rule['lastwinnerid'])) {
            $member = model('member')->where(array('id' => $rule['lastwinnerid'], 'room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('gt', 0)))->find();

            if ($member) {
                $time = time();
                $update['taipaitime'] = $time + 30;
                $update['qiangtime'] = $time;
                $update['xiazhutime'] = $time + 15;
                $update['starttime'] = $time;
                //跳过抢庄流程，直接设置游戏为正在下注
                $update['gamestatus'] = 3;
                model('room')->where(array('id' => $this->memberinfo['room_id']))->update($update);
                model('member')->where(array('room_id' => $this->memberinfo['room_id'], 'banker' => 0, 'gamestatus' => array('gt', 0)))->update(array('issetbanker' => 1));
                model('member')->where(array('id' => $rule['lastwinnerid']))->update(array('banker' => 1));

            }

        }
    }

    //固定庄家：房主为庄家，退出后，随机选择一位固定
    public function fixedup()
    {
        //$rule = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('rule');
        $room = model('room')->where(array('id' => $this->memberinfo['room_id']))->find();
        if ($room) {
            $room = $room->toArray();
        }
        $rule = unserialize($room['rule']);
        if ($rule['gametype'] == 2) {
            $this->setshownum(3, $this->memberinfo['room_id']);


            //房间中存在固定庄家
            if ($room['fixedid'] > 0) {
                //设置的最后一位庄家在不在，在的话可以设置他为庄家

                $banker = model('member')->where(array('id' => $room['fixedid'], 'gamestatus' => array('gt', 0), 'room_id' => $this->memberinfo['room_id']))->find();
                if ($banker) {
                    //他参与了游戏
                    $bankermemberid = $room['fixedid'];
                }else {
                    model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('qiangtime' => time()));
                    $ret = model('room')->setbanker($this->memberinfo['room_id']);
                    $bankermemberid = $ret[(count($ret) - 1)];
                }
            }else {
                model('room')->where(array('id' => $this->memberinfo['room_id']))->update(array('qiangtime' => time()));
                $ret = model('room')->setbanker($this->memberinfo['room_id']);
                $bankermemberid = $ret[(count($ret) - 1)];
            }

            $banker = model('member')->where(array('id' => $room['member_id'], 'gamestatus' => array('gt', 0), 'room_id' => $this->memberinfo['room_id']))->find();
            //房主存在房间中并且参与游戏
            if ($banker) {
                $bankermemberid = $banker['id'];
            }
            $time = time();
            $update['taipaitime'] = $time + 25;
            $update['qiangtime'] = $time;
            $update['xiazhutime'] = $time + 10;
            $update['starttime'] = $time;
            $update['gamestatus'] = 3;
            $update['fixedid'] = $bankermemberid;
            model('room')->where(array('id' => $this->memberinfo['room_id']))->update($update);
            //房间牌最大的id

            model('member')->where(array('id' => $bankermemberid))->update(array('banker' => 1));
            model('member')->where(array('room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('gt', 0)))->update(array('issetbanker' => 1));


        }
    }

    //自由抢庄：玩家自由抢庄，三张牌显示，剩余两张不显示
    public function freeup()
    {
        $rule = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('rule');
        $rule = unserialize($rule);
        if ($rule['gametype'] == 3) {
            $this->setshownum(3, $this->memberinfo['room_id']);
        }
    }

    /**
     *明牌抢庄：玩家自由抢庄，四张牌显示，剩余一张不显示
     */
    public function mingup()
    {
        $rule = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('rule');
        $rule = unserialize($rule);
        if ($rule['gametype'] == 4) {
            $this->setshownum(4, $this->memberinfo['room_id']);
        }
    }

    /**
     *通比牛牛：不抢庄，不下注，直接比牌的大小
     */
    public function tombi()
    {

        $rule = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('rule');
        $rule = unserialize($rule);
        if ($rule['gametype'] == 5) {
            $this->setshownum(3, $this->memberinfo['room_id']);
            $time = time();
            $update['taipaitime'] = $time + 15;
            $update['qiangtime'] = $time;
            $update['xiazhutime'] = $time;
            $update['starttime'] = $time;
            $update['gamestatus'] = 4;
            model('room')->where(array('id' => $this->memberinfo['room_id']))->update($update);
            //房间牌最大的id
            $bankermemberid = model('member')->where(array('room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('gt', 0)))->order('pairet desc')->value('id');
            model('member')->where(array('id' => $bankermemberid))->update(array('banker' => 1));
            model('member')->where(array('room_id' => $this->memberinfo['room_id'], 'gamestatus' => array('gt', 0)))->update(array('issetmultiple' => 1, 'issetbanker' => 1));
        }

    }

    public function showone()
    {
        $gamestatus = model('room')->where(array('id' => $this->memberinfo['room_id']))->value('gamestatus');
        if ($gamestatus < 3) {
            $this->error('目前不能翻牌！');
        }

        $key = input('key');
        $map['id'] = $this->memberinfo['id'];
        $member = model('member')->where($map)->find();

        if ($member) {
            $member->toArray();
        } else {
            $this->error('会员不存在');
        }
        if ($member['banker'] == 0 && $member['issetmultiple'] != 1) {
            $this->error('目前不能翻牌！');
        }
        $pai = unserialize($member['pai']);
        $tanpai = unserialize($member['tanpai']);
        $tanpai[$key] = $pai[$key];
        $data['tanpai'] = serialize($tanpai);
        $ret = model('member')->where($map)->update($data);
        if ($ret) {
            $return['code'] = 1;
            $return['msg'] = $tanpai[$key];
            echo json_encode($return);
        } else {
            $this->error('翻牌失败' . model('member')->getError());
        }

        $this->allmember();
    }
}
