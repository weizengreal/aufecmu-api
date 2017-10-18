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
/*
 * authcode 修复版本
 * 解决原版的authcode函数代码可能会生成+、/、&这样的字符，
 * 导致通过URL传值取回时被转义，导致无法解密的问题
 * */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

    if($operation == 'DECODE') {
        $string = str_replace('[a]','+',$string);
        $string = str_replace('[b]','&',$string);
        $string = str_replace('[c]','/',$string);
    }
    $ckey_length = 4;
    $key = md5($key ? $key : config("OWN_KEY"));
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {

            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        $ustr = $keyc.str_replace('=', '', base64_encode($result));
        $ustr = str_replace('+','[a]',$ustr);
        $ustr = str_replace('&','[b]',$ustr);
        $ustr = str_replace('/','[c]',$ustr);
        return $ustr;
    }
}


function getErrMsg($index) {
    $errMsgArr=[
        -1=>"inner error",
        1=>"ok",
        2=>"lose params!",
        3=>"accessToken 不合法",
        4=>"该用户不存在",
        5=>"源数据错误导致信息查询失败",
        6=>"非法访问",
        7=>"不存在的主题类型",
        8=>"数据已经到底了",
        9=>"ok",
        10=>"ok",
        2001=>"不允许评论自己的发言",
        3001=>"该项在未能在memcache中找到",
        3002=>"memcache已经到达限制数据量，请向数据库中读取更多数据",
        14=>"ok",
        15=>"ok",
        16=>"ok",
        17=>"ok",
        18=>"ok",
        19=>"ok",
    ];
    return [
        'errcode'=>$index,
        'info'=>$errMsgArr[$index]
    ];
}

function getApiMsg($index) {
    $apiMsgArr = [
        1=>"ok",
        2001=>"不允许评论自己的发言(Don't allow yourself to comment)",
        3001=>'请求接口错误(Request interface error)',
        3002=>'加密数据解析错误(Encrypted data parsing error)',
        4001=>'缺少参数(lose params!)',
        4002=>'参数不符合要求(Parameter does not meet the requirements)',
        4003=>'参数错误(Parameter error)',
        4004=>'内部错误(inner error)',
        4005=>'access_token不合法(access_token is illegal)',
        4006=>'非法访问(Illegal access)',


    ];
    return [
        'status'=>$index,
        'info'=>$apiMsgArr[$index]
    ];
}


function adminAuth($userid) {
    $authArr = [
        'oV6UzwaBqA0Eb-kKZeE0SJpJP6_c',
        'oV6UzwbnhiqLe_SYmnIZm9ykCSXM',
        'oV6UzwUPqi8QtQyqdQSUf7glij1o'
    ];
    return in_array($userid,$authArr);
}

