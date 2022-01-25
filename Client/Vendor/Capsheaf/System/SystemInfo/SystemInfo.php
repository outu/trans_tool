<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-31 13:03:38 CST
 *  Description:     SystemInfo.php's function description
 *  Version:         1.0.0.20180331-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-31 13:03:38 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\SystemInfo;

class SystemInfo
{

    public function __construct()
    {

    }


    public function getOsType()
    {

    }


    public function getOsBuildNumber()
    {

    }


    public function getSystemInfo()
    {

    }


    /**
     * 判断PHP运行在32位还是64位
     * @return bool
     */
    public static function isX86()
    {
        return PHP_INT_MAX == 2147483647;
    }

}
