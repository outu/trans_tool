<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:06:08 CST
 *  Description:     Snapshot.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:06:08 CST initialized the file
 ******************************************************************************/
namespace Capsheaf\System;

use Capsheaf\Application\Application;
use Capsheaf\System\Linux\LinuxSnapshot;
use Capsheaf\System\Windows\WindowsSnapshot;

class Snapshot
{

    protected $m_app;
    protected $m_snapshot;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
        if (windows_os()){
            $this->m_snapshot = new WindowsSnapshot($this->m_app);
        }else{
            $this->m_snapshot = new LinuxSnapshot($this->m_app);
        }
    }


    /**
     * 参数传递到直接的对象
     * @param string $sMethodName
     * @param array $arrArguments
     * @return mixed
     */
    public function __call($sMethodName, $arrArguments)
    {
        return call_user_func_array([$this->m_snapshot, $sMethodName], $arrArguments);
    }

}
