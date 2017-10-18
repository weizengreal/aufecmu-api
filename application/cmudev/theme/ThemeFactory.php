<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/6/18
 * Time: 下午3:38
 */

namespace app\cmudev\theme;

class ThemeFactory {
    public static function getInstance( $theme ) {
        if( $theme == 'where' || $theme == 'love' || $theme == 'lostAndFound' || $theme == 'tradeCenter' || $theme == 'schoolOfficial' || $theme == 'officialTucao' ) {
            return new ImgAndText();
        }
        else {
            return new ImgAndText(); // 默认情况下返回一个图文类型
        }
    }
}
