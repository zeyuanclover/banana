<?php

namespace Plantation\Banana\Queue;
use FastRoute\Dispatcher;
use Plantation\Banana\Cache\Cache;
use Plantation\Banana\Core\Crystal;
use Plantation\Banana\Queue\QueueRun;
use function Plantation\Banana\Functions\getFilesConfigInDirectory;

class Consume extends Crystal
{
    //php plantation queue:listen --queue register \\Application\\Queue\\Controller\\LoginController@index
    protected $appConfigs;

    /**
     * @return void
     * 框架运行方法
     */
    public function run($args)
    {
        set_time_limit(0);
        $cache = new Cache(new $this->env['SystemCacheDrive'](false,'cache'));

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

        #确定应用
        if($args['1']=='queue:listen'){
            $appName = ucfirst(trim(str_replace('--','',$args['2'])));
            //$appName = 'Queue';
        }

        if(!isset($args['3'])){
            exit('请填写任务名称!');
        }

        # app 为空
        if(!$appName){
            exit('app 名称为空，请检查脚本参数！');
        }

        $cache = new Cache(new $this->env['SystemCacheDrive'](false,'cache'.DIRECTORY_SEPARATOR.ucfirst($appName)));

        # 获得app专用配置
        $this->appConfigs = $appConfigs = $this->getAppConfig($cache,$appName);

        $_SERVER['config'] = $appConfigs;
        $_SERVER['sys_config'] = $configs;

        # 载入容器
        $container = $this->loadContainer($appName);

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
            'u'=>$appName,
            'controller' => $a,
            'action' => $b,
            'appName' => $appName,
            'vars' => $args,
            'env' => $this->env,
            'publicConfig' => $configs,
            'appConfig' => $appConfigs,
            'cookieSwitch'=>'closed'
        ];

        define('APP_DEBUG', $this->env['debug']);

        $queue = QueueRun::instance($container->get('redis'));
        while (true) {
            if(!$queue->IsStarted($args['3'])){
                continue;
            }

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

            # 初始化类
            if(!class_exists($controller)){
                exit($controller.' >>> 类不存在！');
            }

            $obj = new $controller($data, $container);
            if (method_exists($obj, $b)) {
                if ($jobData) {
                    // 处理任务
                    # 定位方法
                    if(is_callable([$obj, $action])){
                        $rs = $obj->$action($queue,$jobData);
                    }else{
                        exit($controller.' >>> 类方法'.$action.'($vars) 非可访问状态，请设置!');
                    }

                    if (!isset($rs['code']) || ($rs['code'] > 15)) {
                        if ($queue->state($jobData['qid']) != 1) {
                            if ($queue->getErrNumber($jobData['qid']) < $jobData['qerrNumer']) {
                                $queue->addErrNumer($jobData['qid']);
                                $queue->reEnQueue($args['3'], $jobData);
                            }
                        }
                    }else{
                        if($rs['code']==1){
                            $datetime = new \DateTime();
                            $t = $datetime->format('Y-m-d H:i:s.u');
                            echo "OK >>> 执行了一个队列任务[".$t."]\n";
                            $queue->complete($jobData['qid']);
                        }
                    }
                } else {
                    // 没有任务，休眠一会儿
                    //sleep(1);
                }
            }else{
                exit($controller.' >>> 类方法'.$action.'($vars) 不存在!');
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
}