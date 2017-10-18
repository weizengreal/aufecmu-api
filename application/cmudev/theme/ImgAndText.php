<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/6/18
 * Time: 下午3:32
 */

namespace app\cmudev\theme;
use memcacheLoad\FindHandle;

/*
 * 图文类型的数据
 * */

class ImgAndText extends FindHandle{

    /*
     * 处理图文类型的数据
     * */
    public function handleData($res,$fetch=false) {
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
            if($fetch)
                unset($res[$index]['id']);
        }
        return $res;
    }

}
