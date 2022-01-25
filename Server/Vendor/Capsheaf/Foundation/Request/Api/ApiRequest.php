<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-22 16:04:02 CST
 *  Description:     ApiRequest.php's function description
 *  Version:         1.0.0.20180422-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-22 16:04:02 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Request\Api;

use Capsheaf\Foundation\Request\Request;

class ApiRequest extends Request
{

    protected $m_arrParameters;


    public function __construct($sModule, $sController, $sAction, $arrParameters = [])
    {
        parent::__construct($sModule, $sController, $sAction);

        $this->m_arrParameters  = $arrParameters;
    }


    public function getParameters()
    {
        return $this->m_arrParameters;
    }

}
