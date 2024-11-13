<?php

namespace Plantation\Banana;

class Request
{
    /**
     * @var mixed
     * url
     */
    protected $url;

    /**
     * @var mixed
     * 方法
     */
    protected $method;

    /**
     * 构造函数
     */
    public function __construct(){
        $this->url = $uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return Request
     * 初始化
     */
    public static function instance(){
        return new Request();
    }

    /**
     * @return mixed
     * 获得请求方法
     */
    public function getMethod(){
        return $this->method;
    }

    /**
     * @return true|void
     * 是否get请求
     */
    public function isGet()
    {
        if ($this->method == 'GET') {
            return true;
        }
    }

    /**
     * @return true|void
     * 是否post请求
     */
    public function isPost(){
        if ($this->method == 'POST') {
            return true;
        }
    }

    /**
     * @return bool
     * 是否ajax 请求
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @return mixed
     * 获得请求链接
     */
    public function getUrl($addition=''){
        // 获取协议
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';

        // 获取主机名
        $host = $_SERVER['HTTP_HOST'];

        // 获取端口（如果是80或443，则不包含在主机名中）
        $port = $_SERVER['SERVER_PORT'];
        $port = ($protocol === 'http' && $port === 80 || $protocol === 'https' && $port === 443) ? '' : ':' . $port;

        // 获取路径
        $path = $_SERVER['REQUEST_URI'];

        if($port==':80'||$port==':443'){
            $port = '';
        }

        // 构造完整的URL
        return $protocol . '://' . $host . $port . $this->url . $addition;
    }
}