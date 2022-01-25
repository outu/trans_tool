<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:49 CST
 *  Description:     Log.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:49 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Facades\AutoLoad;

use Capsheaf\Facades\AbstractFacades;

class Log extends AbstractFacades
{

    protected static function getFacadeAccessor()
    {
        return 'log';
    }

}
