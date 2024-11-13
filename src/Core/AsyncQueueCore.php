<?php

namespace Plantation\Banana\Core;
use FastRoute\Dispatcher;
use Plantation\Banana\Cache\Cache;
use Plantation\Banana\Core\Queue;
use function Plantation\Banana\Functions\getFilesConfigInDirectory;

class AsyncQueueCore
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

    //php plantation queue:listen --queue register \\Application\\Queue\\Controller\\LoginController@index

    /**
     * @return void
     * 框架运行方法
     */
    public function run($args)
    {
        set_time_limit(0);
        $cache = new Cache(new $this->env['SystemCacheDrive']('cache'));

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
        if($args['1']=='queue:listen' && $args['2']=='--queue'){
            $appName = 'Queue';
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

        if (isset($args[4])) {
            $handle = $args[4];
            $c = explode('@', $args[4]);
            $controller = $a = $c[0];
            $action = $b = $c[1];
        }else{
            $controller = $a = '';
            $action = $b = '';
        }

        $data = [
            'controller' => $a,
            'action' => $b,
            'appName' => $appName,
            'vars' => $args,
            'env' => $this->env,
            'publicConfig' => $configs,
            'appConfig' => $appConfigs,
        ];

        define('APP_DEBUG', $this->env['debug']);

        $_SERVER['config'] = $appConfigs;
        $_SERVER['sys_config'] = $configs;

        $queue = new Queue($container->get('redis'));
        while (true) {
            $jobData = $queue->deQueue($args['3']);

            if(!$jobData){
                continue;
            }

            $jobData = json_decode($jobData, true);

            if (isset($jobData['action'])&&$jobData['action']) {
                $c = explode('@', $jobData['action']);
                $controller = $a = $c[0];
                $action = $b = $c[1];
                $data['controller'] = $controller;
                $data['action'] = $action;
            }

            $obj = new $controller($data, $container);
            if (method_exists($obj, $b)) {
                if ($jobData) {
                    // 处理任务
                    $rs = $obj->$action($queue, $jobData);
                    if (!isset($rs['code']) || ($rs['code'] > 5)) {
                        if ($queue->state($jobData['qid']) != 1) {
                            if ($queue->getErrNumber($jobData['qid']) < $jobData['qerrNumer']) {
                                $queue->addErrNumer($jobData['qid']);
                                $queue->reEnQueue($args['3'], $jobData);
                            }
                        }
                    }
                } else {
                    // 没有任务，休眠一会儿
                    sleep(1);
                }
            }
        }

        # 注销变量
        unset($obj);
        unset($cache);
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
                getFilesInDirectory($path.DIRECTORY_SEPARATOR.$value);
            }else{
                if($value!='.DS_Store'){
                    $arr_file[] = $path.DIRECTORY_SEPARATOR.$value;
                }
            }
        }
        return $arr_file;
    }
}