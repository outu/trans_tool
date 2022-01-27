<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 14:25:41 CST
 *  Description:     ScannerProcess.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 14:25:41 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Process\Scanner;

use ClientApp\Models\Client\TransList;
use ClientApp\Models\Client\TransTask;
use ClientApp\Process\AbstractProcess;
use DateTime;

class ScannerProcess extends AbstractProcess
{
    public $m_sProcess = 'scanner_process';

    public function run($nPid)
    {
        $this->init();

        $this->process['log']->info("scanner started...");
        $arrTransTask = (new TransTask())->getTransTask();

        foreach ($arrTransTask as $arrOneTransTask){

            $arrMeta = json_decode($arrOneTransTask['meta'], true);
            $nTaskId = $arrOneTransTask['id'];
            (new TransTask())->updateTaskStatus($nTaskId, 'TASK_SCANNING');

            $arrFtpInfo      = $arrMeta['arrFtpInfo'];
            $arrSelectedFile = $arrMeta['arrSelectedFile'];

            foreach ($arrSelectedFile as $sSelectedFile){
                $this->dfs($sSelectedFile, $nTaskId, $arrFtpInfo);
            }

            (new TransTask())->updateTaskStatus( $nTaskId, 'TASK_SCANNED');
        }

        $this->process['log']->info("current scanner finished...");
    }


    /**
     * 深度遍历目录，并更新扫描信息到数据库中
     * @return void
     */
    private function dfs($sDirOrFile, $nTaskId, $arrFtpInfo)
    {
        if (is_dir($sDirOrFile)){
            if ($hDir = opendir($sDirOrFile)){
                while(($sFile = readdir($hDir)) !== false){
                    if (in_array($sFile, ['.', '..'])){
                        continue;
                    }

                    $sAbsPath = "{$sDirOrFile}/{$sFile}";

                    if (is_dir($sAbsPath)){
                        $this->dfs($sAbsPath, $nTaskId, $arrFtpInfo);
                    } else {
                        $this->processFile($sAbsPath, $nTaskId);
                    }
                }
            }
        } else {
            $this->processFile($sDirOrFile, $nTaskId);
        }
    }


    private function processFile($sFilePath, $nTaskId)
    {
        if (file_exists($sFilePath)){
            $nSize = filesize($sFilePath);
            $arrParameter = [
                'record'  => $sFilePath,
                'task_id' => $nTaskId,
                'size'    => $nSize,
                'state'   => 'WAITING',
                'keep_alive' => time(),
                'created_at' => new DateTime()
            ];

            (new TransList())->insertTransList($arrParameter);
        }
    }

}