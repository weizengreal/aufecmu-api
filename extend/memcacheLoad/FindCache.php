<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/6/13
 * Time: 下午8:46
 */

namespace memcacheLoad ;

use \think\cache\driver\Memcached;

// 一个memcahce变量中最多能存储多少个数据索引（即主题类型中存放索引的数组最大长度），超过该值默认为不缓存，需要在数据库中读取
define('MEMCACHE_COUNT',500);

// memcache初始化时一次拉取的数据条数
define('ONCE_PULL_LIMIT',100);

// 接口一页返回的数据条数
define('ONE_PAGE_COUNT',8);
/*
 * 基于memcache的数据惰性存储组件（栈结构，后进先出）
 * 当前系统负荷下，不考虑 memcache 内存不够大情况
 * 该组件不负责系统安全部分，只负责和memcahce缓存服务器和数据库交互部分
 *
 * 组件提供如下功能函数
 * 1、将初始化的数据保存在memcache中
 * 2、向memcache中读取指定量的数据并返回
 * 3、新增一条数据到memcache中
 * 4、修改指定下标的memcache数据项
 * 5、删除在主题memcache中存储的某个id项
 * 6、新增n条数据到memcache中（并发存储）
 *
 *
 * */

class FindCache extends Memcached {

//    private $memcache ;
    private $prefix ; // memcache前缀，格式为：memcache_应用名称_版本_变量名称

    // 默认当前状态为生产环境
    public function __construct($version = 'product')
    {
        $this->prefix = "memcache_api_{$version}_";
        parent::__construct();
    }

    /*
     * 要求传递相关的数据data
     * 主题类型type，默认情况(为 null)将默认处理为系统初始化
     * null：覆盖设置所有选项
     * */
    public function initThemeData($data,$themeFormat) {
        // 默认情况，这里视为系统初始化启动时执行的函数
        $themeData = [];
        $totalCount = count($data);
        for ($i =0 ; $i < $totalCount - 1 ; ++$i) {
            $themeData[$this->prefix.$data[$i]['id']] = $data[$i]->getData();
            $this->initHandle($themeData,$data[$i]);
        }
        $themeData[$this->prefix.$data[$totalCount-1]['id']] = $data[$totalCount - 1]->getData();
        $this->initHandle($themeData,$data[$i],$themeFormat,true);
        return $this->multiAdd($themeData);
    }

    /*
     * 获得某个主题的数据
     * */
    public function getThemeData($page,$type) {
        if( !$this->has($this->prefix.$type) ) {
            return getErrMsg(3001);
        }
        $memTheme = $this->get($this->prefix.$type);
        if($memTheme['page'] >= $page) {
            // 正确情况，表示memcache中存在这些数据，直接读取即可
            return $this->getDataByTheme($page,$memTheme);
        }
        else if($memTheme['totalCount'] < MEMCACHE_COUNT) {
            // memcache 没有存满，却请求超过memcache限制的数据，判定为：数据已经到底了
            return getErrMsg(8);
        }
        else {
            // memcache存满了，要求逻辑层直接向数据库请求数据
            return getErrMsg(3002);
        }
    }

    public function updateData($id,$upData) {
    }

    /*
     * 数据删除
     * 将数据库中该条数据display设置为2，将该id的缓存索引从 memcache_api_version_theme
     * 的对应主题中直接删除，将对应的 memcache_api_version_id 缓存数据直接删除
     * */
    public function deleteData($id) {
        return false;
    }

    /*
     * 新增n条数据到memcached中
     * */
    public function multiAdd(& $themeData) {
        return $this->handler()->setMulti($themeData);
    }

    /*
     * 处理每一条数据到对应的下标组合
     *
     * */
    private function initHandle(& $themeData , $item , $themeFormat = null, $end = false) {
        $themeData[$this->prefix.$item['theme']]['id'][] = $item['id'];
        $themeData[$this->prefix.'find']['id'][] = $item['id'];
        if( $end ) {
            // 最后一次将自动整理主题索引
            $this->formatTheme($themeData,$themeFormat);
        }
    }

    /*
     * 处理一个主题数据
     * 定义主题类型的数组格式：
     * 以where主题为例，则该数组含有以下变量：
     * 1、数据总条数:totalCount
     * 2、该主题英文名称:theme（理论上应直接等于where）
     * 3、该主题中文名称:themeName
     * 4、应有的数据页数:page
     * 5、该主题下的数据索引:id (array)
     * */
    private function formatTheme(& $themeData , $themeFormat) {
        foreach ($themeFormat as $index => $item) {
            $item['page'] = ceil($item['totalCount']/ONE_PAGE_COUNT);
            $themeData[$this->prefix.$item['theme']] = array_merge($item->getData(),$themeData[$this->prefix.$item['theme']]);
        }
        $findArr = [
            'totalCount'=>count($themeData[$this->prefix.'find']['id']),
            'theme'=>'find',
            'themeName'=>'校友圈'
        ];
        $findArr['page'] = ceil($findArr['totalCount']/ONE_PAGE_COUNT);
        $themeData[$this->prefix.'find'] = array_merge($findArr,$themeData[$this->prefix.'find']);
    }


    /*
     * 数据获取逻辑
     * 1、在memcache中找寻是否存在该主题类型；
     * 内部逻辑：若 memcache 不存在 memcache_api_{version}_{theme} 变量，
     * 需要先行初始化，若找不到该主题类型直接返回相应错误状态码，
     * 最后需要根据该变量判断当前页数是否超过限制。
     *
     * 2、在memcache中找寻该主题对应变量的数据并返回；
     *
     * */
    public function getMemData($page = 1,$type = 'find') {
        if( !$this->has($this->prefix.$type) ) {
            $this->initMemTheme();
        }
        $memTheme = $this->memcache->get($this->prefix.$type);
        if( !isset($memTheme[$type]) ) {
            return getErrMsg(7);
        }
        else {
            // 检测是否不存在id字段，如果不存在表示该数据项未初始化
            if( !isset($memTheme[$type]['id']) ) {
                $this->initMemData($type);
            }
            if($memTheme[$type]['page'] >= $page) {
                return $this->getDataByTheme($page,$memTheme);
            }
            else if($page*8 < MEMCACHE_COUNT) {
                return getErrMsg(8);
            }
            else {
                return $this->getData($type,($page-1)*ONE_PAGE_COUNT,ONE_PAGE_COUNT);
            }
        }
    }

    /*
     * 获得指定类型的数据
     * 内部逻辑：该数据为数组索引，索引顺序按时间从近到远依次排序，
     * 再具体返回数据过程中，若任然拥有数据缺没有在memcache缓存中，
     * 需要先从数据库中拉取{ONCE_PULL_LIMIT}条数据存入memcache中。
     *
     * */
    private function getDataByTheme($page,& $memTheme) {
        $data = array();
        $endCount = $page*ONE_PAGE_COUNT;
        // 当前页数在容许范围{MEMCACHE_COUNT}内，直接通过memcache缓存读取
        for($i = $endCount - ONE_PAGE_COUNT ; $i < $endCount && $i < $memTheme['totalCount']; ++$i) {
            $data[] = $this->get($this->prefix.$memTheme['id'][$i]);
        }
        return $data;
    }



    /*
     * 初始化主题类型  memcache_api_version_theme
     * 定义主题类型的数组格式：
     * 以where主题为例，则该数组含有以下变量：
     * 1、数据总条数:totalCount
     * 2、该主题英文名称:engName（理论上应直接等于where）
     * 3、该主题中文名称:ChineseName
     * 4、应有的数据页数:page
     * 5、该主题下的数据索引:index
     *
     * data由子类传入
     * */
    private function handleMemTheme($data) {
        // TODO:: 初始化memcache的数据
        foreach ($data as $index => $item) {
            $item['page'] = ceil($item['totalCount']/ONE_PAGE_COUNT);
            $this->memcache->set($this->prefix.$item['engName'],$item);
        }
    }

     /*
      * 初始化数据
      * 定义每一条数据的变量名格式为: memcache_api_{version}_{id}
      * */
    private function handleMemData($data) {

    }



    /*
     * 新添加一条数据
     * 向数据库中写入一条数据，成功后，应将数据写入对应主题缓存中的第一项，
     * 更新 memcache_api_version_theme 中对应的主题
     * */
    public function insertData() {
        return false;
    }


    /*
     * 将一条数据从删除状态恢复（业务功能未上线，等待拓展）
     * 将数据库该数据的display设置为1，将该id添加到 memcache_api_version_theme 对应的主题中，
     * 将该数据重新写入memcache缓存中
     * */
    public function recoverData() {
        return false;
    }




}


