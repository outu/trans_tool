<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-09 16:57:23 CST
 *  Description:     RawSql.php's function description
 *  Version:         1.0.0.20180509-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-09 16:57:23 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

/**
 * Class RawSql
 * 原始SQL语句，表示不解析/编译它
 * @package Capsheaf\Database
 */
class RawSql
{

    /**
     * @var string
     */
    protected $m_sSql;


    public function __construct($sSql)
    {
        $this->m_sSql = $sSql;
    }


    public function getSql()
    {
        return $this->m_sSql;
    }


    /**
     * 取得一个新的实例
     * @param string $sSql
     * @return static
     */
    public static function create($sSql)
    {
        return new static($sSql);
    }


    public function __toString()
    {
        return (string)$this->getSql();
    }

}
