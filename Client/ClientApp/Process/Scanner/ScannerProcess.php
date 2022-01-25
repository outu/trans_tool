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

class ScannerProcess extends AbstractProcess
{
    public function run($nPid)
    {
        $this->m_app['log']->info("scanner started...");
        $arrTransTask = (new TransTask())->getTransTask();

        foreach ($arrTransTask as $arrOneTransTask){

            $arrMeta = json_decode($arrOneTransTask['meta']);
            $nTaskId = $arrOneTransTask['id'];
            (new TransTask())->updateTaskStatus('TASK_SCANNING', $nTaskId);

            foreach ($arrMeta as $sTransRecord){
                $this->dfs($sTransRecord, $nTaskId);
            }

            (new TransTask())->updateTaskStatus('TASK_SCANNED', $nTaskId);
        }

        $this->m_app['log']->info("current scanner finished...");
    }


    /**
     * 深度遍历目录，并更新扫描信息到数据库中
     * @return void
     */
    private function dfs($sDirOrFile, $nTaskId)
    {
        if (is_dir($sDirOrFile)){
            if ($hDir = opendir($sDirOrFile)){
                while(($sFile = readdir($hDir)) !== false){
                    if (in_array($sFile, ['.', '..'])){
                        continue;
                    }

                    $sAbsPath = "{$sDirOrFile}{$sFile}";

                    if (is_dir($sAbsPath)){
                        $this->dfs($sAbsPath, $nTaskId);
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
                'record' => $sFilePath,
                'size'   => $nSize,
                'state'  => 'WAITING'
            ];

            (new TransList())->updateTransList($nTaskId, $arrParameter);
        }
    }

}