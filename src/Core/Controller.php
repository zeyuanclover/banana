<?php

namespace Plantation\Banana\Core;
use http\Url;
use Plantation\Banana\Cache\Cache;
use Plantation\Banana\Cache\Drive\RedisSingle;
use Plantation\Banana\Cookie;
use Plantation\Banana\Request;
use function Plantation\Banana\Functions\file_lock;
use function Plantation\Banana\Functions\getGuLd;
use function Plantation\Banana\Functions\redis_lock;

class Controller
{
    /**
     * @var 容器
     */
    protected $container;

    /**
     * @var mixed
     * 公用配置
     */
    protected $publicConfig;

    /**
     * @var mixed
     * app 配置
     */
    protected $appConfig;

    /**
     * @var mixed
     * 控制器名称
     */
    protected $controller;

    /**
     * @var mixed
     * 方法名称
     */
    protected $action;

    /**
     * @var mixed
     * 变量
     */
    protected $vars;

    /**
     * @var
     * app 名称
     */
    protected $appName;

    /**
     * @var
     * 模板变量
     */
    protected $viewVars = [];

    /**
     * @var bool
     * html 缓存
     */
    protected $htmlCache = false;

    /**
     * @var
     * 环境变量
     */
    protected $env;

    /**
     * @var 表单令牌
     */
    protected $token;

    /**
     * @var Url
     */
    protected $u;

    /**
     * @param $data
     * @param $container
     * 构造函数
     */
    public function __construct($data,$container)
    {
        $this->u = $data['u'];
        $this->appName = $data['appName'];
        $this->publicConfig = $data['publicConfig'];
        $this->appConfig = $data['appConfig'];
        $this->controller = $data['controller'];
        $this->vars = $data['vars'];
        $this->action = $data['action'];
        $this->container = $container;
        $this->env = $data['env'];

        if(isset($data['cookieSwitch'])){
            if ($data['cookieSwitch']!='closed'){
                // 设置用户唯一编码
                if(!isset($_COOKIE['uid'])){
                    while($key = getGuLd('K',150)){
                        if(redis_lock($key,$this->getContainer('redis'))){
                            if(file_lock('lock'.DIRECTORY_SEPARATOR.'cookieuid',$key,'\Application\Admin\Lock\Test@run',['id'=>$key])){
                                break;
                            }
                        }
                    }
                    //echo $key;
                    Cookie::instance(false)->set('uid',$key);
                }
            }
        }
    }

    /**
     * @param $addition
     * @return mixed|string
     *
     * 媒体资源
     */
    public function media($addition=''){
        $url = trim($addition,'/');
        if ($url){
            $url ='/'.$url;
        }
        return Request::instance()->getUrl($this->u.$url);
    }

    /**
     * @return mixed|string
     * 获得当前链接
     */
    public function getCurrentUrl($addition=''){
        return Request::instance()->getCurrentUrl().$addition;
    }

    /**
     * @param $url
     * @return mixed|string
     * 获得url
     */
    public function getUrl($url=null)
    {
        if(is_array($url)){
            $url = '?'.http_build_query($url);
        }else{
            $url = trim($url,'/');
            if ($url){
                $url ='/'.$url;
            }
        }

        return Request::instance()->getUrl($this->u.$url);
    }

    /**
     * @param $name
     * @param $value
     * @return void
     * 注入模板变量
     */
    public function assign($name, $value){
        $this->viewVars[$name] = $value;
    }

    /**
     * @param $val
     * @return void
     * 网页缓存
     */
    public function htmlCache($val=true){
        $this->htmlCache = $val;
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 当前模板
     */
    public function thisFetch($data=[],$htmlCache=false){
        $c = $this->controller;
        $d = explode('\\', $c);
        if(isset($d[4])){
            $e = $d[4];
            $e = strtolower(str_replace('Controller','',$e));
        }

        $name = '.phtml';

        $this->fetch($e.DIRECTORY_SEPARATOR.$this->action.$name,$data,$htmlCache);
    }

    /**
     * @param $name
     * @param $data
     * @return void
     * 显示模板
     */
    public function fetch($name,$data=[],$htmlCache=false){
        //$data['token'] = $this->token();
        $_SERVER['theme'] = APP_PATH . DIRECTORY_SEPARATOR . ucfirst($this->appName)  . DIRECTORY_SEPARATOR.'View' . DIRECTORY_SEPARATOR . $this->appConfig['view']['theme'];
        $view = $this->getContainer('view');
        if ($htmlCache){
            $this->htmlCache = true;
        }

        extract($data);
        extract($this->viewVars);

        if(!isset($token)){
            $token = '';
        }

        $template = $view->fetch($name);

        if ($template){
            if($this->htmlCache){
                ob_start();
            }

            include $template;

            if($this->htmlCache){
                $dir = ROOT_PATH . DIRECTORY_SEPARATOR .'html'. DIRECTORY_SEPARATOR . ucfirst($this->appName)  . DIRECTORY_SEPARATOR.'View' . DIRECTORY_SEPARATOR . $this->appConfig['view']['theme'];

                $filename = str_replace('\\',DIRECTORY_SEPARATOR,$name);
                $filename = ltrim($filename,DIRECTORY_SEPARATOR);
                $filename = str_replace('.phtml','',$filename);
                $filename = ltrim($filename,DIRECTORY_SEPARATOR);
                $filename = ltrim($filename,'/');
                $s = pathinfo($filename);
                $p = $dir. DIRECTORY_SEPARATOR. $s['dirname'];
                $info = null;

                $path = $dir. DIRECTORY_SEPARATOR. $s['dirname'].DIRECTORY_SEPARATOR.$s['filename'] . '.html';
                $htmlContent = ob_get_clean();
                if(!is_dir($p)){
                    mkdir($p,0777,true);
                }
                $dir = null;
                $filename = null;
                file_put_contents($path,$htmlContent);
                echo $htmlContent;
            }
        }
    }

    /**
     * @param $name
     * @return void
     * 获得缓存
     */
    public function getHtmlCache($name){
        $dir = ROOT_PATH . DIRECTORY_SEPARATOR .'html'. DIRECTORY_SEPARATOR . ucfirst($this->appName)  . DIRECTORY_SEPARATOR.'View' . DIRECTORY_SEPARATOR . $this->appConfig['view']['theme'];

        $filename = str_replace('\\',DIRECTORY_SEPARATOR,$name);
        $filename = ltrim($filename,DIRECTORY_SEPARATOR);
        $filename = str_replace('.phtml','',$filename);
        $filename = str_replace('.html','',$filename);
        $filename = ltrim($filename,DIRECTORY_SEPARATOR);
        $filename = ltrim($filename,'/');
        $info = null;

        $path = $dir. DIRECTORY_SEPARATOR.$filename . '.html';
        if (is_file($path)){
            return file_get_contents($path);
        }else{
            return null;
        }
    }

    /**
     * @param $name
     * @return mixed
     * 容器
     */
    public function getContainer($name)
    {
        return $this->container->get($name);
    }

    /**
     * @return void
     * 表单令牌
     */
    public function createToken(){
        if(isset($_COOKIE['uid'])){
            $key = sha1($_COOKIE['uid'] . Request::instance()->getCurrentUrl());

            $token = getGuLd(200);
            Cache::instance(new RedisSingle(false,$this->getContainer('redis')))->set($key,$token,7200);
            //Cookie::instance(false)->set($key,$token,time() + 7200);
        }
        $this->assign('token',$token);
        return $this->token = $token;
    }

    /**
     * @param $token
     * @return bool
     * 验证表单令牌
     */
    public function verfyToken($token)
    {
        $key = sha1($_COOKIE['uid'] . Request::instance()->getCurrentUrl());
        $formtoken = Cache::instance(new RedisSingle(false,$this->getContainer('redis')))->get($key);
        if ($token === $formtoken) {
            Cache::instance(new RedisSingle(false,$this->getContainer('redis')))->delete($key);
            return true;
        }else{
            if(!Request::instance()->getRefferer()){
                return false;
            }

            $key = sha1($_COOKIE['uid'] . Request::instance()->getRefferer());
            $formtoken = Cache::instance(new RedisSingle(false,$this->getContainer('redis')))->get($key);
            if($token === $formtoken){
                Cache::instance(new RedisSingle(false,$this->getContainer('redis')))->delete($key);
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * @param $url
     * @return void
     * 跳转
     */
    public function direct($url){
        $url =Request::instance()->getUrl($url);
        header('Location:'.$url);
    }

    /**
     * @return mixed
     * pdo 类
     */
    public function pdo()
    {
        return $this->getContainer('pdo')->database();
    }
}