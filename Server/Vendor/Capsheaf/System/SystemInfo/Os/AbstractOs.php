<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-31 13:18:46 CST
 *  Description:     AbstractOs.php's function description
 *  Version:         1.0.0.20180331-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-31 13:18:46 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\SystemInfo\Os;

use Capsheaf\Utils\Types\Parameter;

abstract class AbstractOs
{

    /**
     * 保存查询过的可以缓存的值。
     * @var array
     */
    protected $m_arrCachedQueries = [];


    public function __construct()
    {
        $this->m_arrCachedQueries = new Parameter();
    }


    protected function putCache($sCacheKey, $value)
    {
        $this->m_arrCachedQueries[$sCacheKey] = $value;
    }


    protected function getCache($sCacheKey)
    {
        if (isset($this->m_arrCachedQueries[$sCacheKey])){
            return $this->m_arrCachedQueries[$sCacheKey];
        }

        return null;
    }


    public function getSystemInfo()
    {

    }


    public function getDistributionInfo()
    {

    }


    public function getManufacturerInfo()
    {

    }


    public function getHostName()
    {

    }


    public function getUsers()
    {

    }


    public function getUpTime()
    {

    }


    public function getCpuInfo()
    {

    }


    public function getMemoryInfo()
    {

    }


    public function getFileSystemInfo()
    {

    }


    public function getHardwareInfo()
    {

    }


    public function getProcessesInfo()
    {

    }


    public function isSupperUser()
    {

    }

}
