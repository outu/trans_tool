<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:47 CST
 *  Description:     FileSystem.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:47 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Facades\AutoLoad;

use Capsheaf\Facades\AbstractFacades;

class FileSystem extends AbstractFacades
{

    protected static function getFacadeAccessor()
    {
        return 'filesystem';
    }

}
