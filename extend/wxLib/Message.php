<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/17
 * Time: 下午3:55
 */
namespace wxLib;

class Message {
    private $accessToken ;

    public function __construct() {
        $this->accessToken=file_get_contents("http://api.aufe.vip/jssdk/zacToken");
    }

    public function upAccessToken() {
        $this->accessToken=file_get_contents("http://api.aufe.vip/jssdk/upZacToken");
    }

    public function sendMessage($openid,$url,$first,$personName,$date,$detailCot) {
        $requestUrl="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->accessToken}";
        $createCardPostJson = '{
           "touser":"'.$openid.'",
           "template_id":"NI06ig0Bsp_COYNKxZViL4QDa05L-_SF_6CkeGH_Jok",
           "url":"'.$url.'",
           "data":{
                   "first": {
                        "value":"'.$first.'"
                   },
                   "keyword1":{
                        "value":"'.$personName.'"
                   },
                   "keyword2": {
                        "value":"'.$date.'"
                   },
                   "remark":{
                        "value":"'.$detailCot.'"
                   }
           }
       }';
        return $this->curlHttpRequest($requestUrl,null,true,$createCardPostJson);
    }

    public function curlHttpRequest($url,$cookie = null,$skipssl = false ,$postDate = "") {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);
        if(! empty($cookie)) {
            curl_setopt($ch , CURLOPT_COOKIE , $cookie);
        }
        if( $skipssl) {
            curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , false);
            curl_setopt($ch , CURLOPT_SSL_VERIFYHOST , 0);
        }
        if( ! empty($postDate)) {
            curl_setopt($ch ,CURLOPT_POST ,1);
            curl_setopt($ch ,CURLOPT_POSTFIELDS ,$postDate );
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        curl_close($ch);
        return $tmpInfo;
    }

}
