<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 20:06:54 CST
 *  Description:     Db.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 20:06:54 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Facades\AutoLoad;

use Capsheaf\Database\QueryBuilder;
use Capsheaf\Facades\AbstractFacades;

/**
 * Class DB
 * @package Capsheaf\Facades\AutoLoad
 * @method static QueryBuilder table($sTable);
 * @method static array select($sSql, $arrBindings = [])
 * @method static array selectOne($sSql, $arrBindings = [])
 * @method static bool insert($sSql, $arrBindings = [])
 * @method static int|string update($sSql, $arrBindings = [])
 * @method static int|string delete($sSql, $arrBindings = [])
 * @method static bool statement($sSql, $arrBindings = [])
 * @method static bool affectingStatement($sSql, $arrBindings = [])
 * @method static int|string getAffectedRowsCount($statement);
 * @method static int|string getLastInsertedId();
 */
class DB extends AbstractFacades
{

    protected static function getFacadeAccessor()
    {
        return 'db';
    }

}
