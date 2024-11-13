<?php

namespace Plantation\Banana\Core;
use function Plantation\Banana\Functions\generateRandomString;

class Queue
{
    /**
     * @var
     * redis
     */
    public static $redis;

    /**
     * @param $conn
     * 构造函数
     */
    public function __construct($conn)
    {
        self::$redis = $conn;
    }

    /**
     * @param $conn
     * @return Queue
     * 初始化
     */
    public static function instance($conn)
    {
        return new Queue($conn);
    }

    /**
     * @param $id
     * @return int
     * 获得错误次数
     */
    public function getErrNumber($id)
    {
        return intval(self::$redis->get($id)*1);
    }

    /**
     * @param $id
     * @return mixed
     * 增加错误次数
     */
    public function addErrNumer($id)
    {
        $num = intval(self::$redis->get($id)*1)+1;
        self::$redis->set($id,$num);
        return self::$redis->expire($id,3600*24);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 再次入队列
     */
    public function reEnQueue($name,$data)
    {
        $data = json_encode($data);
        self::$redis->rpush($name,$data);
    }

    /**
     * @return int[]
     * 删除任务
     */
    public function delete($id)
    {
        self::$redis->set('state-'.$id,1);
        self::$redis->expire('state-'.$id,3600*24);
        return [
            'code'=>1
        ];
    }

    /**
     * @param $id
     * @return mixed
     * 获得任务状态
     */
    public function state($id)
    {
        return self::$redis->get('state-'.$id);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 压入队列
     */
    public function enQueue($action,$name,$data,$errNumer=3)
    {
        $data['qerrNumer'] = $errNumer;
        while($data['qid'] = sha1(generateRandomString(100).uniqid().time().microtime(true).json_encode($data).mt_rand(11111,9999999))){
            $t = 1000*3600*24;
            if(self::$redis->set('queueLock'.$data['qid'], '1', 'NX', 'PX', $t)){
                self::$redis->del('queueLock'.$data['qid']);
                break;
            }
        }

        $data['action'] = $action;
        $data = json_encode($data);
        self::$redis->rpush($name,$data);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 压入队列
     */
    public function push($action,$name,$data,$errNumer=3)
    {
        $data['qerrNumer'] = $errNumer;
        while($data['qid'] = sha1(generateRandomString(100).uniqid().time().microtime(true).json_encode($data).mt_rand(11111,9999999))){
            $t = 1000*3600*24;
            if(self::$redis->set('queueLock'.$data['qid'], '1', 'NX', 'PX', $t)){
                self::$redis->del('queueLock'.$data['qid']);
                break;
            }
        }

        $data['action'] = $action;
        $data = json_encode($data);
        self::$redis->rpush($name,$data);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function deQueue($name){
        return self::$redis->lpop($name);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function pull($name){
        return self::$redis->lpop($name);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function pop($name){
        return self::$redis->lpop($name);
    }
}