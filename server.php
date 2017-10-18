<?php
/**
 * Created by PhpStorm.
 * User: zhengwei
 * Date: 2017/5/26
 * Time: 下午2:40
 */

// TODO : 写数据库类、写发送模板消息类、发送模板消息

class server {
    private $server;
    private $logFile;

    public function __construct()
    {
        $this->logFile = 'swoole.log';
        $this->server = new swoole_server("127.0.0.1", 9501);
        $this->server->set(array(
            'worker_num' => 2,
            'daemonize'=>1, // 在服务器上以守护进程的形式运行
            'task_worker_num'=>4,
            'max_request' => 100,
        ));

        $this->server->on('receive', array($this,'onReceive'));
        $this->server->on('task', array($this,'onTask'));
        $this->server->on('finish', array($this,'onFinish'));
        $this->server->start();
    }

    public function onReceive ($serv, $fd, $from_id, $data) {
        //投递异步任务
        $task_id = $serv->task($data);
    }

    /*
     * 接收一个json数据包，数据包应有type参数
     * 其中type = 1 表示社区中发送模板消息，其他情况之后扩充逻辑即可
     * */
    public function onTask($serv,$task_id,$from_id, $data) {
        $jsonArr = json_decode( $data , true );
        switch ($jsonArr['type']) {
            case 1: {
                // 处理发送模板消息的业务逻辑


                $finishData = array(
                    'type'=>1,
                );
                break;
            }
            case 2: {

                break;
            }
            default: {

            }
        }
        $serv->finish($finishData);
    }

    /*
     * 处理每一个task的返回任务
     * 这里我们将记录相关信息到日志中
     *
     * 定时处理日志以
     * */
    public function onFinish($serv,$task_id, $data) {
        switch ($data['type']) {
            case 1: {
                $fileData = json_encode(array(
                    'task_id'=>$task_id,
                    'date'=>date('Y-m-d G:i:s'),
                    'time'=>time(),
                    'type'=>1,
                    'comId'=>$data['comId'],
                    'isOk'=>1,
                ),JSON_UNESCAPED_UNICODE);
                file_put_contents($this->logFile,$fileData.PHP_EOL,FILE_APPEND);
                break;
            }
            default: {
                $fileData = json_encode(array(
                    'task_id'=>$task_id,
                    'date'=>date('Y-m-d G:i:s'),
                    'time'=>time(),
                    'type'=>1,
                    'isOk'=>1,
                    'info'=>'lose this handle!'
                ),JSON_UNESCAPED_UNICODE);
                file_put_contents($this->logFile,$fileData.PHP_EOL,FILE_APPEND);
                break;
            }
        }

    }

}

