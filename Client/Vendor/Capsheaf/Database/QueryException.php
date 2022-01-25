<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 09:47:37 CST
 *  Description:     QueryException.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 09:47:37 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

use Exception;
use RuntimeException;

class QueryException extends RuntimeException
{

    protected $m_sSql;

    protected $m_arrBindings;


    public function __construct($sSql, $arrBindings = [], Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->m_sSql = $sSql;
        $this->m_arrBindings = $arrBindings;

        $this->message = $previous->message;
    }


    public function getSql()
    {
        return $this->m_sSql;
    }


    public function getBindings()
    {
        return $this->m_arrBindings;
    }

}
