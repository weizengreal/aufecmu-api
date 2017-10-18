<?php
use think\Route;

//线上版本
//生产环境部分的路由


//获取信息接口
Route::rule("xyq","cmu/index/index");
Route::rule("xyq/getAllData","cmu/index/getAllData");
Route::rule("xyq/getDetail","cmu/index/getDetail");
Route::rule("xyq/getComment","cmu/index/getComment");
Route::rule("xyq/getTheme","cmu/index/getTheme");

Route::rule("xyq/test","cmu/index/test");

//POST数据接口
Route::rule("xyq/loginApplet","cmu/index/loginApplet","POST");
Route::rule("xyq/createNote","cmu/index/createNote","POST");
Route::rule("xyq/createComment","cmu/index/createComment","POST");
Route::rule("xyq/zan","cmu/index/zan","POST");
Route::rule("xyq/deleteNote","cmu/index/hideNote","POST");


/*
 * 测试路由部分
 * */
//Route::rule("xyq/test","cmu/index/test");


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