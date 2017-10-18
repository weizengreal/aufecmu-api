<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/7
 * Time: 上午11:22
 */

namespace applet;

class ScopeApplet {

    private $appid;
    private $secret;

    public function __construct() {
        $this->appid=config("appletAppid");
        $this->secret=config("appletAppsecret");
    }

    /*
     * 根据code换取session_key+openid
     * */
    public function getSessionKey($code) {
//        return json_encode([
//            'openid'=>"tiihtNczf5v6AKRyjwEUhQ==",
//            'session_key'=>"tiihtNczf5v6AKRyjwEUhQ==",
//        ]);
        $requestUrl = "https://api.weixin.qq.com/sns/jscode2session?appid=$this->appid&secret=$this->secret&js_code=$code&grant_type=authorization_code";
        return $this->curlHttpRequest($requestUrl,null,true);
    }

    /*
     * 换取userInfo数据
     * */
    public function getUserInfo($sessionKey,$encryptedData,$iv) {
        \think\Loader::import('applet.wxBizDataCrypt');
        $pc = new \WXBizDataCrypt($this->appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data );
        if ($errCode == 0) {
            return $data;
        }
        else {
            return $errCode;
        }
    }


    private function curlHttpRequest($url,$cookie = null,$skipssl = false ,$postDate = "") {
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

