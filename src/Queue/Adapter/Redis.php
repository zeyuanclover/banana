<?php

namespace Plantation\Banana\Queue\Adapter;
use function Plantation\Banana\Functions\generateRandomString;

class Redis
{
    /**
     * @var
     * redis
     */
    public static $redis;

    /**
     * @var 队列名称
     */
    protected static $name;

    /**
     * @var 队列数据
     */
    protected static $data;

    /**
     * @var 队列id
     */
    protected static $qid;

    /*
     * 是否停止队列
     */
    protected $stop;

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
        return new \Redis($conn);
    }

    /**
     * @return void
     * 停止
     */
    public function stop($name)
    {
        return self::$redis->set('queue-started-'.$name,0);
    }

    /**
     * @return void
     * 启动
     */
    public function start($name)
    {
        return self::$redis->set('queue-started-'.$name,1);
    }

    /**
     * @return mixed
     * 是否停止
     */
    public function IsStarted($name)
    {
        $v = self::$redis->get('queue-started-'.$name);

        if($v==0){
            return 0;
        }
        return 1;
    }

    /**
     * @param $id
     * @return int
     * 获得错误次数
     */
    public function getErrNumber($id=null)
    {
        if(!$id){
            $id = self::$qid;
        }
        return intval(self::$redis->get($id)*1);
    }

    /**
     * @param $id
     * @return int
     * 执行次数
     */
    public function attemp($id=null)
    {
        if(!$id){
            $id = self::$qid;
        }
        return intval(self::$redis->get($id)*1);
    }

    /**
     * @param $id
     * @return mixed
     * 增加错误次数
     */
    public function addErrNumer($id=null)
    {
        if(!$id){
            $id = self::$qid;
        }

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
    public function reEnQueue($name=null,$data=null)
    {
        if(!$name){
            $name = self::$name;
        }
        if(!$data){
            $data = self::$data;
        }
        $data = json_encode($data);
        self::$redis->rpush($name,$data);
    }

    /**
     * @return void
     * 再入队列
     */
    public function rePush($name=null,$data=null)
    {
        if(!$name){
            $name = self::$name;
        }
        if(!$data){
            $data = self::$data;
        }
        self::$redis->rpush($name,$data);
    }

    /**
     * @return int[]
     * 删除任务
     */
    public function delete($id=null)
    {
        if(!$id){
            $id = self::$qid;
        }
        self::$redis->set('state-'.$id,1);
        self::$redis->expire('state-'.$id,3600*24);
        return [
            'code'=>1
        ];
    }

    /**
     * @param $id
     * @return int[]
     * 任务已完成
     */
    public function complete($id=null)
    {
        if(!$id){
            $id = self::$qid;
        }
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
    public function state($id=null)
    {
        if(!$id){
            $id = self::$qid;
        }
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
            $t = 1000*3600;
            if(self::$redis->set('queueLock'.$data['qid'], '1', 'NX', 'PX', $t)){
                //self::$redis->del('queueLock'.$data['qid']);
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
        $this->enQueue($action,$name,$data,$errNumer);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function deQueue($name){
        $data = self::$redis->lpop($name);
        if($data){
            self::$data = $data;
            $c = json_decode($data,true);
            self::$qid = $c['qid'];
            self::$name = $name;
            return $data;
        }
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function pull($name){
        $this->deQueue($name);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function pop($name){
       $this->deQueue($name);
    }
}