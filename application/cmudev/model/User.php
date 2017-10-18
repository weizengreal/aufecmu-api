<?php
/**
 * Created by PhpStorm.
 * User: WeiZeng
 * Date: 2017/2/21
 * Time: 15:17
 */
namespace app\cmudev\model;
use think\Model;

class User extends Model{

    /*
     * 允许传入数组和非数组以验证是否存在，存在返回true
     * */
    public function isExist($whereArr) {
        if(!is_array($whereArr)) {
            $whereArr=array(
                'unionid'=>$whereArr
            );
        }
        if($this->where($whereArr)->count() > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    //新增数据
    public function addNew($arr) {
        return $this->insert($arr) !== false;
    }

    /*
     * 根据unionid更新函数
     * 参数1：需要更改的用户cid
     * 参数2：待更新的关联数组
     * */
    public function updata($unionid,$upArr) {
        return $this->where("`unionid`='$unionid'")->update($upArr) !== false;
    }

    /*
     * 获得该用户的accessToken权限值
     * 可能为null，交由上级判断
     * */
    public function getAccessToken($unionid) {
        $res = $this->where("`unionid` = '$unionid' and `token_time` > ".time())->find();
        return $res['access_token'];
    }

    //批量传入关联数组以获取基本信息，返回一条数据
    public function getInfoByArr($arr){
        return $this->where($arr)->find();
    }

}