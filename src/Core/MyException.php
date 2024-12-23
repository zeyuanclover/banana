<?php

namespace Plantation\Banana\Core;

class MyException
{
    /**
     * @param $message
     * @param $code
     * @param \Exception|null $previous
     * 构造函数
     */
    public function __construct($message, $code = 0, \Exception $previous = null,$template=false){
        if(APP_DEBUG==false){
            return null;
        }

        if($this->isAjax() || strtoupper($_SERVER['REQUEST_METHOD'])!== 'GET'){
            $this->ajaxMsg($message, $code, $previous);
        }else{
            if ($template) {
                $this->templateMsg($message, $code, $previous);
            }else{
                $this->msg($message, $code, $previous);
            }
        }
    }

    /**
     * @param $message
     * @param $code
     * @param \Exception|null $previous
     * @return Exception
     * 初始化函数
     */
    static function instance($message, $code = 0, \Exception $previous = null,$template=false){
        return new MyException($message, $code, $previous,$template);
    }

    /**
     * @return bool
     * 是否ajax
     */
    function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @param $message
     * @param $code
     * @param \Exception|null $previous
     * @return void
     * ajax 异常提示
     */
    function ajaxMsg($message, $code = 0, \Exception $previous = null){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
           'code' => $code,
           'message' => $message
        ]);
    }

    /**
     * @param $message
     * @param $code
     * @param \Exception|null $previous
     * @return void
     * 模板提示错误
     */
    function templateMsg($message, $code = 0, \Exception $previous = null){
        extract([
            'message' => $message,
            'code' => $code,
        ]);
        include APP_PATH . DIRECTORY_SEPARATOR . 'exception.phtml';
    }

    /**
     * @param $message
     * @param $code
     * @param \Exception|null $previous
     * @return void
     * 模板 异常提示
     */
    function msg($message, $code = 0, \Exception $previous = null){
        echo $code.'>>>'.$message;
    }
}