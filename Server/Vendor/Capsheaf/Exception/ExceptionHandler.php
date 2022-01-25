<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:40 CST
 *  Description:     ExceptionHandler.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:40 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Exception;

use Capsheaf\Application\Application;
use ErrorException;
use Exception;

class ExceptionHandler
{
    protected $m_app;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    public function registerHandlers()
    {
        error_reporting(E_ALL & ~E_DEPRECATED);// & ~E_WARNING);
        //将所有的错误转化为异常
        set_error_handler([$this, 'handleError']);
        //处理未捕获的异常，注意已经catch的异常这里不会处理
        set_exception_handler([$this, 'handleException']);
        //注册最后的异常处理函数
        register_shutdown_function([$this, 'handleShutdown']);
    }


    /**
     * 将PHP标准错误按照错误等级全部转换为ErrorException处理
     * @param int $nErrLevel
     * @param string $sErrStr
     * @param string $sErrFile
     * @param int $nErrLine
     * @param array $arrErrContext deprecated
     * @throws ErrorException
     */
    public function handleError($nErrLevel, $sErrStr, $sErrFile = '', $nErrLine = 0, array $arrErrContext = [])
    {
        //注意只需要处理指定等级以上的错误，如可以选择忽略notice以下的消息
        if (error_reporting() & $nErrLevel){
            throw new ErrorException($sErrStr, 0, $nErrLevel, $sErrFile, $nErrLine);
        }
    }


    /**
     * 回调函数用于处理未捕获的异常，注意已经catch的异常这里不会处理
     * @param Exception $exception
     */
    public function handleException($exception)
    {
        $this->m_app->renderException($exception);
    }


    /**
     * PHP停止时，最后的机会对错误进行报告
     */
    public function handleShutdown()
    {
        //要是最后存在致命错误，则报告一个异常
        if (!is_null($arrError = error_get_last()) && $this->isFatal($arrError['type'])){
            $this->handleException(new Exception($arrError['message'].' in '.$arrError['file'].':'.$arrError['line'], $arrError['type']));
        }

        if (isset($this->m_app['log'])){
            $this->m_app['log']->info('Application stopped.');
        }
    }


    /**
     * 判断错误是否是致命错误
     * @param $nErrorType
     * @return bool
     */
    protected function isFatal($nErrorType)
    {
        return in_array($nErrorType, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

}
