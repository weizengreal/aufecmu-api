<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/10
 * Time: 下午12:31
 */



echo a() && b();


function a () {
    echo "a";
    return false;
}


function b() {
    echo "a";
    return true;
}


