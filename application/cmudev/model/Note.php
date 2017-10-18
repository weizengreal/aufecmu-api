<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/6/18
 * Time: 下午2:54
 */
namespace app\cmudev\model;
use think\Model;
use app\cmudev\theme\ThemeFactory;

class Note extends Model {

    /*
     * 获取指定条数目的数据
     * */
    public function getLimitData($page , $count , $type = null , $fetch = false) {
        if( empty($type) || $type == 'find' ) {
            $whereStr =  "club_note.display=1";
        }
        else {
            $whereStr =  "club_note.display=1 and type = '$type'";
        }
        $res = $this->field("club_user.headimgurl,club_user.nickname,club_note.id,club_note.content,club_note.zan,club_note.comcount,club_note.note_info as imgInfo,club_note.signtime,club_note.type as theme,club_note.theme_name as themeName")
            ->where($whereStr)
            ->join("club_user","club_note.unionid=club_user.unionid")
            ->page($page,$count)
            ->order("signtime desc")
            ->select();
        return ThemeFactory::getInstance($type)->handleData($res,$fetch);
    }

    /*
     * 将现有数据group分组之后返回同居结果
     * */
    public function getThemeCount($limitCount) {
        return $this->field('count(*) as totalCount,type as theme,theme_name as themeName')
            ->group('type')
            ->order('signtime desc')
            ->limit($limitCount)
            ->select();
    }


}