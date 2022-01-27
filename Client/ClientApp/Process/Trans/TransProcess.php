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

use ClientApp\Process\AbstractProcess;

class TransProcess extends AbstractProcess
{
    public $m_sProcess = 'trans_process';

    public function run($nPid)
    {
        $this->init();

        $this->process['log']->info("trans starting...");
sleep(10);
        $this->process['log']->info("trans end...");
    }

}