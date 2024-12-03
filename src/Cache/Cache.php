<?php
namespace Plantation\Banana\Cache;

class Cache
{
    /**
     * @var
     * 缓存驱动
     */
    private $drive;

    /**
     * @param $drive
     * 构造函数
     */
    function __construct($drive)
    {
        $this->drive = $drive;
    }

    /**
     * @param $drive
     * @return Cache
     * 初始化
     */
    public static function instance($drive)
    {
        return new Cache($drive);
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     * @return mixed
     * 设置缓存
     */
    function set($key,$value,$expire=true)
    {
        return $this->drive->set($key,$value,$expire);
    }

    /**
     * @param $key
     * @return mixed
     * 获得缓存
     */
    function get($key)
    {
        return $this->drive->get($key);
    }

    /**
     * @param $key
     * @return mixed
     * 删除缓存
     */
    function delete($key)
    {
        return $this->drive->delete($key);
    }

    /**
     * @param $key
     * @return mixed
     * 缓存过期时间
     */
    function expire($key)
    {
        return $this->drive->expire($key);
    }

    /**
     * @param $key
     * @return mixed
     * 一次性使用数据
     */
    function forget($key)
    {
        $val = $this->get($key);
        $this->delete($key);
        return $val;
    }
}