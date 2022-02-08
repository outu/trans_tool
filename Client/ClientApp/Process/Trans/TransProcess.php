<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 14:36:13 CST
 *  Description:     TransProcess.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 14:36:13 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Process\Trans;

use ClientApp\Models\Client\TransList;
use ClientApp\Process\AbstractProcess;

class TransProcess extends AbstractProcess
{
    public $m_sProcess = 'trans_process';

    public function run($nPid)
    {
        $this->init();

        $this->process['log']->info("trans starting...");

        //获取一条等待传输的文件记录
        $bTrans = true;

        while($bTrans == false || $arrOneTransRecordInfo = (new TransList())->getOneTransRecord()){
            (new TransList())->updateListStatus($arrOneTransRecordInfo['id'], 'HANDING');

            $bTrans = $this->doTrans($arrOneTransRecordInfo['record']);

            if ($bTrans){
                //修改数据库表状态为完成
                (new TransList())->updateListStatus($arrOneTransRecordInfo['id'], 'HANDED');
            }
        }


        $this->process['log']->info("trans end...");
    }


    private function doTrans($sTrans)
    {
        $bOk = false;
        $arrFtpInfo = $this->process['config']->get('ftp');

        $hFtp = ftp_connect($arrFtpInfo['server'], $arrFtpInfo['port']);

        if (ftp_login($hFtp, $arrFtpInfo['user'], $arrFtpInfo['password']) == false){
            $this->process['log']->notice("ftp login error {$arrFtpInfo['user']} {$arrFtpInfo['password']}...");
            return false;
        }

        ftp_pasv($hFtp, true);

        if (is_file($sTrans)){
            $this->makeFtpDirectory($hFtp, dirname($sTrans));

            $hLocalAbsPath = fopen($sTrans, "r");
            $bOk = @ftp_nb_fput($hFtp, $sTrans, $hLocalAbsPath,FTP_BINARY);
            //$bOk = @ftp_put($this->m_hFtp, $sRemoteFtpPath, $sLocalAbsPath, FTP_BINARY);
            if (!$bOk) {
                $arrLastError = error_get_last();
                if (!empty($arrLastError[0]['message'])) {
                    if (strpos($arrLastError[0]['message'], ' not ')) {//ftp_nb_fput(): Could not create file
                        $this->makeFtpDirectory($hFtp, dirname($sTrans));
                        $bOk = @ftp_put($hFtp, $sTrans, $sTrans, FTP_BINARY);
                    }
                }
            }
            @fclose($hLocalAbsPath);
        } else {
            $this->makeFtpDirectory($hFtp, $sTrans);
        }


        return $bOk;
    }


    private function makeFtpDirectory($hFtp, $sRemoteFtpDir)
    {
        $bSuccess = true;

        //首先尝试一次性创建完整的路径的文件夹
        $bSuccess = @ftp_mkdir($hFtp, $sRemoteFtpDir);
        if (!$bSuccess) {
            //否则按照顺序依次创建
            $arrDirectoriesSegments = explode('/', trim($sRemoteFtpDir, '\\/'));
            $sFullRemotePath        = '/';
            foreach ($arrDirectoriesSegments as $sSegment) {
                $sFullRemotePath .= '/'.$sSegment;
                $bSuccess        = @ftp_mkdir($hFtp, $sFullRemotePath);
            }
        }

        return $bSuccess;
    }

}