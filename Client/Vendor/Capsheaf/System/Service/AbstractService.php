<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-10-18 11:38:06 CST
 *  Description:     AbstractService.php's function description
 *  Version:         1.0.0.20181018-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-10-18 11:38:06 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Service;

use Exception;

abstract class AbstractService
{

    /**
     * 注册系统服务
     * @param string $sServiceName 服务名
     * @param string $sPath 可执行文件路径
     * @param string $sParams 参数
     * @param array $arrExtraInfo 根据系统的不同，可以传入额外的信息
     * @return bool true表示成功，失败时抛出异常
     * @throws Exception 失败时返回错误信息
     */
    abstract public function createService($sServiceName, $sPath, $sParams, $arrExtraInfo = []);


    /**
     * 删除系统服务
     * @param string $sServiceName 服务名
     * @return bool true表示成功，失败时抛出异常
     * @throws Exception 失败时返回错误信息
     */
    abstract public function deleteService($sServiceName);


    abstract public function queryServiceStatus($sServiceName);


    abstract public function startService($sServiceName);


    abstract public function stopService($sServiceName);

}
