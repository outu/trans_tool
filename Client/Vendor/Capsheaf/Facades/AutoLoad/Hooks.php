<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 10:09:16 CST
 *  Description:     Hook.php's function description
 *  Version:         1.0.0.20180314-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-14 10:09:16 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Facades\AutoLoad;

use Capsheaf\Facades\AbstractFacades;

class Hooks extends AbstractFacades
{

    protected static function getFacadeAccessor()
    {
        return 'hooks';
    }

}
