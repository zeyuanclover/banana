<?php

namespace Plantation\Banana\Core;

class Model
{
    /**
     * @var
     * 数据库连接变量
     */
    protected static $conn;

    /**
     * @param $conn
     * 构造函数
     */
    public function __construct($conn=null){
        self::$conn = $conn;
    }

    /**
     * @param $db
     * @return mixed
     * db方法
     */
    public function database($name='default')
    {
        if ($name){
            return self::$conn->database($name);
        }
    }

    /**
     * @param $data
     * @return mixed
     * 写入日志
     */
    public function log($data){
        return $this->database()->insert('log', $data);
    }

    /**
     * @param $data
     * @return mixed
     * 后台日志
     */
    public function adminLog($data){
        return $this->database()->insert('administrator_log', $data);
    }
}