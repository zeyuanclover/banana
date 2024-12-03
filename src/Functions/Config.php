<?php
namespace Plantation\Banana\Functions;

// 配置
function config($key, $default = null,$configs=[],$keys=[],$sysConfigs=[]){

    static $config;
    // 配置
    if(is_array($configs)){
        if (count($configs)==0){
            $configs = $_SERVER['config'];
            $sysConfigs = $_SERVER['sys_config'];
        }
    }

    // 数组处理与配置降级
    if(count($keys)==0){
        $key = explode('.', $key);
        $k = current($key);
        if(is_array($configs)){
            $configs = $_SERVER['config'];
            if(!isset($configs[$k])){
                $configs = $_SERVER['sys_config'];
            }
        }
    }else{
        $key = $keys;
    }

    // 读取配置
    foreach ($key as $k) {
        if(isset($configs[$k])){
            if(isset($sysConfigs[$k])){
                $sysConfigs = $sysConfigs[$k];
            }

            $s = next($key);
            array_shift($key);
            $config = $configs[$k];

            if(!$s){
                return $configs[$k];
            }
            return config($s,$default,$configs[$k],$key,$sysConfigs);
        }else{
            if(isset($sysConfigs[$k])){
                return $sysConfigs[$k];
            }else{
                return $default;
            }
        }
    }
}

