<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 14:27:57 CST
 *  Description:     AbstractProcess.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 14:27:57 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Process;

use Capsheaf\Config\Config;
use Capsheaf\Config\JsonConfig;
use Capsheaf\Log\Formatter\LineFormatter;
use Capsheaf\Log\Logger;
use Capsheaf\Log\LogHandler\StreamHandler;

abstract class AbstractProcess
{
    public $m_sProcess = 'process';

    public $process;

    abstract public function run($nPid);


    public function init()
    {
        $this->process['log']    = $this->initLog();
        $this->process['config'] = $this->initConfig();
    }


    private function initLog()
    {

        $logHandlerStdout = new StreamHandler('php://stdout');
        $logHandlerFile = new StreamHandler(RUNTIME_PATH.$this->m_sProcess . '.log');
        $logFormatter = new LineFormatter();
        $logHandlerStdout->setFormatter($logFormatter);
        $logHandlerFile->setFormatter($logFormatter);

        return new Logger($this->m_sProcess, [$logHandlerStdout, $logHandlerFile]);
    }


    private function initConfig()
    {
        $sBaseConfigPath = APP_PATH . "Etc/config.json";

        return new JsonConfig($sBaseConfigPath);
    }
}