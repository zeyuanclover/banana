<?php

namespace Plantation\Banana\Core;

class View
{
    /**
     * @param $template
     * @param $data
     * @return void
     * 查看模板
     */
    public function fetch($template){
        $path = $_SERVER['theme'];
        if(!is_dir($path)){
            mkdir($path,0777,true);
        }

        $template = ltrim($template, '/');
        $template = ltrim($template, '\\');
        $template = ltrim($template, DIRECTORY_SEPARATOR);

        if(is_file($path . DIRECTORY_SEPARATOR . $template)){
            return $path . DIRECTORY_SEPARATOR . $template;
        }else{
            $msg = '在 '.__METHOD__.' 方法第'.__LINE__.' 行, ['.$path . DIRECTORY_SEPARATOR . $template.'],不存在的模板，请创建！';
            $code = 1;
            Exception::instance($msg, $code,null,1);
        }
    }
}