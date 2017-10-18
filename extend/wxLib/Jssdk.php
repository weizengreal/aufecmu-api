<?php
namespace wxLib;
/*
 * jssdk的集成核心类库文件
 * 需要传入appId、appSecret
 * jssdk根据appId区别接口
 * */

class Jssdk {
    private $appId;
    private $appSecret;
    private $path;
    public $userId;
    private $tokenPath;
    private $signPath;
    private $cardPath;

    public function __construct($appId,$appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->path=config("JSSDKPATH");
        $this->tokenPath = $this->path."access_token_".$appId.".json";
        $this->signPath = $this->path."jsapi_ticket_".$appId.".json";
        $this->cardPath = $this->path."api_ticket_".$appId.".json";
    }

    public function getAccessToken() {
        $data = json_decode(file_get_contents($this->tokenPath));
//        \think\Log::write(time(),"accessTokenJudgeTime");
//        \think\Log::write($data->expire_time,"accessTokenJudgeexpire_time");
//        \think\Log::write($data->expire_time < time(),"accessTokenJudge");
        if ($data->expire_time < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";

            $res = file_get_contents($url); //获取文件内容或获取网络请求的内容
            $result = json_decode($res, true); //接受一个 JSON 格式的字符串并且把它转换为 PHP 变量
            $access_token = $result['access_token'];

            if ($access_token) {
                $data->expire_time = time()+7000;
                $data->access_token = $access_token;
                $fp = fopen($this->tokenPath, "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
                chmod($this->tokenPath,0755);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }

    public function updateAccesstoken() {
        //直接拉，这是一个更新接口。
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
        $res = file_get_contents($url); //获取文件内容或获取网络请求的内容
//        \think\Log::write($res,"accessTokenJudgeTime");
        $result = json_decode($res, true); //接受一个 JSON 格式的字符串并且把它转换为 PHP 变量
        $access_token = $result['access_token'];
        $data = array();
        if ($access_token) {
            $data['expire_time'] = time()+7000;
            $data['access_token'] = $access_token;
            $fp = fopen($this->tokenPath, "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
            chmod($this->tokenPath,0755);
        }
        return $access_token;
    }

    public function getSignPackage($url=null) {
        $jsapiTicket = $this->getJsApiTicket();
        if(empty($url)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }
        else {
            $url = urldecode($url);
        }
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "String" => $string,
            "jsapiTicket" => $jsapiTicket,
        );
        return $signPackage;
    }

    public function getSignPackageForCard($url=null) {
        $jsapiTicket = $this->getJsApiTicket();
        $apiTicket = $this->getApiTicket();
        if(empty($url)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }
        else {
            $url = urldecode($url);
        }
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "String" => $string,
            "apiTicket" => $apiTicket,
            "signature_card" => $this->cal_sign(array($timestamp,$nonceStr,config("cardId"),$apiTicket))
        );
        return $signPackage;
    }

    /**
     * 计算签名
     * @param array $param_array
     */
    private function cal_sign($param_array) {
        sort($param_array, SORT_STRING);
        $paramStr = implode("",$param_array);
        file_put_contents("test.txt",$paramStr.PHP_EOL,FILE_APPEND);
        return sha1($paramStr);
    }

    /*
     * 创建一个随机字符串
     * */
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新
        $data = json_decode(file_get_contents($this->signPath ) ,true);
        if ( empty($data) || $data['expire_time'] < time() ) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $result=json_decode(file_get_contents($url),true);
            if($result['errcode']==40001){
                //如果accesstoken过期,重要求服务端更新accesstoken
                $accessToken = $this->updateAccessToken();
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
                $result=json_decode(file_get_contents($url),true);
            }
            $ticket = $result['ticket'];
            if ($ticket) {
                $data['expire_time'] = time()+7000;
                $data['ticket'] = $ticket;
                file_put_contents($this->signPath,json_encode($data));
            }
        } else {
            $ticket = $data['ticket'];
        }
        return $ticket;
    }

    /*
     * 获得添加会员卡的apiTicket
     * */
    private function getApiTicket() {
        $data = json_decode(file_get_contents($this->cardPath ) ,true);
        if ( empty($data) || $data['expire_time'] < time() ) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$accessToken&type=wx_card";
            $result=json_decode(file_get_contents($url),true);
            if($result['errcode']==40001){
                //如果accesstoken过期,重要求服务端更新accesstoken
                $accessToken = $this->updateAccessToken();
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$accessToken&type=wx_card";
                $result=json_decode(file_get_contents($url),true);
            }
            $ticket = $result['ticket'];
            if ($ticket) {
                $data['expire_time'] = time()+7000;
                $data['ticket'] = $ticket;
                file_put_contents($this->cardPath,json_encode($data));
            }
        }
        else {
            $ticket = $data['ticket'];
        }
        return $ticket;
    }

}

//$jssdk=new JssdkInterface();
//if(isset($_GET['type'])){
//    $type=$_GET['type'];
//    if($type=="access_token_web"){
//        echo $jssdk->getAccessToken();
//    }
//    else if($type=="update_access_token"){
//        echo $jssdk->updateAccesstoken();
//    }
//    else if($type=="ticket") {
//        header("Access-Control-Allow-Origin : *");
//        $url = urldecode($_POST['url']);
//        echo json_encode($jssdk->getSignPackage($url));
//    }
//    else{
//        $arr=array(
//            'errorId'=>404
//        );
//        echo json_encode($arr);
//    }
//}
//else{
//    $arr=array(
//        'errorId'=>404
//    );
//    echo json_encode($arr);
//}
?>