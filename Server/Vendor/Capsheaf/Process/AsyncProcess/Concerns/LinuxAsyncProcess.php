<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-03 16:26:13 CST
 *  Description:     LinuxAsyncProcess.php's function description
 *  Version:         1.0.0.20180503-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-03 16:26:13 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\AsyncProcess\Concerns;

use RuntimeException;

class LinuxAsyncProcess extends AbstractAsyncProcess
{

    public function start()
    {
        $sPid = exec(sprintf('%s %s & echo $! &', $this->m_sCommand, $this->getRedirectString()));

        if (empty($sPid)){
            throw new RuntimeException("Lunching process: '{$this->m_sCommand}' failed.");
        }

        $this->m_nPid = (int)$sPid;

        return $this->m_nPid;
    }


    public function stop($sSignal = '')
    {
        return static::stopByPid($this->m_nPid);
    }


    public static function stopByPid($nPid, $sSignal = '')
    {
        $sCommand = sprintf('kill %s %s', $sSignal , $nPid);
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
        $sCommand = 'ps -p '.$nPid;
        exec($sCommand, $arrOutputLines, $nStatusCode);
        if ($nStatusCode != 0 || empty($arrOutputLines[1])){
            return false;
        }

        return true;
    }

}
