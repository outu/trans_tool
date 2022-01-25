<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:02:52 CST
 *  Description:     AutoLoader.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:02:52 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Application;

class AutoLoader
{

    protected $m_sRootPath;
    protected $m_sAppPath;
    protected $m_arrLoaderDirectory = [];


    public function __construct($sRootPath, $sAppPath)
    {
        $this->m_sRootPath = $sRootPath;
        $this->m_sAppPath = $sAppPath;

        $this->m_arrLoaderDirectory = [
            $this->getRootVendorPath(),
            $this->getRootPath(),
        ];
    }


    /**
     * 若未注册自动加载器，则加载
     */
    public function register()
    {
        $this->registerGlobalHelpers();
        spl_autoload_register([$this, 'load'], true, true);
    }


    public function registerGlobalHelpers()
    {
        include $this->getRootVendorPath().'/Capsheaf/Support/helpers.php';
    }


    /**
     * 自动加载回调函数
     * @param string $sClass 类名称
     */
    public function load($sClass)
    {
        $sClass = str_replace('\\', '/', $sClass);
        foreach ($this->m_arrLoaderDirectory as $sLoaderDirectory){
            $sTestPath = $sLoaderDirectory.$sClass.'.php';
            if (file_exists($sTestPath)){
                include $sTestPath;
            }
        }
    }


    public function getRootPath()
    {
        return $this->m_sRootPath;
    }


    public function getAppPath()
    {
        return $this->m_sAppPath.'/';
    }


    public function getRootVendorPath()
    {
        return $this->m_sRootPath.'Vendor/';
    }

}
