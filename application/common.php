<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
	$type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = ip2long($ip);
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

//生成树形
function getdeepstr($int, $strp='∟') {
    if ($int == 0) {
        return '';
    } else {
        $str = $strp;
        for ($i = 0; $i < $int; $i++) {
            $str = '　　' . $str;
        }
        return $str;
    }
}

function getRegionById($id){
	$id = trim($id,',');
	if($id == ''){
		$id = '0';
	}
	$ret = array();
	$list = model('region') -> where(array('region_id' => array('in', $id))) -> select();
	foreach($list as $k => $v){
		$r = $v -> toArray();
		$ret[] = $r['region_name'];
	}
	return implode('　', $ret);
}

function getconfig($name){
	
	$ret = model('config') -> where(array('name' => $name)) -> find() -> toArray();
	return $ret['value'];
}
function getnickname($id){
    return model('member') -> where(array('id' => $id)) -> value('nickname');
}