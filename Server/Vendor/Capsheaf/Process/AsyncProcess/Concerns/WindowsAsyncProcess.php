<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-03 16:25:57 CST
 *  Description:     WindowsAsyncProcess.php's function description
 *  Version:         1.0.0.20180503-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-03 16:25:57 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\AsyncProcess\Concerns;

use RuntimeException;

class WindowsAsyncProcess extends AbstractAsyncProcess
{

    /**
     * WshShell 对象
     * @var \COM|null
     */
    protected $m_comShell = null;


    /**
     * WindowsAsyncProcess constructor.
     * @param string $sCommand 进程命令
     * @param null|string $sStdOutFile STDOUT文件路径，null表示不输出
     * @param null|string $sStdInFile STDIN文件路径，null表示无输入
     * @param null|false|string $sStdErrFile STDERR文件路径，null表示不关注，false表示使用STDOUT相同的输出
     * @throws RuntimeException Windows情况下若无法初始化COM组件则抛出
     */
    public function __construct($sCommand, $sStdOutFile = null, $sStdInFile = null, $sStdErrFile = null)
    {
        if (class_exists('\COM')) {
            $this->m_comShell = new \COM('WScript.Shell');
        } else {
            throw new RuntimeException('Failed instantiate COM object. Make sure php_com_dotnet.dll extension is loaded.');
        }

        parent::__construct($sCommand, $sStdOutFile, $sStdInFile, $sStdErrFile);
    }


    protected function getCmdCommand()
    {
        return sprintf(
            'cmd /C (%s) %s',
            $this->escapeArgument($this->m_sCommand),
            $this->getRedirectString()
        );
    }


    public function start()
    {
        try {
            $sCmcCommand = $this->getCmdCommand();
            app('log')->info("Lunching process:{$sCmcCommand}");
            $comExec = $this->m_comShell->Exec($sCmcCommand);
            if (!empty($comExec)){
                $this->m_nPid = $comExec->ProcessID;
            }

        } catch (\COM_Exception $exception) {
            throw new RuntimeException("Lunching process: '{$this->m_sCommand}' failed:{$exception->getMessage()}.");
        }

        return $this->m_nPid;
    }


    public function stop($sSignal = '')
    {
        return static::stopByPid($this->m_nPid, $sSignal);
    }


    public static function stopByPid($nPid, $sSignal = '')
    {
        $sCommand = sprintf('taskkill /T /F /PID %d', $nPid);
        exec($sCommand, $arrOutputLines, $nStatusCode);
        if ($nStatusCode != 0 || static::runningByPid($nPid)){
            return false;
        }

        return true;
    }


    public function getPid()
    {
        return $this->m_nPid;
    }


    public function running()
    {
        return static::runningByPid($this->m_nPid);
    }


    public static function runningByPid($nPid)
    {
        // /NH 不显示标题，/FI 过滤器
        $sCommand = sprintf('tasklist /NH /FI "PID eq %d"', $nPid);
        exec($sCommand, $arrOutputLines, $nStatusCode);
        if ($nStatusCode != 0){
            return false;
        }

        if (isset($arrOutputLines[1]) && strpos($nPid, $arrOutputLines[1])){
            return true;
        }

        return false;
    }

}
