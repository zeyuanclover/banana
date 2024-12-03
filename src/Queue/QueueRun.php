<?php

namespace Plantation\Banana\Queue;

use Plantation\Banana\Queue\Adapter\Redis;
use function Plantation\Banana\Functions\generateRandomString;
use function Plantation\Banana\Functions\config;
class QueueRun
{
    /**
     * @var Redis
     * 对象实例
     */
    protected $conn;

    /**
     * @param $conn
     * 构造函数
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return mixed
     * 是否启动
     */
    public function IsStarted($name){
        return $this->conn->IsStarted($name);
    }

    /**
     * @param $conn
     * @return self
     * 初始化
     */
    public static function instance($conn){
        $class = trim(strtolower(config('Queue.adapter')));
        if ($class=='redis') {
            return new self(new Redis($conn));
        }
    }

    /**
     * @param $queueName
     * @param $time
     * @return void
     * 停止任务
     */
    public function stop($queueName,$time=true){
        $this->conn->stop($queueName,$time);
    }

    /**
     * @param $queueName
     * @return void
     * 开始任务
     */
    public function start($queueName)
    {
        $this->conn->start($queueName);
    }

    /**
     * @param $id
     * @return int
     * 获得错误次数
     */
    public function getErrNumber($id=null)
    {
        return $this->conn->getErrNumber($id);
    }

    /**
     * @param $id
     * @return int
     * 执行次数
     */
    public function attemp($id=null)
    {
        return $this->conn->attemp($id);
    }

    /**
     * @param $id
     * @return mixed
     * 增加错误次数
     */
    public function addErrNumer($id=null)
    {
        return $this->conn->addErrNumer($id);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 再次入队列
     */
    public function reEnQueue($name=null,$data=null)
    {
        return $this->conn->reEnQueue($name,$data);
    }

    /**
     * @return void
     * 再入队列
     */
    public function rePush($name=null,$data=null)
    {
        return $this->conn->rePush($name,$data);
    }

    /**
     * @return int[]
     * 删除任务
     */
    public function delete($id=null)
    {
        return $this->conn->delete($id);
    }

    /**
     * @param $id
     * @return int[]
     * 任务已完成
     */
    public function complete($id=null)
    {
        return $this->conn->complete($id);
    }

    /**
     * @param $id
     * @return mixed
     * 获得任务状态
     */
    public function state($id=null)
    {
        return $this->conn->state($id);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 压入队列
     */
    public function enQueue($action,$name,$data,$errNumer=3)
    {
        return $this->conn->enQueue($action,$name,$data,$errNumer);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 压入队列
     */
    public function push($action,$name,$data,$errNumer=3)
    {
       return $this->conn->push($action,$name,$data,$errNumer);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function deQueue($name){
        return $this->conn->deQueue($name);
    }

    /**
     * @param $name
     * @return mixed
     * 消费队列
     */
    public function pull($name){
        return $this->conn->pull($name);
    }
}