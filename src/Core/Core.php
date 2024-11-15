<?php

namespace Plantation\Banana\Core;
use FastRoute\Dispatcher;
use Plantation\Banana\Cache\Cache;
use function Plantation\Banana\Functions\getFilesConfigInDirectory;
class Core
{
    /**
     * @var array|false
     * 环境变量
     */
    private $env = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $requiredVersion = '8.3.0';
        if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
            exit("你的PHP版本过低。需要至少 $requiredVersion ，当前版本为 " . PHP_VERSION . "。请升级你的PHP版本。");
        }

        #框架路径
        define('FRAMEWORK_PATH', dirname(__DIR__));

        #app 路径
        define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR .'Application' . DIRECTORY_SEPARATOR . 'Src');

        #载入env
        $envPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'env.ini';
        if (is_file($envPath)){
            $this->env = parse_ini_file($envPath);
        }
        $envPath = null;
        if(isset($this->env['timezone'])){
            date_default_timezone_set($this->env['timezone']);
        }
    }

    /**
     * @return void
     * 框架运行方法
     */
    public function run()
    {
        $cache = new Cache(new $this->env['SystemCacheDrive'](false,'cache'));

        #载入方法
        if($this->env['cache']){
            $data = $cache->get('functionsPath');
            if ($data){
                $functionsPath = $data;
                unset($data);
            }else{
                $functionsPath = $this->getFilesInDirectory(FRAMEWORK_PATH . DIRECTORY_SEPARATOR . 'Functions');
                $cache->set('functionsPath', $functionsPath);
            }
        }else{
            $functionsPath = $this->getFilesInDirectory(FRAMEWORK_PATH . DIRECTORY_SEPARATOR . 'Functions');
        }

        foreach ($functionsPath as $file){
            if(is_file($file)){
                include ($file);
            }
        }
        unset($functionsPath);

        #载入公用配置
        if($this->env['cache']){
            $data = $cache->get('publicConfig');
            if ($data){
                $configs = $data;
                unset($data);
            }else{
                $configs = getFilesConfigInDirectory(APP_PATH . DIRECTORY_SEPARATOR . 'Config');
                $cache->set('publicConfig', $configs);
            }
        }else{
            $configs = getFilesConfigInDirectory(APP_PATH . DIRECTORY_SEPARATOR . 'Config');
        }

        #确定应用
        $appName = '';
        $url = $_SERVER['REQUEST_URI'];
        if($url=='/'){
            $appName = 'Home';
        }else{
            $appName = explode('/', $url);
            if (isset($configs['Web']['application'][$appName[1]]['name'])){
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
        $hostParts = null;
        if ($subDomain!='www'){
            if (isset($configs['SubDomain']['application'][$subDomain]['name'])){
                $appName = $configs['SubDomain']['application'][$subDomain]['name'];
            }
        }

        # 载入应用配置
        if($this->env['cache']){
            $data = $cache->get('appConfig');
            if ($data){
                $appConfigs = $data;
                unset($data);
            }else{
                $appConfigs = getFilesConfigInDirectory(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR . 'Config');
                $cache->set('appConfig', $appConfigs);
            }
        }else{
            $appConfigs = getFilesConfigInDirectory(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR . 'Config');
        }

        if(count($appConfigs)==0){
            $appConfigs = $configs;
        }

        # 获得容器
        if (is_file(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Container.php')){
            include (APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Container.php');
        }

        # 载入route
        if (is_file(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php')){
            include (APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Route.php');
        }

        if(!isset($dispatcher)){
            echo '没有'.$appName.'模块！,请创建！在'.__METHOD__.__LINE__.'行';
            exit;
        }

        # 路由
        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars

                # 定位类
                $controller = '';
                $hs = explode('@', $handler);
                if($handler[0]=='\\'){
                    $controller = $hs[0];
                }else{
                    if (isset($hs[0])){
                        $controller = '\\Application\\'.ucfirst($appName).'\\Controller\\'.$hs[0];
                    }
                }

                $action = 'index';
                if (isset($hs[1])){
                    $action = $hs[1];
                }

                $data = [
                    'controller' => $controller,
                    'action' => $action,
                    'appName' => $appName,
                    'vars' => $vars,
                    'env'=>$this->env,
                    'publicConfig' => $configs,
                    'appConfig' => $appConfigs,
                ];

                $_SERVER['config'] = $appConfigs;
                $_SERVER['sys_config'] = $configs;

                define('APP_DEBUG',$this->env['debug']);

                $obj = new $controller($data,$container);

                unset($data);
                unset($configs);
                unset($appConfigs);

                if (method_exists($controller, $action)){
                    $obj->$action($vars);
                }
                break;
        }

        # 注销变量
        unset($obj);
        unset($cache);
        unset($dispatcher);
        unset($container);
        unset($controller);
        unset($action);
        $this->env = null;
    }

    /**
     * @param $path
     * @return array
     * 获得某个目录内所有文件方法ß
     */
    function getFilesInDirectory($path){
        //列出目录下的文件或目录
        $fetchdir = scandir($path);
        sort($fetchdir);
        static $arr_file = array();
        foreach ($fetchdir as $key => $value) {
            if($value == "." || $value == ".."){
                continue;
            }
            if(is_dir($path.DIRECTORY_SEPARATOR.$value)){
                $this->getFilesInDirectory($path.DIRECTORY_SEPARATOR.$value);
            }else{
                if($value!='.DS_Store'){
                    $arr_file[] = $path.DIRECTORY_SEPARATOR.$value;
                }
            }
        }
        return $arr_file;
    }
}