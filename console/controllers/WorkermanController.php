<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-11-29
 * Time: 16:32
 * Author: henry
 */

/**
 * @name WorkermanController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-11-29 16:32
 */


namespace console\controllers;

use console\models\ProductEngine;
use Workerman\Worker;
use yii\helpers\Console;
use yii\console\Controller;

class WorkermanController extends Controller
{
    public $send;
    public $daemon;
    public $gracefully;

    public static $websoket;

    // 这里不需要设置，会读取配置文件中的配置
    public $config = [];
    private $ip = '0.0.0.0';
    private $port = '2346';

    public function options($actionID)
    {
        return ['send', 'daemon', 'gracefully'];
    }


    public function optionAliases()
    {
        return [
            's' => 'send',
            'd' => 'daemon',
            'g' => 'gracefully',
        ];
    }

    public function actionIndex()
    {
        $this->worker();

    }


    public function sendMessage($message){
        foreach($this->websoket->connections as $connection)
        {
            $connection->send($message);
        }
        return true;
    }

    public function worker()
    {
        if (PHP_OS === 'WINNT') {
          $this->initWorker();
          Worker::runAll();
        }
        else {

            if ('start' == $this->send) {
                try {
                    $this->start($this->daemon);
                } catch (\Exception $e) {
                    $this->stderr($e->getMessage() . "\n", Console::FG_RED);
                }
            } else if ('stop' == $this->send) {
                $this->stop();
            } else if ('restart' == $this->send) {
                $this->restart();
            } else if ('reload' == $this->send) {
                $this->reload();
            } else if ('status' == $this->send) {
                $this->status();
            } else if ('connections' == $this->send) {
                $this->connections();
            }
        }
    }

    public function initWorker()
    {
        //var_dump($this->config['ip']);exit;
        $ip = isset($this->config['ip']) ? $this->config['ip'] : $this->ip;
        $port = isset($this->config['port']) ? $this->config['port'] : $this->port;
        $this->websoket = new Worker("websocket://{$ip}:{$port}");

//        $this->websoket->onWorkerStart = function($worker) {
//            // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
//            $inner_text_worker = new Worker('text://0.0.0.0:5678');
//            $inner_text_worker->onMessage = function ($connection, $buffer) {
//                // $data数组格式，里面有uid，表示向那个uid的页面推送数据
//                //$data = json_decode($buffer, true);
//                // 通过workerman，向uid的页面推送数据
//                $ret = $this->sendMessage($buffer);
//                // 返回推送结果
//                $connection->send($ret ? true : false);
//            };
//            $inner_text_worker->listen();
//        };


        // 4 processes
        if(PHP_OS !== 'WINNT') {
            $this->websoket->count = 4;
        }

        // Emitted when new connection come
        $this->websoket->onConnect = function ($connection) {
            //array_push($this->session, $connection);
            Yii::app()->session->set('websocket', $this->websocket->connects);
            echo "Congratulations, connect server successful! \n";
        };

        // Emitted when data received
        $this->websoket->onMessage = function ($connection, $data) {
            // Send hello
            if($data === 'new'){  //指定请求，会发送指定数据
                $info = ProductEngine::getDailyReportData();
                $data = json_encode($info);
            }
            $connection->send($data);
        };

        // Emitted when connection closed
        $this->websoket->onClose = function ($connection) {
            //array_diff($this->session, $connection);
            echo "Connection closed. \n";
        };
    }

    /**
     * workman websocket start
     */
    public function start()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'start';
        if ($this->daemon) {
            $argv[2] = '-d';
        }
        //var_dump($argv);exit;
        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket restart
     */
    public function restart()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'restart';
        if ($this->daemon) {
            $argv[2] = '-d';
        }

        if ($this->gracefully) {
            $argv[2] = '-g';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket stop
     */
    public function stop()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'stop';
        if ($this->gracefully) {
            $argv[2] = '-g';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket reload
     */
    public function reload()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'reload';
        if ($this->gracefully) {
            $argv[2] = '-g';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket status
     */
    public function status()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'status';
        if ($this->daemon) {
            $argv[2] = '-d';
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * workman websocket connections
     */
    public function connections()
    {
        $this->initWorker();
        // 重置参数以匹配Worker
        global $argv;
        $argv[0] = $argv[1];
        $argv[1] = 'connections';

        // Run worker
        Worker::runAll();
    }
}