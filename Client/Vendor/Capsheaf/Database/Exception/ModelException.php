<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-31 14:04:36 CST
 *  Description:     ModelException.php's function description
 *  Version:         1.0.0.20180531-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-31 14:04:36 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database\Exception;

use RuntimeException;

class ModelException extends RuntimeException
{

    public function __construct($sMessage = '', $nCode = 400, $previous = null)
    {
        parent::__construct($sMessage, $nCode, $previous);
    }

}
