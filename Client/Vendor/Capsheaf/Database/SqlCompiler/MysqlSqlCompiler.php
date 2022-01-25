<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 22:52:41 CST
 *  Description:     MysqlQueryBuilder.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 22:52:41 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database\SqlCompiler;

use Capsheaf\Database\SqlCompiler;

class MysqlSqlCompiler extends SqlCompiler
{

    protected $m_arrOperators = [

    ];


    public function wrapName($sName)
    {
        if ($sName !== '*'){
            return '`'.str_replace('`', '``', $sName).'`';
        }

        return $sName;
    }

}
