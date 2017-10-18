<?php
namespace app\index\controller;
use Qiniu\Auth;

class Index
{
    // 获取七牛云 community 空间的上传 Token
    public function getQnToken() {
        $auth = new Auth(config("qnAccessKey"),config("qnSecretKey"));
        $bucket = 'community';
        return json_encode([
            'uptoken'=>$auth->uploadToken($bucket)
        ]);
    }

    // 获取七牛云 wxeva 空间的上传 Token
    public function getEvaQnToken() {
        $auth = new Auth(config("qnAccessKey"),config("qnSecretKey"));
        $bucket = 'wxeva';
        return json_encode([
            'uptoken'=>$auth->uploadToken($bucket)
        ]);
    }
}
