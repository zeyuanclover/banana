<?php

namespace Plantation\Banana\Cache\Drive;

use Plantation\Banana\Safe\Certificate;

class File
{
    /**
     * @var mixed|string
     * 缓存路径
     */
    private $path;

    /**
     * @var string
     * 保存路径
     */
    private $saveDirectory;

    private $safe=false;

    private $permPath;

    /**
     * @param $relativePath
     * 构造函数
     */
    public function __construct($safe,$relativePath='',$path=[]){
        $this->safe = $safe;
        $this->path = trim($relativePath,'/');
        $this->path = trim($relativePath,DIRECTORY_SEPARATOR);

        $this->saveDirectory = ROOT_PATH . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR;

        if(count($path)>0){
            $this->permPath = $path;
        }else{
            $this->permPath = [
                'private'=>ROOT_PATH.DIRECTORY_SEPARATOR.'perm'.DIRECTORY_SEPARATOR.'private.perm',
                'public'=>ROOT_PATH.DIRECTORY_SEPARATOR.'perm'.DIRECTORY_SEPARATOR.'public.perm'
            ];
        }
    }

    /**
     * @param $safe
     * @param $relativePath
     * @return File
     *
     */
    public static function instance($safe,$relativePath='',$path=[])
    {
        return new File($safe,$relativePath,$path );
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     * @return true
     * 设置缓存
     */
    public function set($key, $value,$expire = true){
        $saveDir = $this->saveDirectory;
        if(!is_dir($saveDir)){
            mkdir($saveDir,0777, true);
        }
        $savePath = $saveDir . $key . '.php';

        if($this->safe==true){
            if(is_array($value)){
                $value = json_encode($value);
            }
            $obj = new Certificate($this->permPath['private'],$this->permPath['public']);
            $value = $obj->publicEncrypt($value);
        }

        $data = [];
        $data['content'] = $value;
        $data['expire'] = $expire;
        if($expire!==true){
            $data['expire'] += time();
        }

        file_put_contents($savePath,"<?php \n return ".var_export($data,true).";");
        return true;
    }

    /**
     * @param $key
     * @return mixed|null
     * 获得缓存
     */
    public function get($key){
        $saveDir = $this->saveDirectory;
        $savePath = $saveDir . $key . '.php';
        if(is_dir($saveDir)){
            if (is_file($savePath)) {
                $data = include $savePath;
                if ($data['expire']===true||$data['expire']>time()) {
                    if($this->safe==true){
                        $obj = new Certificate($this->permPath['private'],$this->permPath['public']);
                        $value = $obj->privDecrypt($data['content']);
                        if (json_validate($value)){
                            return json_decode($value,true);
                        }
                        return $value;
                    }else{
                        return $data['content'];
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param $key
     * @return bool
     * 删除缓存
     */
    public function delete($key){
        $saveDir = $this->saveDirectory;
        $savePath = $saveDir . $key . '.php';
        if(is_file($savePath)){
            unlink($savePath);
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $key
     * @param $expire
     * @return bool
     * 缓存过期时间
     */
    public function expire($key,$expire){
        $saveDir = $this->saveDirectory;
        $savePath = $saveDir . $key . '.php';
        if(is_dir($saveDir)){
            if (is_file($savePath)) {
                $data = include $savePath;
                $this->set($key,$data,$expire);
                return true;
            }
        }
        return false;
    }

    /**
     * @param $key
     * @return int|mixed|true|null
     * 剩余时间
     */
    public function ttl($key){
        $saveDir = $this->saveDirectory;
        $savePath = $saveDir . $key . '.php';
        if(is_dir($saveDir)){
            if (is_file($savePath)) {
                $data = include $savePath;
                if($data['expire']===true){
                    return true;
                }else{
                    return $data['expire']-time();
                }
            }
        }
        return null;
    }
}