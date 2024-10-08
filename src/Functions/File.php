<?php
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
function upload($targetDirectory,$name,$key=[],$relativePath='',$fileName=''){
    if(!is_dir($targetDirectory)){
        return -1;
    }

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

        $targetFile = rtrim($targetDirectory,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        if (move_uploaded_file($tempFile, $targetFile)) {
           return rtrim($relativePath,'/') . '/' . $fileName;
        } else {
            return false;
        }
    }
}