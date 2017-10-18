<?php
namespace app\cmu\controller;

use think\Db;
use think\Request;

use app\cmu\logic\UserAuth;
use app\cmu\logic\Note;

use \think\Log;

/*
 * 定义errcode参数的值
 * */

class Index
{
    private $request;
    private $userAuth;


    public function __construct()
    {
        $this->request = Request::instance();
        $this->userAuth = new UserAuth();
    }


    /*
         * 评论修复
         * */
    public function test()
    {
        echo authcode("e3af1JcBj5pW2Fi65Boy[c]e8qtoNzNR[c]JeyxPvxfnI7b9Eas","DECODE");
    }


    /*
     * 全局滤网函数
     * 形参$fitType 默认为1，
     * 1 速率验证
     * 2 身份验证+速率验证
     * 3
     * 功能如下：
     * 需要接收access_token参数并判断是否过期
     * */
    private function filter($fitType = 1)
    {
        switch ($fitType) {
            case 1: {
                // TODO::速率验证
                return getErrMsg(1);
                break;
            }
            case 2: {
                // TODO::速率验证
                $accessToken = $this->request->get("access_token", false);
                if ($accessToken != false) {
                    $authArr = $this->userAuth->judgeAccessToken($accessToken);
                    return $authArr;
                } else {
                    return getErrMsg(2);
                }
                break;
            }
            default: {
                return getErrMsg(-1);
            }
        }
    }

    /*
     * 获取所有信息函数
     * 接收一个get参数page
     * */
    public function getAllData()
    {
        $page = $this->request->get("page", 1);
        if (is_numeric($page)) {
            $fitArr = $this->filter();
            if ($fitArr['errcode'] == 1) {
                return [
                        'data' => Note::getAllData($page, $this->request->get("theme", null))
                    ] + getApiMsg(1);
            } else {
                return [
                        'data' => null,
                    ] + getApiMsg(4001);
            }
        } else {
            return [
                    'data' => null,
                ] + getApiMsg(4002);
        }
    }


    public function index()
    {
        $page = $this->request->get("page", 1);
        if (is_numeric($page)) {
            $fitArr = $this->filter();
            if ($fitArr['errcode'] == 1) {
                return [
                        'data' => Note::getAllData($page)
                    ] + getApiMsg(1);
            } else {
                return [
                        'data' => null,
                    ] + getApiMsg(4001);
            }
        } else {
            return [
                    'data' => null,
                ] + getApiMsg(4002);
        }
    }

    /*
     * 获取某个帖子的详细信息
     * 如果存在access_token参数，则需要处理以下变量
     * isZan：该用户是否已经赞过这个帖子
     * 否则默认没有点赞
     * */
    public function getDetail()
    {
        $noteid = $this->request->get("noteid", null);
        if (!empty($noteid)) {
            $id = authcode($noteid, "DECODE");
            if (!empty($id)) {
                $fitArr = $this->filter(2);
                if ($fitArr['errcode'] == 2) {
                    return [
                            'data' => Note::getOneNote($id),
                        ] + getApiMsg(1);
                } else {
                    return [
                            'data' => Note::getOneNote($id, $fitArr['unionid']),
                        ] + getApiMsg(1);
                }
            } else {
                return [
                        'data' => null,
                    ] + getApiMsg(4003);
            }

        } else {
            return [
                    'data' => null,
                ] + getApiMsg(4001);
        }
    }

    /*
     * 获取该帖子的评论信息
     * 如果存在access_token参数，则需要处理以下变量
     * isCom：该用户是否可以评论该帖子
     * 否则默认可以评论
     * */
    public function getComment()
    {
        $noteid = $this->request->get("noteid", null);
        $page = $this->request->get("page", 1);
        if (!empty($noteid)) {
            if (is_numeric($page)) {
                $id = authcode($noteid, "DECODE");
                if (!empty($id)) {
                    $fitArr = $this->filter(2);
                    if ($fitArr['errcode'] == 2) {
                        return [
                                'data' => Note::getCommentList($id, $page),
                            ] + getApiMsg(1);
                    } else {
                        return [
                                'data' => Note::getCommentList($id, $page, $fitArr['unionid']),
                            ] + getApiMsg(1);
                    }
                } else {
                    return [
                            'data' => null,
                        ] + getApiMsg(4003);
                }
            } else {
                return [
                        'data' => null,
                    ] + getApiMsg(4002);
            }
        } else {
            return [
                    'data' => null,
                ] + getApiMsg(4001);
        }
    }

    public function getTheme()
    {
        return [
                'data' => Note::getTheme(),
            ] + getApiMsg(1);
    }

    /*
     * 登录方法
     * 将该用户设置为登录状态（数据来源小程序）
     * 接收用户的设置登录状态信息
     * 接收post数据为：
     * code、encryptedData和iv
     *
     * 返回accessToken数据并设置accessToken超时时间为三天
     *
     * //TODO 不允许随意更新accessToken
     * */
    public function loginApplet()
    {
        $postData = $this->request->post();
        if (isset($postData['code']) && isset($postData['encryptedData']) && isset($postData['iv'])) {
            // 该用户第一次登陆，需要先换取 session_key
            $scope = new \applet\ScopeApplet();
            $codeArr = json_decode($scope->getSessionKey($postData['code']), true);
            if (!empty($codeArr['session_key'])) {
                $userArr = json_decode($scope->getUserInfo($codeArr['session_key'], $postData['encryptedData'], $postData['iv']), true);
                if (!empty($userArr['unionId'])) {
//                    $userArr['access_token'] = $this->userAuth->getOneAccessToken($postData['code']);
                    $userArr['code']=$postData['code'];
                    $result = $this->userAuth->addAuthDataForApplet($userArr);
                    if ($result['status']) {
                        return [
                                'data' => [
                                    'access_token' => $result['access_token'],
                                    'token_time' => $result['token_time'],
                                    'headimgurl'=>$result['headimgurl'],
                                    'nickname'=>$result['nickname'],
                                ]
                            ] + getApiMsg(1);
                    } else {
                        Log::write(json_encode($userArr), "4004=>userArr:");
                        return getApiMsg(4004);
                    }
                } else {
                    Log::write(json_encode($userArr), "3002=>userArr:");
                    return getApiMsg(3002);
                }
            } else {
                Log::write(json_encode($codeArr), "3001=>codeArr:");
                return getApiMsg(3001);
            }
        } else {
            return getApiMsg(4001);
        }
    }

    /*
     * 创建一条帖子
     * request：
     * accessToken：用户唯一标识
     * content：评论内容
     * imgInfo：图片信息(非必须)
     * */
    public function createNote()
    {
        $postData = $this->request->post();
        if (isset($postData['content'])) {
            $fitArr = $this->filter(2);
            if ($fitArr['errcode'] == 1) {
                $postData['unionid'] = $fitArr['unionid'];
//                \think\Log::write(json_encode($postData),"createNote");
//                \think\Log::write(json_encode($postData['imgInfo']),"createNote");
                $idNum = Note::newNote($postData);
                if ($idNum !== false) {
                    return [
                            'data' => [
                                'noteid' => authcode($idNum, "ENCODE")
                            ]
                        ] + getApiMsg(1);
                } else {
                    return getApiMsg(4004);
                }
            } else {
                return getApiMsg(4005);
            }
        } else {
            return getApiMsg(4001);
        }
    }

    /*
     * 创建一个评论
     * access_token：系统外接码
     * single：主评论=>0 回复他人的评论=>comId（该参数请参考获取评论部分对该参数的解释）
     * noteid：帖子的唯一标识
     * comment：评论内容
     * */
    public function createComment()
    {
        $postData = $this->request->post();
        if (isset($postData['single']) && isset($postData['noteid']) && isset($postData['comment']) && isset($postData['owner'])) {
            $postData['single'] = ($postData['single'] == "0") ? 0 : authcode($postData['single'], "DECODE");
            $postData['id'] = authcode($postData['noteid'], "DECODE");
            $postData['owner'] = $postData['single'] == "0" ? 0 : authcode($postData['owner'], "DECODE");
            unset($postData['noteid']);
//            \think\Log::write(json_encode($postData),"postData");
            if ((!empty($postData['single']) || $postData['single'] == "0") && (!empty($postData['owner']) || $postData['owner'] == "0") && !empty($postData['id']) && is_numeric($postData['single']) && is_numeric($postData['owner']) && is_numeric($postData['id'])) {
                $fitArr = $this->filter(2);
                if ($fitArr['errcode'] == 1) {
                    unset($postData['access_token']);
                    $postData['unionid'] = $fitArr['unionid'];
                    $retArr = Note::newComment($postData);
                    if ($retArr['errcode'] == "1" && $retArr['sqlHandle']) {
                        return [
                                'data' => [
                                    'comId' => null
                                ]
                            ] + getApiMsg(1);
                    } else if ($retArr['errcode'] == "2001") {
                        return getApiMsg(2001);
                    } else {
                        return getApiMsg(4004);
                    }
                } else {
                    return getApiMsg(4005);
                }
            } else {
                return getApiMsg(4002);
            }
        } else {
            return getApiMsg(4001);
        }
    }

    /*
     * 点赞接口
     * 接收参数：
     * access_token：系统外接码
     * noteid：该帖子的唯一标记
     * */
    public function zan()
    {
        $postData = $this->request->post();
        if (isset($postData['noteid'])) {
            $postData['id'] = authcode($postData['noteid'], "DECODE");
            unset($postData['noteid']);
            if (!empty($postData['id']) && is_numeric($postData['id'])) {
                $fitArr = $this->filter(2);
                if ($fitArr['errcode'] == 1) {
                    $postData['unionid'] = $fitArr['unionid'];
                    $zanArr = Note::zan($postData);
                    if ($zanArr['isOk']) {
                        return [
                                'data' => $zanArr
                            ] + getApiMsg(1);
                    } else {
                        return [
                                'data' => $zanArr
                            ] + getApiMsg(4004);
                    }
                } else {
                    return getApiMsg(4005);
                }
            } else {
                \think\Log::write(json_encode($postData), "postData1");
                return getApiMsg(4002);
            }
        } else {
            return getApiMsg(4001);
        }
    }

    /*
    * 发帖删除功能
    * 即将该用户该条信息隐藏
    * request：
    * noteid：帖子的编号
    * accessToken：用户权限
    *
    * */
    public function hideNote() {
        $postData = $this->request->post();
        if (isset($postData['noteid'])) {
            $postData['id'] = authcode($postData['noteid'], "DECODE");
            unset($postData['noteid']);
            if (!empty($postData['id']) && is_numeric($postData['id'])) {
                $fitArr = $this->filter(2);
                if ($fitArr['errcode'] == 1) {
                    $postData['unionid'] = $fitArr['unionid'];
                    $hideArr = Note::hideNote($postData);
                    if ($hideArr['errcode'] == 1) {
                        return getApiMsg(1);
                    }
                    else if($hideArr['errcode'] == 6) {
                        return getApiMsg(4006);
                    }
                    else {
                        return getApiMsg(4004);
                    }
                } else {
                    return getApiMsg(4005);
                }
            } else {
                return getApiMsg(4002);
            }
        } else {
            return getApiMsg(4001);
        }
    }


}