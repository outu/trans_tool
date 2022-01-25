<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 11:47:11 CST
 *  Description:     AbstractLogger.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 11:47:11 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log;

abstract class AbstractLogger
{

    abstract public function log($nLevel, $sMessage, array $arrContext = []);


    public function emergency($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::EMERGENCY, $sMessage, $arrContext);
    }


    public function alert($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::ALERT, $sMessage, $arrContext);
    }


    public function critical($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::CRITICAL, $sMessage, $arrContext);
    }


    public function error($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::ERROR, $sMessage, $arrContext);
    }


    public function warning($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::WARNING, $sMessage, $arrContext);
    }


    public function notice($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::NOTICE, $sMessage, $arrContext);
    }


    public function info($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::INFO, $sMessage, $arrContext);
    }


    public function debug($sMessage, array $arrContext = [])
    {
        $this->log(LogLevel::DEBUG, $sMessage, $arrContext);
    }

}
