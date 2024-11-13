<?php

namespace Plantation\Banana\Core;

class Model
{
    /**
     * @var
     * 数据库连接变量
     */
    protected $conn;

    /**
     * @param $conn
     * 构造函数
     */
    public function __construct($conn){
        $this->conn = $conn;
    }

    /**
     * @param $db
     * @return mixed
     * db方法
     */
    public function db($db=null)
    {
        if ($db){
            $this->conn->db($db);
        }
        return $this->conn;
    }
}