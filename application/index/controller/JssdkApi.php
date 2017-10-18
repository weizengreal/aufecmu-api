<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/8
 * Time: 上午10:32
 */

namespace app\index\controller;

use wxLib\Jssdk;
use think\Request;

class JssdkApi {

    public function init() {
        die("meile");
//        $appid=config("axcAppid");
//        file_put_contents("/home/wwwroot/api.aufe.vip/public/jssdk/jsapi_ticket_$appid.json","");
//        file_put_contents("/home/wwwroot/api.aufe.vip/public/jssdk/access_token_$appid.json","");
//        file_put_contents("/home/wwwroot/api.aufe.vip/public/jssdk/api_ticket_$appid.json","");
    }

    // 安小财公众号获取accessToken
    public function getAccessTokenForAxc() {
        $jssdk = new Jssdk(config("axcAppid"),config("axcSecret"));
        return $jssdk->getAccessToken();
    }

    // 安小财公众号更新accessToken
    public function updateAccessTokenForAxc() {
        $jssdk = new Jssdk(config("axcAppid"),config("axcSecret"));
        return $jssdk->updateAccesstoken();
    }

    // 安小财公众号获取SignPackage
    public function getSignPackageForAxc() {
        $jssdk = new Jssdk(config("axcAppid"),config("axcSecret"));
        return json_encode($jssdk->getSignPackage(Request::instance()->post("url",null)));
    }

    // 安小财公众号获取accessToken
    public function getAccessTokenForZac() {
        $jssdk = new Jssdk(config("zacAppid"),config("zacSecret"));
        return $jssdk->getAccessToken();
    }

    // 安小财公众号更新accessToken
    public function updateAccessTokenForZac() {
        $jssdk = new Jssdk(config("zacAppid"),config("zacSecret"));
        return $jssdk->updateAccesstoken();
    }

    // 安小财公众号获取SignPackage
    public function getSignPackageForZac() {
        $jssdk = new Jssdk(config("zacAppid"),config("zacSecret"));
        return json_encode($jssdk->getSignPackage(Request::instance()->post("url",null)));
    }


}

