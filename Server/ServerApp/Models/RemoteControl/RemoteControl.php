<?php
/********************************************************************************************
 *             Copy Right (c) 2021 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2021-11-08 10:31:09 CST
 *  Description:     RemoteControl.php's function description
 *  Version:         1.0.0.20211108-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2021-11-08 10:31:09 CST initialized the file
 *******************************************************************************************/

namespace CapsheafBuilder\Models\RemoteControl;

use Capsheaf\Process\Process;
use RuntimeException;

class RemoteControl
{
    protected $m_sType;
    protected $m_sPackerOsIp;
    protected $m_sPackerOsPasswd;

    private $m_sExecRemoteShellTool;
    private $m_sTransTool;

    public function __construct($sType, $sPackerOsIp, $sPackerOsPasswd)
    {
        $this->m_sType = $sType;
        $this->m_sPackerOsIp = $sPackerOsIp;
        $this->m_sPackerOsPasswd = $sPackerOsPasswd;

        $this->m_sExecRemoteShellTool = str_replace('/', "\\", ROOT_PATH.'CapsheafBuilder/Public/remoteTools/plink.exe');
        $this->m_sTransTool = ROOT_PATH.'CapsheafBuilder/Public/remoteTools/pscp.exe';
    }


    public function uploadFile($sLocalFilePath, $sRemoteFileDir)
    {
        $sUploadFileCmd = "{$this->m_sTransTool} -q -C -pw {$this->m_sPackerOsPasswd} {$sLocalFilePath} root@{$this->m_sPackerOsIp}:{$sRemoteFileDir}";

        $process = new Process($sUploadFileCmd, null, null, null, 3600 * 24 * 2);
        if ($process->run() == 0){
            $sOutput = $process->getOutput();

            return trim($sOutput);
        } else {
            throw new RuntimeException($process->getErrorOutput());
        }
    }


    public function downloadFile($sLocalFilePath, $sRemoteFilePath)
    {
        $sLocalFileDir = dirname($sLocalFilePath);

        $sDownloadFileCmd = "{$this->m_sTransTool} -q -C -pw {$this->m_sPackerOsPasswd} root@{$this->m_sPackerOsIp}:{$sRemoteFilePath} {$sLocalFileDir}";
        $process = new Process($sDownloadFileCmd, null, null, null, 3600 * 24 * 2);
        if ($process->run() == 0){
            $sOutput = $process->getOutput();

            return trim($sOutput);
        } else {
            throw new RuntimeException($process->getErrorOutput());
        }

    }


    public function execRemoteShell($sCommand, $bIgnoreOutput = true)
    {
        $sStorageCacheCmd = "echo y | {$this->m_sExecRemoteShellTool} -ssh root@{$this->m_sPackerOsIp} \"exit\"";
        $process = new Process($sStorageCacheCmd);
        if ($process->run() != 0) {
            throw new RuntimeException($process->getErrorOutput());
        }

        $sExecCmd = "{$this->m_sExecRemoteShellTool} -pw {$this->m_sPackerOsPasswd} root@{$this->m_sPackerOsIp} -ssh -batch \"$sCommand\"";

        $nTry = 0;
        do{
            $nTry++;
            $process = new Process($sExecCmd);
            $process->run();
            $sOutput = $process->getOutput();
            if (!empty($sOutput) || $bIgnoreOutput){
                break;
            }

            usleep(100);
            unset($process);
            $process = null;
        } while($nTry < 50);


        return trim($sOutput);
    }
}