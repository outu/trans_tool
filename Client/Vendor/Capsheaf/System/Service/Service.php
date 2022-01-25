<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-10-18 15:46:31 CST
 *  Description:     Service.php's function description
 *  Version:         1.0.0.20181018-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-10-18 15:46:31 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Service;

/**
 * Class Service
 * @package Capsheaf\System\Service
 * @method createService($sServiceName, $sPath, $sParam, $arrExtraInfo = [])
 * @method deleteService($sServiceName)
 * @method getCurrentPhpExecutorPath()
 */
class Service
{

    protected static $m_serviceInstance;


    public static function getService()
    {
        if (!empty(self::$m_serviceInstance)){
            return self::$m_serviceInstance;
        }

        if (windows_os()){
            self::$m_serviceInstance = new WindowsService();
        } else {
            self::$m_serviceInstance = new LinuxService();
        }

        return self::$m_serviceInstance;
    }


    public static function __callStatic($sMethodName, $arrArguments)
    {
        $service = self::getService();

        return call_user_func_array([$service, $sMethodName], $arrArguments);
    }

}
