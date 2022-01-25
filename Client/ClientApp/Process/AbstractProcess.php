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

use Capsheaf\Application\Application;

abstract class AbstractProcess
{
    protected $m_app;

    abstract public function run($nPid);


    public function init(Application $app)
    {
        $this->m_app = $app;
    }
}