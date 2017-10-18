<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/6
 * Time: 上午11:06
 */
namespace app\cmu\logic;
use \app\cmu\model\User;
use \think\Db;


class UserAuth
{

    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function getAuthLevel()
    {

    }

    /*
     * 形参：accesstoken
     * 返回数组：
     * array(
     *  'openid'=>$openid(null),
     *  'unionid'=>$unionid(null),
     *  'errcode'=>int,
     *  'info'=>string
     * )
     * */
    public function judgeAccessToken($accessToken)
    {
        $userInfo = Db::table("club_user")->where("`access_token` = '$accessToken' and `token_time` > ".time())->find();
        if (empty($userInfo)) {
            return array(
                    'openid' => null,
                    'unionid' => null,
                ) + getErrMsg(4);
        } else {
            return array(
                    'openid' => $userInfo['openid'],
                    'unionid' => $userInfo['unionid'],
                ) + getErrMsg(1);
        }
    }

    /*
     * 增加用户的User数据
     * 根据unionid自动判断更新还是添加
     * 默认更新参数有：
     * headimgurl、nickname、uptime、token_time、status
     * access_token的更新逻辑如下：
     * 查询数据库上access_token是否过期，
     * 已过期则更新数据，
     * 未过期只能返回数据库对应字段数据并更新该权限时间长度
     * */
    public function addAuthDataForApplet($userArr)
    {
        $time = time();
        $isaArr = [
            'headimgurl' => $userArr['avatarUrl'],
            'nickname' => $userArr['nickName'],
            'uptime' => $time,
            'token_time'=>$time+3600*24*3,
            'status' =>1
        ];
        // 判断该用户是否存在
        if($this->user->isExist([
            'unionid' => $userArr['unionId']
        ])) {
            $accessToken = $this->user->getAccessToken($userArr['unionId']);
            if(empty($accessToken)) {
                $isaArr['access_token'] = $this->getOneAccessToken($userArr['code']);
            }
            return [
                'status'=>$this->user->updata($userArr['unionId'],$isaArr) !== false,
                'access_token'=>empty($isaArr['access_token']) ? $accessToken : $isaArr['access_token'],
                'token_time'=>$isaArr['token_time'],
                'headimgurl'=>$isaArr['headimgurl'],
                'nickname'=>$isaArr['nickname'],
            ];
        }
        else {
            $isaArr += [
                'openid_applet' => $userArr['openId'],
                'unionid' => $userArr['unionId'],
                'access_token'=>$this->getOneAccessToken($userArr['code'])
            ];
            return [
                'status'=>$this->user->addNew($isaArr) !== false,
                'access_token'=>$isaArr['access_token'],
                'token_time'=>$isaArr['token_time'],
                'headimgurl'=>$isaArr['headimgurl'],
                'nickname'=>$isaArr['nickname'],
            ];
        }
    }


    /*
     * 获取不重复的access_token
     * */
    public function getOneAccessToken($singleId) {
        $accessToken = password_hash($singleId,PASSWORD_BCRYPT);
        if($this->user->isExist(array('access_token'=>$accessToken))) {
            $accessToken = password_hash($singleId,PASSWORD_BCRYPT);
        }
        return $accessToken;
    }



}