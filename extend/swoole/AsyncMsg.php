<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/26
 * Time: 下午3:48
 */
namespace swoole;

class AsyncMsg {

    private $client;

    public function __construct() {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP);
    }

    /*
     * 发送数据到本地的端口，要求swoole处理该发送模板消息的请求
     * */
    public function connect($comId,$openid) {
        if( !$this->client->connect("127.0.0.1", 9501 , 1) ) {
            die();
        }
        $data = array(
            'type'=>1,
            "comId" =>  $comId,
            'toUserOpenid'=> $openid
        );
        $json_data = json_encode($data);
        $this->client->send( $json_data );
    }

}



