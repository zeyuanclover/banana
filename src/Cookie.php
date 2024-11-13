<?php

namespace Plantation\Banana;
use Plantation\Banana\Safe\Certificate;

class Cookie
{
    /**
     * @var int|mixed
     * 是否安全模式
     */
    public $safe = 0;

    protected $path;
    /**
     * @param $safe
     * 构造函数
     */
    public function __construct($safe=true,$path)
    {
        if ($safe==true){
            $this->safe = $safe;
        }

        if(count($path)>0){
            $this->path = $path;
        }else{
            $this->path = [
                'private'=>ROOT_PATH.DIRECTORY_SEPARATOR.'/perm/private.perm',
                'public'=>ROOT_PATH.DIRECTORY_SEPARATOR.'/perm/public.perm'
            ];
        }
    }

    /**
     * @param $safe
     * @return Cookie
     * 初始化
     */
    public static function instance($safe,$path=[])
    {
        return new Cookie($safe,$path);
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     * @param $path
     * @param $domain
     * @param $secure
     * @param $httponly
     * @return bool
     * 设置cookie
     */
    public function set($key, $value,$expire=0,$path='/',$domain="",$secure=false,$httponly=false)
    {
        if(!$expire){
            $expires_or_options = time() + 3600 * 24 * 90;
        }else{
            $expires_or_options = $expire + time();
        }

        if ($this->safe==1){
            $obj = new Certificate($this->path['private'],$this->path['public']);
            $value = $obj->publicEncrypt($value);
        }

        return setcookie( $key,
            $value,
            $expires_or_options,
            $path,
            $domain,
            $secure,
            $httponly
        );
    }

    /**
     * @param $key
     * @return mixed|null
     * 获得cookie
     */
    public function get($key)
    {
        $value = isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
        if(!$value){
            return null;
        }

        if ($this->safe==1){
            $obj = new Certificate($this->path['private'],$this->path['public']);
            $value = $obj->privDecrypt($value);
        }

        return $value;
    }

    /**
     * @param $key
     * @param $expire
     * @param $path
     * @param $domain
     * @param $secure
     * @param $httponly
     * @return bool|void
     * 删除cookie
     */
    public function delete($key,$expire=0,$path='/',$domain="",$secure=false,$httponly=false)
    {
        if (isset($_COOKIE[$key])){
            uniqid($_COOKIE[$key]);
        }
        return $this->set($key,null,-10000,$path,$domain,$secure,$httponly);
    }

}