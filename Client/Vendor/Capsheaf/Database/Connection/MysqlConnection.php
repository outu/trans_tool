<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 11:03:45 CST
 *  Description:     MysqlConnection.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 11:03:45 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database\Connection;

use Capsheaf\Database\AbstractConnection;
use Capsheaf\Database\SqlCompiler\MysqlSqlCompiler;
use Capsheaf\Utils\Types\Arr;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class MysqlConnection extends AbstractConnection
{

    protected $m_sServer;
    protected $m_nPort;
    protected $m_sSocket;
    protected $m_sUser;
    protected $m_sPassword;
    protected $m_sDatabase;
    protected $m_sCharset;
    protected $m_sCollation;
    protected $m_sModes;
    protected $m_bStrict;
    protected $m_sTimeZone;


    public function __construct(array $arrConfig = [])
    {
        parent::__construct($arrConfig);

        $this->m_sServer    = Arr::get($this->m_arrConfig, 'server', '127.0.0.1');

        $nPort      = (int)Arr::get($this->m_arrConfig, 'port');
        $sSocket    = Arr::get($this->m_arrConfig, 'socket');


        if (empty($nPort) && !empty($sSocket)){
            $this->m_nPort = null;
            $this->m_sSocket = $sSocket;
        } else {
            $nPort = empty($nPort) ? '3306' : $nPort;
            $this->m_nPort = $nPort;
            $this->m_sSocket = null;
        }

        $this->m_sUser      = Arr::get($this->m_arrConfig, 'user', '');
        $this->m_sPassword  = Arr::get($this->m_arrConfig, 'password', '');
        $this->m_sDatabase  = Arr::get($this->m_arrConfig, 'database', '');

        $this->m_sCharset   = (string)Arr::get($this->m_arrConfig, 'charset', 'utf8');
        $this->m_sCollation = (string)Arr::get($this->m_arrConfig, 'collation', 'utf8_unicode_ci');
        $this->m_sTimeZone  = Arr::get($this->m_arrConfig, 'timezone', null);
        $this->m_sModes     = Arr::get($this->m_arrConfig, 'modes', null);
        $this->m_bStrict    = Arr::get($this->m_arrConfig, 'strict', false);
    }


    protected function getDefaultSqlCompiler()
    {
        return new MysqlSqlCompiler();
    }


    public function reconnect($arrConfig = [])
    {
        try{
            app('log')->debug("Connecting MySql server:{$this->m_sServer}...");
            $this->m_hConnection = mysqli_connect($this->m_sServer, $this->m_sUser, $this->m_sPassword, $this->m_sDatabase, $this->m_nPort, $this->m_sSocket);

            $this->setEncoding($this->m_sCharset, $this->m_sCollation);
            $this->setTimeZone($this->m_sTimeZone);
            $this->setModes($this->m_sModes, $this->m_bStrict);

        } catch (Exception $exception){
            throw new RuntimeException("Connect MySQL server {$this->m_sServer} failed: ".mysqli_connect_error());
        }
    }


    protected function setEncoding($sEncoding, $sCollation)
    {
        if ($sEncoding !== ''){
            $sSql = "set names '{$sEncoding}'";
            if ($sCollation !== ''){
                $sSql .= " collate '{$sCollation}'";
            }

            return mysqli_query($this->m_hConnection, $sSql);
        }

        return true;
    }


    protected function setModes($sModes, $bStrict = false)
    {
        if (!empty($sModes)){
            $sSql = "set session sql_mode='{$sModes}'";
        } elseif ($bStrict){
            $sSql = "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
        } else {
            return true;
        }

        return mysqli_query($this->m_hConnection, $sSql);
    }


    protected function setTimeZone($sTimeZone)
    {
        if (!empty($sTimeZone)){
            $sSql = "set time_zone='{$sTimeZone}'";

            return mysqli_query($this->m_hConnection, $sSql);
        }

        return true;
    }


    public function disconnect()
    {
        if (is_resource($this->m_hConnection)){
            mysqli_close($this->m_hConnection);
        }

        $this->m_hConnection = null;
    }


    protected function prepare($sSql, $arrBindings = [])
    {
        $statement = mysqli_prepare($this->m_hConnection, $sSql);

        if ($statement === false){
            $this->throwLastErrorException($this->m_hConnection);
        }

        return $statement;
    }


    /**
     *
     * @param $statement
     * @param array $arrBindings
     * @return bool
     * @see https://secure.php.net/manual/zh/mysqli-stmt.bind-param.php
     */
    protected function bindValues($statement, &$arrBindings = [])
    {
        if (count($arrBindings) == 0){
            return true;
        }

        $sBindTypes = implode(
            '', array_map(
                function($value){
                    if (is_string($value)){
                        return 's';
                    } elseif (is_int($value)) {
                        return 'i';
                    } elseif (is_double($value)) {
                        return 'd';
                    } else {
                        return 'b';
                    }
                }, $arrBindings
            )
        );

        array_unshift($arrBindings, $statement, $sBindTypes);

        return call_user_func_array('mysqli_stmt_bind_param', $this->makeValuesReferenced($arrBindings));
    }


    private function makeValuesReferenced($arrBindings)
    {
        $arrRefs = [];
        foreach($arrBindings as $key => $value){
            $arrRefs[$key] = &$arrBindings[$key];
        }

        return $arrRefs;
    }


    /**
     * @param \mysqli $hConnection
     * @throws RuntimeException|InvalidArgumentException
     */
    protected function throwLastErrorException($hConnection)
    {
        if (!empty($hConnection)){
            throw new RuntimeException("Mysql error: [".mysqli_errno($hConnection)."]".mysqli_error($hConnection));
        }

        throw new InvalidArgumentException("Not a valid mysql connection handle.");
    }


    /**
     * @param \mysqli_stmt $statement
     * @param array $arrBindings
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    protected function execute($statement, $arrBindings = [])
    {
        return mysqli_stmt_execute($statement);
    }


    /**
     * @param \mysqli_stmt $statement
     * @return array 仅返回数组的形式，空数组表示没有结果
     */
    protected function fetchAll($statement)
    {
        $arrResult = [];

        $result = mysqli_stmt_get_result($statement);

        while ($arrRow = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            $arrResult[] = $arrRow;
        }

        return $arrResult;
    }


    /**
     * @param $statement
     * @return int|string If the number of affected rows is greater than maximal PHP int value, the number of affected rows will be returned as a string value.
     */
    public function getAffectedRowsCount($statement)
    {
        //Returns the number of rows affected by INSERT, UPDATE, or DELETE query.
        //This function only works with queries which update a table. In order to get the number of rows from a SELECT query, use mysqli_stmt_num_rows() instead.
        return mysqli_stmt_affected_rows($statement);
    }


    /**
     * 返回最近插入的记录的ID值（针对自动增长键）
     * @return int|string
     */
    public function getLastInsertedId()
    {
        $nId = mysqli_insert_id($this->m_hConnection);
        return is_numeric($nId) ? (int)$nId : $nId;
    }


    protected function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

}
