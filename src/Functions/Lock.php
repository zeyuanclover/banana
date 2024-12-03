<?php
namespace Plantation\Banana\Functions;

/**
 * @param $directory
 * @param $filename
 * @param $class
 * @param $data
 * @return bool|mixed
 * 文件锁
 */
function file_lock($directory,$filename,$class,$data=[])
{
    $directory = ltrim($directory,'/');
    $directory = rtrim($directory,'/');

    $directory = ltrim($directory,DIRECTORY_SEPARATOR);
    $directory = rtrim($directory,DIRECTORY_SEPARATOR);

    $dir = ROOT_PATH . DIRECTORY_SEPARATOR . $directory;
    if(!is_dir($dir)){
        mkdir($dir,0777,true);
    }

    $content = '';
    $file = ROOT_PATH . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $filename.'.lock';
    if(is_file($file)){
        $content = file_get_contents($file);
    }else{
        file_put_contents($file,0);
    }

    $fp = fopen($file, 'w');
    $rs = false;

    if ($fp) {
        // 尝试获取独占锁
        if (flock($fp, LOCK_EX)) {  // 获取排它锁
            //var_dump($content);
            if ($content != 'ok') {

                if(strpos($class,'@')){
                    $c = explode('@',$class);
                    $class = $c[0];
                    $action = $c[1];
                }else{
                    $action = 'run';
                }

                $obj = new $class();
                if(method_exists($obj,$action)){
                    $rs = $obj->$action($data);
                    if(isset($rs['state'])&&$rs['state']===true){
                        fwrite($fp, 'ok');
                    }else{
                        fwrite($fp, '-1');
                    }
                }else{
                    fwrite($fp, '-2');
                }
            }else{
                fwrite($fp, 'ok');
            }

            // 解锁
            flock($fp, LOCK_UN);
        }

        // 关闭文件
        fclose($fp);
    }

    return $rs;
}

/**
 * @param $key
 * @param $obj
 * @param $ttl
 * @return bool
 * redis 互斥锁 毫秒级
 */
function redis_lock($key,$obj,$ttl=5000){
    $rs = $obj->set($key, 1, 'NX', 'PX', $ttl);
    if($rs){
        return true;
    }else{
        return false;
    }
}