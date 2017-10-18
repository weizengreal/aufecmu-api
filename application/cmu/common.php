<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/6
 * Time: 下午12:34
 */

/*
 * 计算显示在主界面上的时间
 * 最多表示到前天，剩下的直接显示为对应的日期
 * */
function getTime($time) {
    $nowTime = time();
    $differ=$nowTime-$time;
    if($differ >= 0 && $differ < 60) {
        //一分钟内
        return "刚刚";
    }
    else if($differ >= 60 && $differ < 3600) {
        //一小时内
        return (int)($differ/60)." 分钟前";
    }
    else if($differ >= 3600 && $differ < 86400) {
        //当天
        return (int)($differ/3600)." 小时前";
    }
    else if($differ >= 86400 && $differ < 172800) {
        //昨天
        return "昨天 ".date("G:i",$time);
    }
    else if($differ >= 172800 && $differ < 259200) {
        //前天
        return "前天 ".date("G:i",$time);
    }
    else {
        //大前天开始全部使用日期
        return date("m-d",$time);
    }
}

function getIPaddress () {
    $IPaddress='';
    if (isset($_SERVER)){
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $IPaddress = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")){
            $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $IPaddress = getenv("HTTP_CLIENT_IP");
        } else {
            $IPaddress = getenv("REMOTE_ADDR");
        }
    }
    return ipton($IPaddress);
}


function ipton($ip) {
    $ip_arr=explode('.',$ip);//分隔ip段
    $ipstr="";
    foreach ($ip_arr as $value)
    {
        $iphex=dechex($value);//将每段ip转换成16进制
        if(strlen($iphex)<2)//255的16进制表示是ff，所以每段ip的16进制长度不会超过2
        {
            $iphex='0'.$iphex;//如果转换后的16进制数长度小于2，在其前面加一个0
            //没有长度为2，且第一位是0的16进制表示，这是为了在将数字转换成ip时，好处理
        }
        $ipstr.=$iphex;//将四段IP的16进制数连接起来，得到一个16进制字符串，长度为8
    }
    return hexdec($ipstr);//将16进制字符串转换成10进制，得到ip的数字表示
}