<?php
use think\Route;
//生产环境部分的路由

//获取信息接口
Route::rule("common/getQnToken","index/index/getQnToken");
Route::rule("common/getEvaQnToken","index/index/getEvaQnToken");

//获取安小财accessToken相关配置项
Route::rule("jssdk/axcToken","index/jssdkApi/getAccessTokenForAxc");
Route::rule("jssdk/upAxcToken","index/jssdkApi/updateAccessTokenForAxc");
Route::rule("jssdk/axcShare","index/jssdkApi/getSignPackageForAxc");

//获取在安财accessToken相关配置项
Route::rule("jssdk/zacToken","index/jssdkApi/getAccessTokenForZac");
Route::rule("jssdk/upZacToken","index/jssdkApi/updateAccessTokenForZac");
Route::rule("jssdk/zacShare","index/jssdkApi/getSignPackageForZac");

//初始化基本信息
Route::rule("init","index/jssdkApi/init");



/*
 * 测试路由部分
 * */


//return [
//    '__pattern__' => [
//        'name' => '\w+',
//    ],
//    '[hello]'     => [
//        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//        ':name' => ['index/hello', ['method' => 'post']],
//    ],
//
//];