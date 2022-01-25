<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-10-18 11:51:59 CST
 *  Description:     LinuxService.php's function description
 *  Version:         1.0.0.20181018-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-10-18 11:51:59 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Service;

use Capsheaf\Process\Process;
use Capsheaf\System\Service\LinuxService\Rhel6Service;
use RuntimeException;

class LinuxService extends AbstractService
{

    /**
     * @var AbstractService
     */
    protected $m_serviceImpl = null;


    public function __construct()
    {
        $sOsDistribution = $this->getOsDistribution();
        $sOsVersionMajor = $this->getOsVersionMajor();

        switch ($sOsDistribution){
            case "CentOS":
            case "RedHatEnterpriseServer":
            case "OracleServer":
                $this->m_serviceImpl = new Rhel6Service();
                break;
            default:
                throw new RuntimeException("Service not implement of OS: {$sOsDistribution}, Version: {$sOsVersionMajor}.");
        }
    }


    public function createService($sServiceName, $sPath, $sParams, $arrExtraInfo = [])
    {
        return $this->m_serviceImpl->createService($sServiceName, $sPath, $sParams, $arrExtraInfo);
    }


    public function deleteService($sServiceName)
    {
        return $this->m_serviceImpl->deleteService($sServiceName);
    }


    public function queryServiceStatus($sServiceName)
    {
        return $this->m_serviceImpl->queryServiceStatus($sServiceName);
    }


    public function startService($sServiceName)
    {
        return $this->m_serviceImpl->startService($sServiceName);
    }


    public function stopService($sServiceName)
    {
        return $this->m_serviceImpl->stopService($sServiceName);
    }


    public function getOsDistribution()
    {
        $process = new Process("lsb_release -a 2>/dev/null | grep \"Distributor ID:\" | awk '{ print $3 }'");

        return $process->getOutput();
    }


    public function getOsVersionMajor()
    {
        $sCmd =<<<CMD
OS_VERSION=`lsb_release -a  2>/dev/null | grep "Release:" | awk '{ print $2 }'`
OS_VERSION_MAJOR=\${OS_VERSION%%.*}
echo \$OS_VERSION_MAJOR
CMD;

        $process = new Process($sCmd);

        return trim($process->getOutput());
    }

}
