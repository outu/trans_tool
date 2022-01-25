<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:56 CST
 *  Description:     FacadesLoader.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:56 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Facades;

class FacadesLoader
{

    protected $m_arrFacadesAliases;
    protected $m_bAutoLoaderRegistered;


    public function __construct($arrFacadesAliases = [])
    {
        $this->m_arrFacadesAliases = $arrFacadesAliases;
    }


    /**
     * 注册的加载函数
     * @param string $sFacadesAlias
     */
    public function load($sFacadesAlias)
    {
        if (isset($this->m_arrFacadesAliases[$sFacadesAlias])){
            //立即注册一个别名进行加载
            class_alias($this->m_arrFacadesAliases[$sFacadesAlias], $sFacadesAlias);
        }
    }


    public function setFacadeAlias($sClassName, $sFacadesAlias)
    {
        $this->m_arrFacadesAliases[$sFacadesAlias] = $sClassName;
    }


    /**
     * 设置底层的全部别名
     * @param array $arrFacadesAliases
     */
    public function setFacadesAliases(array $arrFacadesAliases)
    {
        $this->m_arrFacadesAliases = $arrFacadesAliases;
    }


    /**
     * 获取全部的别名
     * @return array
     */
    public function getFacadesAliases()
    {
        return $this->m_arrFacadesAliases;
    }


    /**
     * 若未注册自动加载器，则加载
     */
    public function registerAutoLoader()
    {

        if (!$this->m_bAutoLoaderRegistered){
            $this->prependToLoaderStack();
            $this->m_bAutoLoaderRegistered = true;
        }
    }


    /**
     * 将load方法注册到自动加载栈前端
     */
    protected function prependToLoaderStack()
    {
        spl_autoload_register([$this, 'load'], true, true);
    }

}
