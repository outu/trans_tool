<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-15 10:12:07 CST
 *  Description:     Hook.php's function description
 *  Version:         1.0.0.20180315-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-15 10:12:07 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Plugin;

class HookPoint
{

    protected $m_arrCallbacks = [];
    protected $m_bDoAction = false;


    public function __construct()
    {

    }


    public function addFilter($fnFunction, $nPriority = 10, $nAcceptedArgs = 1)
    {
        $this->m_arrCallbacks[$nPriority] = [
            'fn'    => $fnFunction,
            'argc'  => $nAcceptedArgs
        ];


    }


    public function hasFilter($fnCheckFunction = false)
    {

    }


    public function applyFilters($pipedValueToFilter, $arrExtraPassToFunction)
    {

        return $pipedValueToFilter;
    }


    public function doAction($arrExtraPassToFunction)
    {
        $this->m_bDoAction = true;

    }

}
