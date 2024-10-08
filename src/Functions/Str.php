<?php
/**
 * @param $length
 * @return string
 * 生成随机字符串
 */
if(!function_exists('generateRandomString')){
    function generateRandomString($length) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        return $randomString;
    }
}

/**
 * @param $bytes
 * @param $precision
 * @return string
 * 字节大小格式化
 */
if(!function_exists('formatBytes')){
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * @param $date
 * @return string
 * 日期格式化
 */
if(!function_exists('relativeTime')){
    function relativeTime($date) {
        $timestamp = strtotime($date);
        $seconds = time() - $timestamp;

        $minutes = $seconds / 60;
        $hours = $minutes / 60;
        $days = $hours / 24;
        $years = $days / 365;

        if ($seconds < 60) {
            return "刚刚";
        } else if ($minutes < 60) {
            return round($minutes) . "分钟前";
        } else if ($hours < 24) {
            return round($hours) . "小时前";
        } else if ($days < 30) {
            return round($days) . "天前";
        } else if ($years < 1) {
            return round($days / 30) . "个月前";
        } else {
            return round($years) . "年前";
        }
    }
}

/**
 * @param $length
 * @return string
 * 获取连续的随机数
 */
if(!function_exists('getGuLd')){
    function getGuLd($length){
        return round(microtime(true) * 1000).mt_rand(1000,9999).generateRandomString(5);
    }
}

/**
 * @param $filename
 * @return array|string
 * 获取文件扩展名
 */
if(!function_exists('getFileExtension')){
    function getFileExtension($filename){
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
}

/*
// 使用示例
echo relativeTime("2023-01-01 12:00:00"); // 输出: "几年前"
echo relativeTime("yesterday"); // 输出: "1天前"
echo relativeTime("now"); // 输出: "刚刚"

echo generateRandomString(1000);
*/
