<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-21 19:44:30 CST
 *  Description:     ApiResponse.php's function description
 *  Version:         1.0.0.20180421-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-21 19:44:30 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Response\Api;

use Capsheaf\Foundation\Response\Response;

class ApiResponse extends Response
{

    protected $m_responseData;


    public function __construct($responseData = null)
    {
        $this->m_responseData = $responseData;
    }


    public function send()
    {
        return $this->m_responseData;
    }

}
