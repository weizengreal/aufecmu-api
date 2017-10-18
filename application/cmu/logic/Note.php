<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/6
 * Time: 上午11:38
 */
namespace app\cmu\logic;

use think\Db;
use think\Log;


class Note {

    /*
     * 获取所有数据
     * */
    public static function getAllData($page,$type = null) {
        if( empty($type) || $type == 'find' ) {
            $whereStr =  "club_note.display=1";
        }
        else {
            $whereStr =  "club_note.display=1 and type = '$type'" ;
        }
//        $whereStr = !empty($type) ? "club_note.display=1 and type = '$type'" : "club_note.display=1";
        $res = Db::table("club_note")
            ->field("club_user.headimgurl,club_user.nickname,club_note.id,club_note.content,club_note.zan,club_note.comcount,club_note.note_info as imgInfo,club_note.signtime,club_note.theme_name as theme")
            ->where($whereStr)
            ->join("club_user","club_note.unionid=club_user.unionid")
            ->page($page,8)
            ->order("signtime desc")
            ->select();
        foreach ($res as $index => $item) {
            $res[$index]['noteid']=authcode($item['id'],"ENCODE");
            $res[$index]['signtime']=getTime($item['signtime']);
            if(! empty($item['imgInfo']) ) {
                $imgArr = [];
                $item['imgInfo']=json_decode($item['imgInfo'],true);
                if(count($item['imgInfo']) == 1) {
                    $imgArr[]=$item['imgInfo'][0]."?imageView2/2/w/450";
                }
                else {
                    foreach($item['imgInfo'] as $it) {
                        $imgArr[] = $it."?imageView2/1/w/270/h/270";
                    }
                }
                $res[$index]['imgInfo']=$imgArr;
            }
//            $res[$index]['testid']=authcode($res[$index]['noteid'],"DECODE");
            unset($res[$index]['id']);
        }
        return $res;
    }


    /*
     * 获取某个帖子的详细信息
     * 需要同时发送八条评论数据
     * 需要发送10个点赞人员的头像
     * */
    public static function getOneNote($id,$userid=null) {
        $res = Db::table("club_note")
            ->field("club_user.unionid,club_user.headimgurl,club_user.nickname,club_note.content,club_note.signtime,club_note.note_info as imgInfo,club_note.zan,club_note.comcount,club_note.theme_name as theme,club_note.type")
            ->where("club_note.id='$id' and club_note.display=1 ")
            ->join("club_user","club_note.unionid=club_user.unionid")
            ->find();
        $res['zanList'] = Db::table("club_behave")
            ->field("club_user.headimgurl")
            ->where("club_behave.id='$id' and club_behave.thing=1 ")
            ->join("club_user","club_user.unionid=club_behave.unionid")
            ->order("club_behave.addtime asc")
            ->select();
        $res['isZan'] = Db::table("club_behave")
            ->where("id='$id' and thing=1 and unionid = '$userid'")
            ->count() > 0 ? false : true;
        $res['isOwn'] = adminAuth($userid) ? true :  $res['unionid'] == $userid;
        unset($res['unionid']);
        $comRes = Db::table("club_comment")
            ->field("club_user.headimgurl,club_user.nickname,club_comment.comment,club_comment.comtime,club_comment.cid,club_comment.note_nickname as note_name,club_comment.unionid")
            ->where("club_comment.id = '$id' and club_comment.reply_id = 0")
            ->join("club_user","club_comment.unionid = club_user.unionid")
            ->order("club_comment.comtime asc")
            ->limit(0,8)
            ->select();
        $orStr = "";
        for($i = 0; $i < count($comRes) ; ++$i) {
            if($i == count($comRes) - 1) {
                $orStr .= "owner = ".$comRes[$i]['cid'];
            }
            else {
                $orStr .= " owner = ".$comRes[$i]['cid']." or ";
            }
        }
        $comReply = Db::table("club_comment")
            ->field("club_user.headimgurl,club_user.nickname,club_comment.comment,club_comment.comtime,club_comment.cid,club_comment.note_nickname as note_name,club_comment.unionid,club_comment.owner")
            ->where($orStr)
            ->join("club_user","club_comment.unionid = club_user.unionid")
            ->order("club_comment.comtime asc")
            ->select();
        if(! empty($res['imgInfo']) ) {
            $imgArr = [];
            $res['imgInfo']=json_decode($res['imgInfo'],true);
            if(count($res['imgInfo']) == 1) {
                $imgArr[]=$res['imgInfo'][0];
            }
            else {
                foreach($res['imgInfo'] as $it) {
                    $imgArr[] = $it;
                }
            }
            $res['imgInfo']=$imgArr;
        }
        $res['comList'] = self::handleComment($comRes,$comReply,$userid);
        $res['signtime'] = getTime($res['signtime']);
        return $res;
    }

    /*
     * 获取评论列表信息
     * */
    public static function getCommentList($id,$page,$userid=null,$limit=20) {
        $comRes = Db::table("club_comment")
            ->field("club_user.headimgurl,club_user.nickname,club_comment.comment,club_comment.comtime,club_comment.cid,club_comment.note_nickname as note_name,club_comment.unionid")
            ->where("club_comment.id = '$id' and club_comment.reply_id = 0")
            ->join("club_user","club_comment.unionid = club_user.unionid")
            ->order("club_comment.comtime asc")
            ->limit(($page-1)*20+8,$limit)
            ->select();
        $orStr = "";
        for($i = 0; $i < count($comRes) ; ++$i) {
            if($i == count($comRes) - 1) {
                $orStr .= "owner = ".$comRes[$i]['cid'];
            }
            else {
                $orStr .= " owner = ".$comRes[$i]['cid']." or ";
            }
        }
        $comReply = Db::table("club_comment")
            ->field("club_user.headimgurl,club_user.nickname,club_comment.comment,club_comment.comtime,club_comment.cid,club_comment.note_nickname as note_name,club_comment.unionid,club_comment.owner")
            ->where($orStr)
            ->join("club_user","club_comment.unionid = club_user.unionid")
            ->order("club_comment.comtime asc")
            ->select();
        return self::handleComment($comRes,$comReply,$userid);
    }


    /*
     * 根据id抓取的评论信息处理为可用的接口数据
     * */
    private static function handleComment($res,$replyComment,$userid=null) {
        foreach ($replyComment as $item) {
            if($item['unionid'] == $userid) {
                $item['isCom'] = false;
            }
            else {
                $item['isCom'] = true;
            }
            $item['comId']=authcode($item['cid'], "ENCODE" );
            $item['noteName']=$item['note_name'];
            $item['time']=getTime($item['comtime']);
            unset($item['note_name']);
            unset($item['comtime']);
            unset($item['unionid']);
            foreach ($res as $index => $it) {
                if($res[$index]['cid'] == $item['owner']) {
                    unset($item['owner']);
                    unset($item['cid']);
                    $res[$index]['repliedComment'][]=$item;
                    break;
                }
            }
        }
        foreach ($res as $index => $item) {
            if($res[$index]['unionid'] == $userid) {
                $res[$index]['isCom'] = false;
            }
            else {
                $res[$index]['isCom'] = true;
            }
            $res[$index]['comId']=authcode($res[$index]['cid'], "ENCODE" );
            $res[$index]['noteName']=$res[$index]['note_name'];
            $res[$index]['time']=getTime($res[$index]['comtime']);
            unset($res[$index]['note_name']);
            unset($res[$index]['comtime']);
            unset($res[$index]['cid']);
            unset($res[$index]['unionid']);
        }
        return $res;
    }


    /*
     * 获取当前社区的所有主题
     * */
    public static function getTheme() {
        $themeRes = Db::table("club_theme")
            ->where(1)
            ->field("type as theme,theme_name as name")
            ->select();

        return $themeRes;
    }


    /*
     * 创建一个新的帖子
     * 形参：$postData
     * 返回int型，表示该条
     * */
    public static function newNote($postData) {
        $otherInfo = self::groupData($postData);
        $noteArr = [
                'unionid'=>$postData['unionid'],
                'content'=>$postData['content'],
                'signtime'=>time()
            ]+$otherInfo;
        return Db::table("club_note")
            ->insert($noteArr);
    }

    /*
     * 组合多主题版本的发帖接口
     * */
    public static function groupData($postData) {
        $otherInfo = [];
        if(empty($postData['type'])) {
            // 默认情况下将帖子发送在"一张图说说你在哪里"
            $otherInfo['source'] = 1;
            $otherInfo['type'] = "where";
            $otherInfo['theme_name'] = "一张图说说你在哪里";
        }
        else {
            // 多主题版本的处理，当前状态只兼容图文模式
            if(Db::table("club_theme")->where(array('type'=>$postData['type']))->count() > 0) {
                $otherInfo+=Db::table("club_theme")
                    ->field("theme_name,type,source")
                    ->where(array('type'=>$postData['type']))
                    ->find();
            }
            else {
                return false;
            }
        }

        if($otherInfo['source'] === 1) {
            if(! empty($postData['imgInfo'])) {
                if(is_array($postData['imgInfo'])) {
                    $otherInfo += ['note_info'=>json_encode($postData['imgInfo'])];
                }
                else {
                    // 默认处理为json字符串传递
                    $img_info = array();
                    $imgInfo = json_decode($postData['imgInfo']);
                    foreach ($imgInfo as $item) {
                        $img_info[] = $item;
                    }
                    $otherInfo += ['note_info'=>json_encode($img_info)];
                }
            }
        }
        else {
            // TODO 其他类型，暂不支持
            return false;
        }
        unset($otherInfo['source']);
        Log::write($otherInfo,"otherInfo::");
        return $otherInfo;
    }

    /*
     * 创建一个新的评论
     * 需要做如下几件事情：
     * 1、根据id或者comId（即single）获取被回复人员的基本信息写入数据库中
     * 2、组合insert数组写入数据库
     * 3、将该帖子的评论数+1
     * */
    public static function newComment($postData) {
        if($postData['single'] == "0") {
            // 根据id获取被回复人员基本信息
            $backUserInfo = Db::table("club_note")
                ->where("club_note.id = ".$postData['id'])
                ->field("club_user.unionid as note_unionid,club_user.openid,club_user.nickname as note_nickname")
                ->join("club_user","club_note.unionid = club_user.unionid")
                ->find();
        }
        else {
            // 根据single获取被回复人员基本信息
            $backUserInfo = Db::table("club_comment")
                ->where("club_comment.cid = ".$postData['single'])
                ->field("club_user.unionid as note_unionid,club_user.openid,club_user.nickname as note_nickname")
                ->join("club_user","club_comment.unionid = club_user.unionid")
                ->find();
            if($postData['unionid'] == $backUserInfo['note_unionid']) {
                return getErrMsg(2001);
            }
        }
        $postData['reply_id'] = $postData['single'];
        $single = $postData['single'];
        $openid = $backUserInfo['openid'];
        unset($postData['single']);
        unset($backUserInfo['openid']);
        if(!empty($backUserInfo)) {
            $comArr = $postData+$backUserInfo+[
                    'comtime'=>time()
                ];
            if(Db::table("club_comment")->insert($comArr) !== false &&
                Db::table("club_note")->where("`id`='{$postData['id']}'")->setInc("comcount",1) !== false) {
                // 发送模板消息
                $url="http://wx.aufe.vip/aufecmu/index.html#/detail/".authcode($comArr['id'],"ENCODE");
                $message = new \wxLib\Message();
//                $to = Db::table("club_user")->where(array('unionid'=>$comArr['note_unionid']))->find();
                $from = Db::table("club_user")->where(array('unionid'=>$comArr['unionid']))->find();
                if($single == "0") {
                    $message->sendMessage($openid,$url,"你的动态收到一条新的评论",$from['nickname'],date("Y-m-d G:i"),$comArr['comment']);
                }
                else {
                    $message->sendMessage($openid,$url,"你的评论收到一条新的回复",$from['nickname'],date("Y-m-d G:i"),$comArr['comment']);
                }
                return [
                        'sqlHandle'=>true
                    ]+getErrMsg(1);
            }
            else {
                return [
                        'sqlHandle'=>false
                    ]+getErrMsg(-1);
            }
        }
        else {
            return getErrMsg(5);
        }
    }
//    public static function newComment($postData) {
//        if($postData['single'] == "0") {
//            // 根据id获取被回复人员基本信息
//            $backUserInfo = Db::table("club_note")
//                ->where("club_note.id = ".$postData['id'])
//                ->field("club_user.unionid as note_unionid,club_user.nickname as note_nickname")
//                ->join("club_user","club_note.unionid = club_user.unionid")
//                ->find();
//        }
//        else {
//            // 根据single获取被回复人员基本信息
//            $backUserInfo = Db::table("club_comment")
//                ->where("club_comment.cid = ".$postData['single'])
//                ->field("club_user.unionid as note_unionid,club_user.nickname as note_nickname")
//                ->join("club_user","club_comment.unionid = club_user.unionid")
//                ->find();
//            if($postData['unionid'] == $backUserInfo['note_unionid']) {
//                return getErrMsg(2001);
//            }
//        }
//        $postData['reply_id'] = $postData['single'];
//        $single = $postData['single'];
//        unset($postData['single']);
//        if(!empty($backUserInfo)) {
//            $comArr = $postData+$backUserInfo+[
//                    'comtime'=>time()
//                ];
//            if(Db::table("club_comment")->insert($comArr) !== false &&
//                Db::table("club_note")->where("`id`='{$postData['id']}'")->setInc("comcount",1) !== false) {
//                // 发送模板消息
//                $url="http://wx.aufe.vip/aufecmu/index.html#/detail/".authcode($comArr['id'],"ENCODE");
//                $message = new \wxLib\Message();
//                $to = Db::table("club_user")->where(array('unionid'=>$comArr['note_unionid']))->find();
//                $from = Db::table("club_user")->where(array('unionid'=>$comArr['unionid']))->find();
//                if($single == "0") {
//                    $message->sendMessage($to['openid'],$url,"你的动态收到一条新的评论",$from['nickname'],date("Y-m-d G:i"),$comArr['comment']);
//                }
//                else {
//                    $message->sendMessage($to['openid'],$url,"你的评论收到一条新的回复",$from['nickname'],date("Y-m-d G:i"),$comArr['comment']);
//                }
//                return [
//                        'sqlHandle'=>true
//                    ]+getErrMsg(1);
//            }
//            else {
//                return [
//                        'sqlHandle'=>false
//                    ]+getErrMsg(-1);
//            }
//        }
//        else {
//            return getErrMsg(5);
//        }
//    }


    /*
     * 点赞接口
     * 1、变动behave表
     * 2、变动帖子对应的列的值
     *
     * 返回数组，对应下标为：
     * behave：1  点赞   2  取消点赞
     * isOk：boolean，表示该操作是否成功
     * headimgurl：该用户的头像
     * */
    public static function zan($postData) {
        if(Db::table("club_behave")->where($postData)->count() > 0) {
            // 取消点赞、note-1
            if(Db::table("club_behave")
                    ->where($postData)
                    ->limit(1)
                    ->delete() !== false &&
                Db::table("club_note")->where("`id`='{$postData['id']}'")->setDec("zan",1) !== false) {
                $userInfo = Db::table("club_user")->field("headimgurl")->where("`unionid`='{$postData['unionid']}'")->find();
                return [
                    'behave'=>2,
                    'isOk'=>true,
                    'headimgurl'=>$userInfo['headimgurl']
                ];
            }
            else {
                return [
                    'behave'=>2,
                    'isOk'=>false,
                ];
            }

        }
        else {
            // 点赞、note+1
            $behaveArr = $postData + [
                    'ipaddress'=>getIPaddress(),
                    'addtime'=>time(),
                    'thing'=>1
                ];
            if(Db::table("club_behave")
                    ->insert($behaveArr) !== false &&
                Db::table("club_note")->where("`id`='{$postData['id']}'")->setInc("zan",1) !== false) {
                $userInfo = Db::table("club_user")->field("headimgurl")->where("`unionid`='{$postData['unionid']}'")->find();
                return [
                    'behave'=>1,
                    'isOk'=>true,
                    'headimgurl'=>$userInfo['headimgurl']
                ];
            }
            else {
                // 可能用户数据出错，查不到该用户
                return [
                    'behave'=>1,
                    'isOk'=>false,
                ];
            }
        }
    }

    /*
     * 隐藏当前用户的帖子
     *
     * thing：2 删除帖子
     *
     *
     * 权限检测
     * 检测该用户是否隐藏的是自己发的帖子
     *
     * */
    public static function hideNote($postData) {
        if(adminAuth($postData['unionid'])) {
            // 管理员权限
            $behaveArr = array_merge($postData,[
                'thing'=>2,
                'addtime'=>time(),
                'ipaddress'=>getIPaddress()
            ]);
            $postArr = $postData;
            unset($postArr['unionid']);
            if(Db::table('club_note')->where($postArr)->setField('display',2) !== false &&
                Db::table('club_behave')->insert($behaveArr) !== false) {
                return getErrMsg(1);
            }
            else {
                return getErrMsg(-1);
            }
        }
        else {
            if(Db::table('club_note')->where(array_merge($postData,['display'=>1]))->count() > 0) {
                $behaveArr = array_merge($postData,[
                    'thing'=>2,
                    'addtime'=>time(),
                    'ipaddress'=>getIPaddress()
                ]);
                if(Db::table('club_note')->where($postData)->setField('display',2) !== false &&
                    Db::table('club_behave')->insert($behaveArr) !== false) {
                    return getErrMsg(1);
                }
                else {
                    return getErrMsg(-1);
                }
            }
            else {
                // 前端已经不存在这条记录、判定用户非法访问
                return getErrMsg(6);
            }
        }
    }



    // out的代码
    /*
     * 根据id抓取的评论信息处理为可用的接口数据
     * */
    /*
    private static function handleComment($res,$userid=null) {
        $result = array();
        foreach ($res as $index => $item) {
            if($res[$index]['openid'] == $userid) {
                $res[$index]['isCom'] = false;
            }
            else {
                $res[$index]['isCom'] = true;
            }
            unset($res[$index]['openid']);
            if($res[$index]['single'] == 0) {
                $result[]=$res[$index];
            }
        }
        foreach ($res as $it) {
            if($it['single'] != 0) {
                for( $i =0 ;$i < count($result) ; ++$i) {
                    if($it['single'] == $result[$i]['cid']) {
                        $it['comId']=authcode($it['cid'], "ENCODE" );
                        $it['noteName']=$it['note_name'];
                        $it['time']=getTime($it['comtime']);
                        unset($it['cid']);
                        unset($it['note_name']);
                        unset($it['comtime']);
                        unset($it['single']);
                        $result[$i]['repliedComment'][]=$it;
                        break;
                    }
                }
            }
        }
        $_result=array();
        foreach ($result as $item) {
            $item['comId']=authcode($item['cid'], "ENCODE" );
            $item['noteName']=$item['note_name'];
            $item['time']=getTime($item['comtime']);
            unset($item['cid']);
            unset($item['note_name']);
            unset($item['comtime']);
            unset($item['single']);
            $_result[]=$item;
        }
        return $_result;
    }
    */

    /*
         * 根据id抓取的评论信息处理为可用的接口数据
         * */
    /*
    private static function _handleComment($res,$userid=null) {
        $result = array();
        for ( $i = 0, $resultLength = 0; $i < count($res) ; ++$i ) {
            if($res[$i]['openid'] == $userid) {
                $res[$i]['isCom'] = false;
            }
            else {
                $res[$i]['isCom'] = true;
            }
            $res[$i]['comId']=authcode($res[$i]['cid'], "ENCODE" );
            $res[$i]['noteName']=$res[$i]['note_name'];
            $res[$i]['time']=getTime($res[$i]['comtime']);
            unset($res[$i]['note_name']);
            unset($res[$i]['comtime']);
            if($res[$i]['single'] == "0") {
                $result[] = $res[$i];
                $res[$i]['_index'] = $resultLength++;
            }
            else {
                for( $j=0; $j<$i; ++$j ) {
                    if($res[$i]['single'] == $res[$j]['cid']) {
                        $result[$res[$j]['_index']]['repliedComment'][]=$res[$i];
                        $res[$i]['_index'] = $res[$j]['_index'];
                    }
                }
            }
        }
        foreach($result as $index => $item) {
            unset($result[$index]['cid']);
            unset($result[$index]['openid']);
            unset($result[$index]['single']);
            if(!empty($result[$index]['repliedComment'])) {
                foreach($result[$index]['repliedComment'] as $_index => $it) {
                    unset($result[$index]['repliedComment'][$_index]['cid']);
                    unset($result[$index]['repliedComment'][$_index]['openid']);
                    unset($result[$index]['repliedComment'][$_index]['single']);
                }
            }
        }
        return $result;
    }

*/


}


