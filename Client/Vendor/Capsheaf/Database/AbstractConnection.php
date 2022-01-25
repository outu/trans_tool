<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 10:07:17 CST
 *  Description:     Connection.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 10:07:17 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

use Capsheaf\Utils\Types\Json;
use Capsheaf\Utils\Types\Str;
use Closure;
use DateTime;
use Exception;

abstract class AbstractConnection
{

    /**
     * 数据库连接句柄
     * @var resource|mixed
     */
    protected $m_hConnection;

    protected $m_sTablePrefix = '';

    /**
     * 当前是否在事务中
     * @var bool
     */
    protected $m_bInTransaction = false;

    /**
     * 数据库配置数组
     * @var array
     */
    protected $m_arrConfig;

    /**
     * 是否记录查询语句
     * @var bool
     */
    protected $m_bLogQueries = false;

    protected $m_sqlCompiler;


    public function __construct($arrConfig = [])
    {
        $this->m_arrConfig = $arrConfig;

        $this->m_hConnection = null;

        $this->useDefaultSqlCompiler();
    }


    /**
     * 记录到日志
     */
    public function enableLog()
    {
        $this->m_bLogQueries = false;

        return $this;
    }


    /**
     * 关闭记录到日志
     */
    public function disableLog()
    {
        $this->m_bLogQueries = true;

        return $this;
    }


    /**
     * @return SqlCompiler
     */
    public function getSqlCompiler()
    {
        return $this->m_sqlCompiler;
    }


    protected function getDefaultSqlCompiler()
    {
        return new SqlCompiler();
    }


    public function useDefaultSqlCompiler()
    {
        $this->m_sqlCompiler = $this->getDefaultSqlCompiler();

        return $this;
    }


    protected function logQuery($sSql, $arrBindings = [], $nTimeCost = 0)
    {
        app('log')->info("Sql: {$sSql} executed in {$nTimeCost}ms. Bindings:", $arrBindings);
    }


    protected function getTimeCost($nStart)
    {
        return round((microtime(true) - $nStart) * 1000, 3);
    }


    public function getConnection()
    {
        return $this->m_hConnection;
    }


    public function getConfig()
    {
        return $this->m_arrConfig;
    }


    public function setTablePrefix($sTablePrefix)
    {
        $this->m_sTablePrefix = $sTablePrefix;
        $this->getSqlCompiler()->setTablePrefix($sTablePrefix);

        return $this;
    }


    public function getTablePrefix()
    {
        return $this->m_sTablePrefix;
    }


    public function raw($sRawSql)
    {
        return new RawSql($sRawSql);
    }


    abstract public function reconnect($arrConfig = []);


    abstract public function disconnect();


    protected function connectIfNotConnected()
    {
        if (is_resource($this->m_hConnection)){
            return;
        }

        $this->reconnect();
    }


    /**
     * @param string|RawSql $from
     * @return QueryBuilder
     */
    public function table($from)
    {
        return (new QueryBuilder($this))->from($from);
    }


    /**
     * @param string $sSql
     * @param array $arrBindings
     * @return array
     */
    public function select($sSql, $arrBindings = [])
    {
        return $this->run(
            $sSql, $arrBindings, function ($sSql, $arrBindings = []){

                $statement = $this->prepare($sSql, $arrBindings);

                $this->bindValues($statement, $this->prepareBindings($arrBindings));

                $this->execute($statement, $arrBindings);

                return $this->fetchAll($statement);
            }
        );
    }


    public function & prepareBindings(&$arrBindings)
    {
        foreach ($arrBindings as $sColumn => $value){

            //(PHP 5 >= 5.5.0, PHP 7)才支持DateTimeInterface，但是使用instanceof不存在也不会报错
            if ($value instanceof DateTime || $value instanceof \DateTimeInterface){
                $arrBindings[$sColumn] = $value->format($this->getDateFormat());
            } elseif ($value === false) {
                $arrBindings[$sColumn] = 0;
            } elseif (is_array($value)){
                $arrBindings[$sColumn] = Json::toJson($value, true);
            } elseif (is_object($value) && method_exists($value, '__toString')){
                $arrBindings[$sColumn] = $value->__toString();
            }
        }

        return $arrBindings;
    }


    protected function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }


    abstract protected function prepare($sSql, $arrBindings = []);


    abstract protected function bindValues($statement, &$arrBindings = []);


    /**
     * @param mixed $statement
     * @param array $arrBindings
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    abstract protected function execute($statement, $arrBindings = []);


    /**
     * @param $statement
     * @return array 仅返回数组的形式，空数组表示没有结果
     */
    abstract protected function fetchAll($statement);


    public function selectOne($sSql, $arrBindings = [])
    {
        $arrResult = $this->select($sSql, $arrBindings);

        return array_shift($arrResult);
    }


    /**
     * 执行插入语句
     * @param string $sSql
     * @param array $arrBindings
     * @return bool
     */
    public function insert($sSql, $arrBindings = [])
    {
        return $this->statement($sSql, $arrBindings);
    }


    /**
     * 执行更新语句
     * @param string $sSql
     * @param array $arrBindings
     * @return int|string 返回影响的行数
     */
    public function update($sSql, $arrBindings = [])
    {
        return $this->affectingStatement($sSql, $arrBindings);
    }


    /**
     * 执行删除语句
     * @param string $sSql
     * @param array $arrBindings
     * @return int|string
     */
    public function delete($sSql, $arrBindings = [])
    {
        return $this->affectingStatement($sSql, $arrBindings);
    }


    /**
     * 执行Statement
     * @param string $sSql
     * @param array $arrBindings
     * @return bool 失败或者成功
     */
    public function statement($sSql, $arrBindings = [])
    {
        return $this->run(
            $sSql, $arrBindings, function ($sSql, $arrBindings){
                $statement = $this->prepare($sSql, $arrBindings);

                $this->bindValues($statement, $this->prepareBindings($arrBindings));

                return $this->execute($statement, $arrBindings);
            }
        );
    }


    /**
     * 执行Statement并返回影响的行数
     * @param string $sSql
     * @param array $arrBindings
     * @return int|string 返回影响的行数
     */
    public function affectingStatement($sSql, $arrBindings = [])
    {
        return $this->run(
            $sSql, $arrBindings, function ($sSql, $arrBindings){
                $statement = $this->prepare($sSql, $arrBindings);

                $this->bindValues($statement, $this->prepareBindings($arrBindings));

                $this->execute($statement, $arrBindings);

                return $this->getAffectedRowsCount($statement);
            }
        );
    }


    /**
     * 返回对于上次最近的操作影响的行数
     * @param $statement
     * @return int|string
     */
    abstract public function getAffectedRowsCount($statement);


    /**
     * 返回最近插入的记录的ID值（针对自动增长键）
     * @return int|string
     */
    abstract public function getLastInsertedId();


    public function beginTransaction()
    {

    }


    public function commit()
    {

    }


    public function rollBack()
    {

    }


    public function handleQueryException(QueryException $exception, $sSql, $arrBindings, Closure $fnQuery)
    {
        if ($this->m_bInTransaction){
            throw $exception;
        }

        return $this->tryAgainIfCausedByLostConnection($exception, $sSql, $arrBindings, $fnQuery);
    }


    /**
     * @param $sSql
     * @param $arrBindings
     * @param Closure $fnQuery
     * @return array|mixed
     */
    public function run($sSql, $arrBindings, Closure $fnQuery)
    {
        $this->connectIfNotConnected();

        $nStart = microtime(true);

        app('log')->debug('Executing: '.$sSql.', Bindings:', $arrBindings);

        try {
            $result = $this->runQueryCallback($sSql, $arrBindings, $fnQuery);
        } catch (QueryException $exception){
            $result = $this->handleQueryException($exception, $sSql, $arrBindings, $fnQuery);
        }


        if ($this->m_bLogQueries){
            $this->logQuery($sSql, $arrBindings, $this->getTimeCost($nStart));
        }


        return $result;
    }


    /**
     * @param string $sSql
     * @param array $arrBindings
     * @param Closure $fnQuery
     * @return mixed
     */
    protected function runQueryCallback($sSql, $arrBindings, Closure $fnQuery)
    {
        try {
            $result = $fnQuery($sSql, $arrBindings);
        } catch (Exception $exception){
            throw new QueryException($sSql, $arrBindings, $exception);
        }

        return $result;
    }


    /**
     * 若因为连接丢失，则重新执行
     * @param QueryException $exception
     * @param string $sSql
     * @param array $arrBindings
     * @param Closure $fnQuery
     * @return mixed
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $exception, $sSql, $arrBindings, Closure $fnQuery)
    {
        if ($this->isCausedByLostConnection($exception->getPrevious())){

            $this->reconnect();

            return $this->runQueryCallback($sSql, $arrBindings, $fnQuery);
        }


        //其它情况继续抛出
        throw $exception;
    }


    /**
     * 判断异常是否因为连接丢失的原因，根据不同的数据库类型子类中可以添加或者覆盖
     * @param Exception $exception
     * @return bool
     */
    protected function isCausedByLostConnection(Exception $exception)
    {
        $sMessage = $exception->getMessage();

        return Str::contains(
            $sMessage, [
                'server has gone away',
                'no connection to the server',
                'Lost connection',
                'is dead or not enabled',
                'Error while sending',
                'decryption failed or bad record mac',
                'server closed the connection unexpectedly',
                'SSL connection has been closed unexpectedly',
                'Error writing data to the connection',
                'Resource deadlock avoided',
                'Transaction() on null',
            ]
        );
    }
}
