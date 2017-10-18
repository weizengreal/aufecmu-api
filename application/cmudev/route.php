<?php
use think\Route;

//线上版本
//生产环境部分的路由


//获取信息接口
Route::rule("xyqdev","cmudev/index/index");
Route::rule("xyqdev/getAllData","cmudev/index/getAllData");
Route::rule("xyqdev/getDetail","cmudev/index/getDetail");
Route::rule("xyqdev/getComment","cmudev/index/getComment");
Route::rule("xyqdev/getTheme","cmudev/index/getTheme");

Route::rule("xyqdev/test","cmudev/index/test");

//POST数据接口
Route::rule("xyqdev/loginApplet","cmudev/index/loginApplet","POST");
Route::rule("xyqdev/createNote","cmudev/index/createNote","POST");
Route::rule("xyqdev/createComment","cmudev/index/createComment","POST");
Route::rule("xyqdev/zan","cmudev/index/zan","POST");
Route::rule("xyqdev/deleteNote","cmudev/index/hideNote","POST");






/*
 * 通过memcache缓存读取
 * */
Route::rule("memdev/getAllData","cmudev/index/_getAllData");



/*
 * 测试路由部分
 * */
//Route::rule("xyqdev/test","cmudev/index/test");


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