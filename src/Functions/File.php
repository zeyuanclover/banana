<?php
namespace Plantation\Banana\Functions;

/**
 * @param $path
 * @return array
 * 获取目录下所有配置文件
 */
if(!function_exists('getFilesConfigInDirectory')){
    function getFilesConfigInDirectory($path){
        //列出目录下的文件或目录
        $fetchdir = scandir($path);
        sort($fetchdir);
        $arr_file = array();
        foreach ($fetchdir as $key => $value) {
            if($value == "." || $value == ".."){
                continue;
            }
            if(is_dir($path.DIRECTORY_SEPARATOR.$value)){
                $arr_file[$value] = getFilesConfigInDirectory($path.DIRECTORY_SEPARATOR.$value);
            }else{
                if($value!='.DS_Store'){
                    $fileName = pathinfo($value, PATHINFO_FILENAME);
                    $content = include ($path.DIRECTORY_SEPARATOR.$value);
                    $arr_file[$fileName] = $content;
                }
            }
        }
        return $arr_file;
    }
}

/*
 * @param 目录路径
 * 递归获得目录下所有文件
 * @返回数组
 */
if(!function_exists('getFilesInDirectory')){
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

/**
 * @param $path
 * @return void
 * 删除目录下所有文件以及文件夹
 */
if(!function_exists('deldir')){
    function deldir($path){
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    $rpath = $path.DIRECTORY_SEPARATOR.$val;
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($rpath)){
                        //子目录中操作删除文件夹和文件
                        deldir($rpath.DIRECTORY_SEPARATOR);
                        //目录清空后删除空文件夹
                        @rmdir($rpath.DIRECTORY_SEPARATOR);
                    }else{
                        //如果是文件直接删除
                        @unlink($rpath);
                    }
                }
            }
        }
    }
}

/**
 * @param $arr
 * @return array
 * 获取数组key
 */
if(!function_exists('getKeyArr')){
    function getKeyArr($arr){
        static $sarr = [];
        foreach ($arr as $key=>$val){
            $sarr[] = $key;
            if (is_array($val)){
                getKeyArr($val);
            }
        }
        return $sarr;
    }
}

/**
 * @param $arr
 * @param $keys
 * @return array|mixed
 * 获取数组层级最底层一个value
 */
if(!function_exists('getValue')){
    function getValue($arr,$keys){
        $ekey = end($keys);
        $farr = [];

        foreach ($keys as $key){
            if(isset($arr[$key])){
                $farr = $arr[$key];
            }

            if(isset($farr[$key])){
                $farr = $farr[$key];
            }
        }

        if (isset($farr[$ekey])){
            return $farr[$ekey];
        }
        return $farr;
    }
}

/**
 * @param $targetDirectory
 * @param $fileName
 * @param $name
 * @param $key
 * @return false|int|string|void
 * 上传文件
 */
function upload($name,$key=[],$relativePath='',$fileName=''){
    if(count($key)==0){
        $err = $_FILES[$name]['error'];
        $tempFile = $_FILES[$name]['tmp_name'];
        $name = $_FILES[$name]['name'];
    }else{
        $keys = getKeyArr($key);
        $err = getValue($_FILES[$name]['error'],$keys);
        $tempFile = getValue($_FILES[$name]['tmp_name'],$keys);
        $name = getValue($_FILES[$name]['name'],$keys);
    }

    if ($err === UPLOAD_ERR_OK) {
        if(!$fileName){
            $fileName = $name;
        }

        $path = ROOT_PATH.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.rtrim($relativePath,DIRECTORY_SEPARATOR);
        if(!is_dir($path)){
            mkdir($path,0777,true);
        }

        if(!$relativePath){
            $relativePath = '/media'.'/'.date('Y-m-d-H');
        }

        $targetFile = $path . DIRECTORY_SEPARATOR . $fileName;
        if (move_uploaded_file($tempFile, $targetFile)) {
           return rtrim($relativePath,'/') . '/' . $fileName;
        } else {
            return false;
        }
    }
}

/**
 * @param $path
 * @return void
 * 生成证书
 */
function generateCertificate($path){
    // 配置公钥和私钥的配置
    $config = array(
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );

    // 创建新的公钥和私钥
    $res = openssl_pkey_new($config);

    // 检查是否生成成功
    if ($res === false) {
        die('生成公钥和私钥失败');
    }

    // 从资源中获取公钥和私钥的信息
    openssl_pkey_export($res, $privateKey);
    $publicKeyDetails = openssl_pkey_get_details($res);
    $publicKey = $publicKeyDetails["key"];

    $path = rtrim($path,'/');
    $path = rtrim($path,DIRECTORY_SEPARATOR);
    if(!is_dir($path)){
        mkdir($path,0777,true);
    }

    // 将私钥和公钥写入文件
    $privateKeyFile = $path.DIRECTORY_SEPARATOR.'private_key.pem';
    $publicKeyFile = $path.DIRECTORY_SEPARATOR.'public_key.pem';
    file_put_contents($privateKeyFile, $privateKey);
    file_put_contents($publicKeyFile, $publicKey);
}