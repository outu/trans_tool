<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-12 16:22:46 CST
 *  Description:     Hooks.php's function description
 *  Version:         1.0.0.20180312-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-12 16:22:46 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Plugin;

use Capsheaf\Application\Application;

class Hooks
{

    public $m_app;
    public $m_arrFilters = [];


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    public function addAction($sTag, $fnFunction, $nPriority = 10, $nAcceptedArgs = 1)
    {
        $this->addFilter($sTag, $fnFunction, $nPriority, $nAcceptedArgs);
    }


    public function doAction($sTag)
    {

    }


    public function didAction($sTag)
    {

    }


    public function addFilter($sTag, $fnFunction, $nPriority = 10, $nAcceptedArgs = 1)
    {
        if (!isset($this->m_arrFilters[$sTag])){
            $this->m_arrFilters[$sTag] = new HookPoint();
        }

        $this->m_arrFilters[$sTag]->addFilter($fnFunction, $nPriority, $nAcceptedArgs);
    }


    /**
     * 判断指定的钩子是否有过滤器函数
     * @param string $sTag
     * @param callable|string|bool $fnCheckFunction 要检测的方法，为false表示不检查函数是否存在于钩子函数列表中
     * @return bool
     */
    public function hasFilter($sTag, $fnCheckFunction = false)
    {
        if (!isset($this->m_arrFilters[$sTag])){
            return false;
        }

        return $this->m_arrFilters[$sTag]->hasFilter($fnCheckFunction);
    }


    public function applyFilters($sTag, $pipedValueToFilter, $arrExtraPassToFunction)
    {

        return $pipedValueToFilter;
    }

}
