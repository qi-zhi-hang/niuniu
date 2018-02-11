<?php
/**
 * Created by PhpStorm.
 * User: 10510
 * Date: 2017/6/26
 * Time: 21:17
 * 定义游戏规则配置数据格式如下
 * 底分：score【1,3,5,10,20】
 * 规则、牌型倍数：rule【1,2】，types【1,2,3】
 * 房卡游戏局数：gamenum【10:1,20:2】
 * 固定上庄：openroom【0,100,300,500】
 */
class douniu
{

    /**
     * @var array|初始化纸牌数据
     */
    private $cards = array();

    /**
     * douniu constructor.
     * 构造函数
     * @param $cards  初始化纸牌数据
     */
    public function __construct($cards = array())
    {
        if (empty($cards)) {
            //产生一个长度为52的自然数组，代表52张牌
            $this->cards = range(1, 52);
            //将52张牌的顺序打乱，相当于洗牌
            shuffle($this->cards);
        } else {
            $this->cards = $cards;
        }

    }

    /**
     * 发牌 你你你
     */
    public function create()
    {
        $array = array();
        $cards = $this->cards;
        $i = 0;
        foreach ($cards as $k => $v) {
            if ($i < 5) {
                $array[] = $cards[$k];
                unset($cards[$k]);
            }

            $i++;
        }
        $this->cards = $cards;
        return $array;

    }

    /**
     * 传入纸牌编号返回当前纸牌的点数
     * @param $i 纸牌序号
     * @return float|int
     */
    public function getscore($i)
    {
        return ceil($i / 4) < 10 ? ceil($i / 4) : 10;
    }
    /**
     * 传入纸牌编号返回当前纸牌的点数
     * @param $i 纸牌序号
     * @return float|int
     */
    public function getscoreorigin($i)
    {
        return ceil($i / 4);
    }
    /**
     * 传入纸牌编号，返回当前纸牌的名称
     * @param $i 纸牌序号
     * @return string
     */
    public function getcardname($i)
    {
        $name = array('方块 <span style="color:#f00;">♦</span> ', '草花 ♣ ', '红桃 <span style="color:#f00;">♥</span> ', '黑桃 ♠ ');
        $num = array('A', 2, 3, 4, 5, 6, 7, 8, 9, 10, 'J', 'Q', 'K');
        $namekey = ($i % 4) > 0 ? ($i % 4 - 1) : 3;
        $numkey = ceil($i / 4) - 1;
        return $name[$namekey] . $num[$numkey];
    }

    /**
     * 从传入数组中任意取出三个元素进行组合，返回所有可能
     * @param $a 原数组
     * @param $m 取出组合个数
     * @return array 返回数组
     */
    public function combination($a, $m)
    {
        $r = array();
        $n = count($a);
        if ($m <= 0 || $m > $n) {
            return $r;
        }
        for ($i = 0; $i < $n; $i++) {
            $t = array($a[$i]);
            if ($m == 1) {
                $r[] = $t;
            } else {
                $b = array_slice($a, $i + 1);
                $c = $this->combination($b, $m - 1);
                foreach ($c as $v) {
                    $r[] = array_merge($t, $v);
                }
            }
        }
        return $r;
    }

    /**
     * 计算牛的组合 aakel
     * @param array $cards
     * @return array
     */
    public function niucount($cards = array())
    {
        $count = array();
        $arr = $this->combination($cards, 3);
        foreach ($arr as $k => $v) {
            $sum = $this->getscore($v[0]) + $this->getscore($v[1]) + $this->getscore($v[2]);
            if ($sum % 10 == 0) {
                $count[] = $v;
            }
        }
        return $count;
    }


    public function getniuname($cards = array()){
        $ret = $this->getniu($cards);
        if($ret === 0){
            return 10;
        }
        if($ret > 0){
            return $ret;
        }
        if($ret === false){
            return 0;
        }
    }

    public function getmax($cards){
        $ret = array();
        foreach($cards as $k => $v) {
            $namescore = (($v % 4) > 0 ? ($v % 4 - 1) : 3) + 1;
            $keyscore = $this->getscoreorigin($v);
            $ret[] = $namescore + $keyscore*$keyscore;
        }
        return max($ret);
    }

    public function ret($cards){
        //获取牛的类型
        $type = $this->getniuname($cards);
        if($type !== false){
            $type += 13;
            $type = $type*$type*$type*$type;
        }
        $ret = $type + $this -> getmax($cards);

        return $ret;
    }

    /**
     * bool false 是无牛   1-9牛几  0牛牛  10五花牛   11炸弹牛   12小牛牛
     * @param $cards 5张纸牌的数组
     * @return mixed
     */
    public function getniu($cards)
    {

        //是否五小
        $ismin = true;
        //是否五花
        $ismax = true;
        //是否炸弹
        $isbomb = false;
        $isbombcount = array();
        $totalscore = 0;
        foreach($cards as $k => $v){
            if($this->getscore($v) > 5){
                //五小不成立
                $ismin = false;
            }
            if($this->getscoreorigin($v) <= 10){
                //五花牛不成立
                $ismax = false;
            }
            $totalscore += $this->getscore($v);
            $isbombcount[] = (int)$this->getscoreorigin($v);
        }


        $isbombarr = array_count_values($isbombcount);
        if(max($isbombarr) >= 4){
            $isbomb = true;
        }
        if($ismin && $totalscore < 10){
            return 13;
        }
        if($isbomb){
            return 12;
        }
        if($ismax){
            return 11;
        }

        $return = array();
        $count = $this->niucount($cards);
        foreach ($count as $k => $v) {
            $ret = array_diff($cards, $v);
            $socre = 0;
            foreach($ret as $val){
                $socre += $this->getscore($val);
            }
            $return[] =  $socre%10;
        }
        if(count($return) > 0){
            return max($return);
        }
        return false;
    }
}
