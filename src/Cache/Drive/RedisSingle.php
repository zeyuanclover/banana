<?php

namespace Plantation\Banana\Cache\Drive;

use Plantation\Banana\Cookie;
use Plantation\Banana\Safe\Certificate;

class RedisSingle
{
    /**
     * @var int|mixed
     * 是否安全模式
     */
    public $safe = false;

    /**
     * @var
     *
     */
    protected $instance;

    protected $path;

    /**
     * @param $safe
     * 构造函数
     */
    public function __construct($safe,$obj,$path=[])
    {
        if ($safe==true){
            $this->safe = $safe;
        }
        $this->instance = $obj;
        if(count($path)>0){
            $this->path = $path;
        }else{
            $this->path = [
                'private'=>ROOT_PATH.DIRECTORY_SEPARATOR.'perm'.DIRECTORY_SEPARATOR.'private.perm',
                'public'=>ROOT_PATH.DIRECTORY_SEPARATOR.'perm'.DIRECTORY_SEPARATOR.'public.perm'
            ];
        }
    }

    /**
     * @param $safe
     * @return RedisSingle
     * 初始化
     */
    public static function instance($safe,$obj,$path=[])
    {
        return new RedisSingle($safe,$obj,$path);
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     * 设置
     */
    public function set($key, $value,$expire=0)
    {
        if (is_array($value)){
            $value = json_encode($value);
        }

        if ($this->safe==true){
            $obj = new Certificate($this->path['private'],$this->path['public']);
            $value = $obj->publicEncrypt($value);
        }

        if($expire>0){
            $rs = $this->instance->setex($key,$expire,$value);
        }else{
            $rs = $this->instance->set($key,$value);
        }

        if ($rs=='OK'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $key
     * @return null
     * 获取
     */
    public function get($key)
    {
        $value = $this->instance->get($key);

        if(!$value){
            return null;
        }

        if ($this->safe==true){
            $obj = new Certificate($this->path['private'],$this->path['public']);
            $value = $obj->privDecrypt($value);
        }

        if (json_validate($value)){
            return json_decode($value,true);
        }

        return $value;
    }

    /**
     * @param $key
     * @return mixed
     * 删除
     */
    public function delete($key)
    {
        return $this->instance->del($key);
    }

    /**
     * @param $key
     * @param $expre
     * @return mixed
     * 过期时间
     */
    public function expire($key,$expre)
    {
        return $this->instance->expire($key,$expre);
    }

    /**
     * @param $key
     * @return mixed
     * 过期时间查询
     */
    public function ttl($key)
    {
        return $this->instance->ttl($key);
    }
}