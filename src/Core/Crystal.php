<?php

namespace Plantation\Banana\Core;

use function Plantation\Banana\Functions\getFilesConfigInDirectory;

abstract class Crystal
{
    /**
     * @var array|false
     * 环境变量
     */
    protected $env = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        # 检查php 版本
        $this->checkPhpVersion();

        # 框架路径
        define('FRAMEWORK_PATH', dirname(__DIR__));

        #app 路径
        define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR .'Application' . DIRECTORY_SEPARATOR . 'Src');

        # 再入env文件配置
        $this->loadEnv();

        #设置时区
        $this->setTimeZone();
    }

    /**
     * @param $zone
     * @return void
     * 设置时区
     */
    public function setTimeZone($zone=null){
        # 设置时区
        if (!$zone){
            if(isset($this->env['timezone'])) {
                $zone = $this->env['timezone'];
            }else {
                throw new \Exception('请设置时区：在'.ROOT_PATH . DIRECTORY_SEPARATOR . 'env.ini. 文件中或者在'.__METHOD__.'中指定！');
                exit();
            }
        }

        date_default_timezone_set($zone);
        $zone = null;
    }

    /**
     * @return void
     * 检查php版本
     */
    protected function checkPhpVersion()
    {
        #检查php版本
        $requiredVersion = '8.3.0';
        if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
            throw new \Exception("你的PHP版本过低。需要至少 $requiredVersion ，当前版本为 " . PHP_VERSION . "。请升级你的PHP版本。");
            exit();
        }
        $requiredVersion = null;
    }

    /**
     * @return void
     * 再入env文件配置
     */
    protected function loadEnv()
    {
        #载入env
        $envPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'env.ini';
        if (is_file($envPath)){
            $this->env = parse_ini_file($envPath);
        }else{
            throw new \Exception(ROOT_PATH.'/evi.ini文件缺失！');
            exit();
        }
        $envPath = null;
    }

    /**
     * @param $cache
     * @return array
     * 加载共用方法
     */
    protected function loadFunctions($cache){
        $functionsPath = [];
        #载入方法
        if(is_dir(FRAMEWORK_PATH . DIRECTORY_SEPARATOR . 'Functions')){
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
        }
        return $functionsPath;
    }

    /**
     * @param $cache
     * @return array
     * 加载共用配置
     */
    protected function loadPublicConigs($cache){
        #载入公用配置
        $configs = [];
        if(is_dir(APP_PATH . DIRECTORY_SEPARATOR . 'Config')){
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
        }
        return $configs;
    }

    /**
     * @param $cache
     * @param $appName
     * @return array
     * 获得app专用配置
     */
    protected function getAppConfig($cache,$appName)
    {
        # 载入应用配置
        $appConfigs = [];
        if(is_dir(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR . 'Config')){
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
        }
        return $appConfigs;
    }

    /**
     * @param $appName
     * @return mixed|null
     * @throws \Exception
     * 载入容器
     */
    protected function loadContainer($appName){
        # 获得容器
        if (is_file(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Container.php')){
            $container = include (APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Container.php');
        }else{
            throw new \Exception(APP_PATH . DIRECTORY_SEPARATOR . ucfirst($appName) . DIRECTORY_SEPARATOR  . 'Container.php 文件缺失！');
            exit();
        }

        if(!isset($container)){
            $container = null;
        }

        return $container;
    }


    protected function run($myAppName){

    }

    /**
     * @param $path
     * @return array
     * 获得某个目录内所有文件方法ß
     */
    protected function getFilesInDirectory($path){
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