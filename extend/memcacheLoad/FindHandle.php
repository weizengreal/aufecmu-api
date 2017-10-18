<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/6/16
 * Time: 下午4:55
 */
namespace memcacheLoad ;


/*
 * 策略模式
 * 实现一个算法簇将具
 * */

abstract class FindHandle {

    /*
     * 获取数据接口
     * */
    public abstract function handleData( $data , $show );



}
