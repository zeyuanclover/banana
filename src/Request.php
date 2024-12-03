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
     * @var 是否安全模式
     */
    protected $secure;

    /**
     * 构造函数
     */
    public function __construct($secure=false){
        $this->url = $uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->secure = $secure;
    }

    /**
     * @return Request
     * 初始化
     */
    public static function instance($secure=false){
        return new Request($secure);
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
     * @return true|void
     * 是否put
     */
    public function isPut(){
        if ($this->method == 'PUT') {
            return true;
        }
    }

    /**
     * @return true|void
     * 是否delete
     */
    public function isDelete(){
        if ($this->method == 'DELETE') {
            return true;
        }
    }

    /**
     * @return true|void
     * 是否patch
     */
    public function isPatch(){
        if ($this->method == 'PATCH') {
            return true;
        }
    }

    /**
     * 是否head
     */
    public function isHead(){
        if ($this->method == 'HEAD') {
            return true;
        }
    }

    /**
     * @return true|void
     * 是否option
     */
    public function isOptions(){
        if ($this->method == 'OPTIONS') {
            return true;
        }
    }

    /**
     * @return true|void
     * 是否connect
     */
    public function isConnect(){
        if ($this->method == 'CONNECT') {
            return true;
        }
    }

    /**
     * @return true|void
     * 是否trace
     */
    public function isTrace()
    {
        if ($this->method == 'TRACE') {
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
     * @return false|string
     * 获得input
     */
    public function getInput()
    {
        return file_get_contents('php://input');
    }

    /**
     * @return mixed|string
     * 获取当前连接
     */
    public function getCurrentUrl($addition=null)
    {
        if (is_array($addition)) {
            $addition = '?'.http_build_query($addition);
        }
        return $this->getUrl($this->url.$addition);
    }

    /**
     * @return mixed
     * 获得上一次url
     */
    public function getRefferer()
    {
        return isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
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

        $addition = ltrim($addition, '/');

        // 构造完整的URL
        return $protocol . '://' . $host . $port .'/'. $addition;
    }

    /**
     * @return mixed
     * 获得主机
     */
    public function getDomain()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * @param $name
     * @return array|mixed
     * get
     */
    public function get($name=null)
    {
        if($this->secure==true){
            foreach ($_GET as $key => $value) {
                $_GET[$key] = strip_tags($value);
            }
        }

        if ($name === null) {
            return $_GET;
        }else{
            if (isset($_GET[$name])) {
                return $_GET[$name];
            }else{
                return null;
            }
        }
    }

    /**
     * @param $name
     * @return array|mixed
     * post
     */
    public function post($name=null)
    {
        if($this->secure==true){
            foreach ($_POST as $key => $value) {
                $_POST[$key] = strip_tags($value);
            }
        }

        if ($name === null) {
            return $_POST;
        }else{
            if (isset($_POST[$name])) {
                return $_POST[$name];
            }else{
                return null;
            }
        }
    }

    /**
     * @return false|string
     * 获得input
     */
    public function input()
    {
        return file_get_contents('php://input');
    }

    /**
     * @param $hname
     * @return array|false|mixed|null
     * 获取头
     */
    public function getHeaders($hname=null)
    {
        $headers = getallheaders();
        if($hname){
            foreach ($headers as $name => $value) {
                if ($name==$hname) {
                    return $value;
                }
            }
            return null;
        }else{
            return $headers;
        }
    }
}