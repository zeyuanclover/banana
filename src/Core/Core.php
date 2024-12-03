<?php

namespace Plantation\Banana\Core;
use FastRoute\Dispatcher;
use Plantation\Banana\Cache\Cache;
use function Plantation\Banana\Functions\getFilesConfigInDirectory;
class Core extends Crystal
{
    /**
     * @var
     * app config
     */
    protected $appConfigs;

    /**
     * @var
     * 共用配置
     */
    protected $configs;

    /*
     * u
     *
     */
    protected $u;

    /**
     * @param $configs
     * @param $myAppName
     * @return array
     * 获得qpp名称
     */
    public function getApp($configs,$myAppName=null){
        $u = '/';

        #确定应用
        $appName = '';
        $url = trim(strip_tags($_SERVER['REQUEST_URI']));
        if($url=='/'){
            $appName = 'Home';
        }else{
            $appName = explode('/', $url);
            if (isset($configs['Web']['application'][$appName[1]]['name'])){
                $u = $appName[1];
                $appName = $configs['Web']['application'][$appName[1]]['name'];
            }else{
                $appName = 'Home';
            }
        }

        # 获取子域名 - 优先级较高
        $subDomain = '';
        $host = $_SERVER['HTTP_HOST'];
        $hostParts = explode('.', $host);
        if (count($hostParts) > 2) {
            $subDomain = $hostParts[0];
        }

        #子域名确定app名称
        $hostParts = null;
        if ($subDomain!='www'){
            if (isset($configs['SubDomain']['application'][$subDomain]['name'])){
                $appName = $configs['SubDomain']['application'][$subDomain]['name'];
            }
        }

        // 手动指定app名称
        if($myAppName){
            $appName = $myAppName;
        }

        $this->u = $u;
        return ['appName'=>$appName,'u'=>$u];
    }

    /**
     * @param $appName
     * @return mixed
     * @throws \Exception
     * 载入route
     */
    protected function loadRoutes($appName,$container){
        # 载入route
        if (is_file(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php')){
            $dispatcher = include (APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php');
        }else{
            throw new \Exception(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php 文件缺失！');
            exit();
        }

        # 没有载入route
        if(!isset($dispatcher)){
            throw new \Exception( PP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php >>> $dispatcher 变量缺失！');
            exit();
        }

        return $dispatcher;
    }

    /**
     * @param $dispatcher
     * @param $appName
     * @return void
     * 路遇方法
     */
    protected function route($dispatcher,$appName,$container){
        # 路由
        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = trim(strip_tags($_SERVER['REQUEST_URI']));

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                MyException::instance('链接未配置,请检查 >>> '.APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php',404,null,true);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                MyException::instance('请求类型错误 >>> '.APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php',405,null,true);
                // ... 405 Method Not Allowed
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars

                $this->dispath($handler,$vars,$appName,$container);
                break;
        }
    }

    /**
     * @param $handler
     * @param $vars
     * @param $appName
     * @return void
     * 实例
     */
    public function dispath($handler,$vars,$appName,$container){
        # 定位类
        $controller = '';
        $hs = explode('@', $handler);
        if($handler[0]=='\\'){
            $controller = $hs[0];
        }else{
            if (isset($hs[0])){
                $controller = '\\Application\\'.ucfirst($appName).'\\Controller\\'.$hs[0];
            }else{
                MyException::instance('本次请求，未指定控制器名称或控制器名称有误！>>> '.$handler,201,null,true);
                exit();
            }
        }

        # 定位方法
        $action = 'index';
        if (isset($hs[1]) && $hs[1]){
            $action = $hs[1];
        }

        $hs = null;

        # 数据
        $data = [
            'u'=>$this->u,
            'controller' => $controller,
            'action' => $action,
            'appName' => $appName,
            'vars' => $vars,
            'env'=>$this->env,
            'publicConfig' => $this->configs,
            'appConfig' => $this->appConfigs,
        ];

        $u = null;

        # 初始化类
        if(!class_exists($controller)){
            MyException::instance($controller.' >>> 类不存在！',202,null,true);
            exit();
        }

        $obj = new $controller($data,$container);

        unset($data);
        unset($configs);
        unset($appConfigs);
        unset($container);
        $this->env = null;
        $this->configs = [];
        $this->appConfigs = [];
        $this->u = null;

        # 定位方法
        if (method_exists($controller, $action)){
            if(is_callable([$obj, $action])){
                $obj->$action($vars);
            }else{
                MyException::instance($controller.' >>> 类方法'.$action.'($vars) 非可访问状态，请设置!',203,null,true);
                exit();
            }
        }else{
            MyException::instance($controller.' >>> 类方法'.$action.'($vars) 不存在!',204,null,true);
            exit();
        }

        $vars = null;
        $controller = null;
        $action = null;
    }

    /**
     * @return void
     * 框架运行方法
     */
    public function run($myAppName=null)
    {
        # 定义缓存对象
        $cache = new Cache(new $this->env['SystemCacheDrive'](false,'cache'));

        # debug
        $debug = false;
        error_reporting(0);
        if((isset($this->env['debug']) && $this->env['debug']==true) || (isset($_REQUEST['is_debug']) && $_REQUEST['is_debug']==true)){
            $debug = true;
            error_reporting(E_ALL);

            if($_SERVER['REQUEST_METHOD'] == 'GET'){
                $whoops = new \Whoops\Run;
                $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
                $whoops->register();
            }
        }

        define('APP_DEBUG', $debug);
        $debug = null;

        # 加载共用方法
        $functionsPath = $this->loadFunctions($cache);

        #包含方法
        foreach ($functionsPath as $file){
            if(is_file($file)){
                include ($file);
            }
        }

        # 加载共用配置
        $this->configs = $configs = $this->loadPublicConigs($cache);

        # 获得app name
        $data = $this->getApp($configs,$myAppName);

        $appName = $data['appName'];
        $u = $data['u'];

        $data = null;

        # app 为空
        if(!$appName){
            throw new \Exception('app 名称为空，请检查链接参数！');
            exit();
        }

        #app缓存
        $cache = null;
        $cache = new Cache(new $this->env['SystemCacheDrive'](false,'cache'.DIRECTORY_SEPARATOR.ucfirst($appName)));

        # 获得app专用配置
        $this->appConfigs = $appConfigs = $this->getAppConfig($cache,$appName);

        #定义server数组
        $_SERVER['config'] = $appConfigs;
        $_SERVER['sys_config'] = $configs;

        # app 相关常量
        define('APP_NAME', ucfirst($appName));
        define('CURRENT_APP_PATH', APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR);

        # 载入容器
        $container = $this->loadContainer($appName);

        # 载入路由
        $dispatcher = $this->loadRoutes($appName,$container);

        # 路由方法
        $this->route($dispatcher,$appName,$container);

        # 注销变量
        unset($cache);
        unset($dispatcher);
        unset($container);
    }
}