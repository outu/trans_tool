<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-09 00:34:00 CST
 *  Description:     SqlComplier.php's function description
 *  Version:         1.0.0.20180509-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-09 00:34:00 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

class SqlCompiler
{

    protected $m_sTablePrefix = '';

    protected $m_arrOperators = [

    ];


    protected $m_arrSelectComponents = [
        'aggregate',
        'columns',
        'from',
        'wheres',
        'groups',
        'orders',
        'limit',
        'offset',

    ];


    public function getTablePrefix()
    {
        return $this->m_sTablePrefix;
    }


    public function setTablePrefix($sTablePrefix)
    {
        $this->m_sTablePrefix = $sTablePrefix;
    }


    public function getOperators()
    {
        return $this->m_arrOperators;
    }


    protected function compileComponents(QueryBuilder $queryBuilder)
    {
        $arrSql = [];

        foreach ($this->m_arrSelectComponents as $sSelectComponent){
            $sMethodInBuilder = 'm_component'.ucfirst($sSelectComponent);
            if (!is_null($queryBuilder->$sMethodInBuilder)){
                $sMethod = 'compile'.ucfirst($sSelectComponent);

                $arrSql[$sSelectComponent] = $this->$sMethod($queryBuilder, $queryBuilder->$sMethodInBuilder);
            }
        }

        return $arrSql;
    }


    public function compileSelect(QueryBuilder $queryBuilder)
    {
        $arrOldColumns = $queryBuilder->m_componentColumns;
        if (is_null($arrOldColumns)){
            $queryBuilder->m_componentColumns = ['*'];
        }

        $sSql = $this->concatenate(
            $this->compileComponents($queryBuilder)
        );

        $queryBuilder->m_componentColumns = $arrOldColumns;

        return $sSql;
    }


    public function compileExists(QueryBuilder $queryBuilder)
    {
        $sSubSelectSql = $this->compileSelect($queryBuilder);

        //??????exists???????????????????????????wrap????????????????????????????????????????????????
        return "SELECT EXISTS({$sSubSelectSql}) AS ".$this->wrap('exists');
    }


    public function compileAggregate(QueryBuilder $queryBuilder, $arrAggregate = [])
    {
        $sColumns = $this->expandColumns($arrAggregate['columns']);

        //https://www.w3resource.com/mysql/aggregate-functions-and-grouping/aggregate-functions-and-grouping-count-with-distinct.php
        $sSelect = ($queryBuilder->m_componentDistinct && $sColumns != '*') ? 'SELECT DISTINCT ' : 'SELECT ';

        //??????????????????????????????aggregate??????????????????COUNT(ColA, ColB)?????????
        return $sSelect.$arrAggregate['function']."({$sColumns}) AS aggregate";
    }


    public function compileColumns(QueryBuilder $queryBuilder, $arrColumns = [])
    {
        //????????????????????????SQL???COUNT,AVG???Aggregate???????????????????????????????????????
        if (!is_null($queryBuilder->m_componentAggregate)){
            return '';
        }

        $sSelect = $queryBuilder->m_componentDistinct ? 'SELECT DISTINCT ' : 'SELECT ';

        return $sSelect.$this->expandColumns($arrColumns);
    }


    public function compileFrom(QueryBuilder $queryBuilder, $sFromTable)
    {
        return 'FROM '.$this->wrapTable($sFromTable);
    }


    public function compileWheres(QueryBuilder $queryBuilder)
    {
        if (is_null($queryBuilder->m_componentWheres)){
            return '';
        }

        $arrWhereConditionSqlList = array_map(
            function ($arrSingleWhereCondition) use ($queryBuilder){
                return $arrSingleWhereCondition['boolean'].' '.$this->{"where{$arrSingleWhereCondition['type']}"}($queryBuilder, $arrSingleWhereCondition);
            }, $queryBuilder->m_componentWheres
        );

        if (count($arrWhereConditionSqlList) > 0){
            return $this->concatenateWhereConditionSqlList($arrWhereConditionSqlList);
        }

        return '';
    }


    protected function concatenateWhereConditionSqlList($arrWhereConditionSqlList)
    {
        $sWhereConditionsSql = preg_replace('/AND |OR /i', '', implode(' ', $arrWhereConditionSqlList), 1);

        return 'WHERE '.$sWhereConditionsSql;
    }


    protected function whereBasic(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        $sValue = $this->handleValue($arrSingleWhereCondition['value']);

        return $this->wrap($arrSingleWhereCondition['column']).' '.$arrSingleWhereCondition['operator'].' '.$sValue;
    }


    protected function whereRaw(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        return $arrSingleWhereCondition['sql'];
    }


    protected function whereBetween(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        $sBetween = $arrSingleWhereCondition['not'] ? 'NOT BETWEEN' : 'BETWEEN';

        return $this->wrap($arrSingleWhereCondition['column']).' '.$sBetween.' ? AND ?';
    }


    protected function whereNull(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        return $this->wrap($arrSingleWhereCondition['column']).' IS NULL';
    }


    protected function whereNotNull(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        return $this->wrap($arrSingleWhereCondition['column']).' IS NOT NULL';
    }


    /**
     * ??????WHERE ???????????????????????????????????????????????????WHERE ???(ACondition AND BCondition)??? OR OuterCondition
     * @param QueryBuilder $queryBuilder
     * @param array $arrSingleWhereCondition
     * @return string
     */
    protected function whereNested(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        //??????????????????WHERE ???
        $nOffset = strlen('WHERE ');

        return '('.substr($this->compileWheres($arrSingleWhereCondition['queryBuilder']), $nOffset).')';
    }


    /**
     * @param RawSql|string $value
     * @return string
     */
    public function handleValue($value)
    {
        return $this->isRawSql($value) ? $this->getRawSql($value) : '?';
    }


    protected function whereIn(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        if (!empty($arrSingleWhereCondition['range'])){
            return $this->wrap($arrSingleWhereCondition['column']).' IN ('.$this->expandValues($arrSingleWhereCondition['range']).')';
        }

        return '0 = 1';
    }


    protected function whereNotIn(QueryBuilder $queryBuilder, $arrSingleWhereCondition)
    {
        if (!empty($arrSingleWhereCondition['range'])){
            return $this->wrap($arrSingleWhereCondition['column']).' NOT IN ('.$this->expandValues($arrSingleWhereCondition['range']).')';
        }

        return '1 = 1';
    }


    /**
     * @param RawSql $rawSql
     * @return string
     */
    public function getRawSqlValue(RawSql $rawSql)
    {
        return $rawSql->getSql();
    }


    public function compileGroups(QueryBuilder $queryBuilder, $arrColumns = [])
    {
        return 'GROUP BY '.$this->expandColumns($arrColumns);
    }


    public function compileOrders(QueryBuilder $queryBuilder, $arrOrders = [])
    {
        if (!empty($arrOrders)){
            $arrOrderByList = array_map(
                function ($arrOrders){
                    return isset($arrOrders['sql']) ? $arrOrders['sql'] : $this->wrap($arrOrders['column']).' '.$arrOrders['direction'];
                }, $arrOrders
            );

            return 'ORDER BY '.implode(' ', $arrOrderByList);
        }

        return '';
    }


    public function compileLimit(QueryBuilder $queryBuilder, $nLimit)
    {
        return 'LIMIT '.(int)$nLimit;
    }


    public function compileOffset(QueryBuilder $queryBuilder, $nOffset)
    {
        return 'OFFSET '.(int)$nOffset;
    }


    protected function expandColumns($arrColumns)
    {
        return implode(', ', array_map([$this, 'wrap'], $arrColumns));
    }


    /**
     * ??????????????????????????????????????????SQL??????
     * @param RawSql|string $sql
     * @return bool
     */
    public function isRawSql($sql)
    {
        return $sql instanceof RawSql;
    }


    public function wrapTable($sTable)
    {
        if (!$this->isRawSql($sTable)){
            return $this->wrap($this->m_sTablePrefix.$sTable, true);
        }

        return $this->getRawSqlValue($sTable);
    }


    /**
     * ??????SQL????????????/?????????????????????????????????????????????????????????MySql????????????backquote/backticks???SqlServer??????[]
     * @param string $sName ??????????????????
     * @return string
     */
    public function wrapName($sName)
    {
        if ($sName !== '*'){
            return '"'.str_replace('"', '""', $sName).'"';
        }

        return $sName;
    }


    public function wrap($sName, $bAliasAddPrefix = false)
    {
        //????????????SQL?????????????????????
        if ($this->isRawSql($sName)){
            return $this->getRawSql($sName);
        }

        if (strpos(strtolower($sName), ' as ') !== false){
            return $this->wrapAliasedColumn($sName, $bAliasAddPrefix);
        }

        return $this->wrapColumnParts(explode('.', $sName));
    }


    /**
     * ?????????????????????????????????: ???name as n??? =>???"name as n"?????????????????????????????????"name as tf_n"???
     * @param string $sColumn
     * @param bool $bAliasAddPrefix
     * @return string
     */
    public function wrapAliasedColumn($sColumn, $bAliasAddPrefix = false)
    {
        $arrParts = preg_split('/\s+as\s+/i', $sColumn);

        if ($bAliasAddPrefix){
            //????????????????????????
            $arrParts[1] = $this->m_sTablePrefix.$arrParts[1];
        }

        return $this->wrap(
            $arrParts[0].' as '.$this->wrapName($arrParts[1])
        );
    }


    /**
     * ???????????????????????????????????????.????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
     * @param array $arrOneColumnParts
     * @return string
     */
    public function wrapColumnParts($arrOneColumnParts)
    {
        $arrWrappedParts = array_map(
            function($sPart, $nIndex) use ($arrOneColumnParts){
                //???????????????????????????
                if (count($arrOneColumnParts) > 1 && $nIndex == 0){
                    return $this->wrapTable($sPart);
                }

                return $this->wrapName($sPart);

            }, $arrOneColumnParts, array_keys($arrOneColumnParts)
        );

        return implode('.', $arrWrappedParts);
    }


    /**
     * @param RawSql $rawSql
     * @return string
     */
    public function getRawSql(RawSql $rawSql)
    {
        return $rawSql->getSql();
    }


    /**
     * ??????????????????????????????
     * @param string[] $arrParts
     * @return string
     */
    public function concatenate($arrParts)
    {
        return implode(
            ' ', array_filter(
                $arrParts, function ($sParts){
                    //?????????????????????
                    return (string)$sParts !== '';
                }
            )
        );
    }


    public function compileInsert(QueryBuilder $queryBuilder, $arrKVPairs)
    {
        $sTable = $this->wrapTable($queryBuilder->m_componentFrom);

        //?????????????????????????????????????????????????????????
        $sColumns = $this->expandColumns(array_keys(reset($arrKVPairs)));

        $sValuesList = implode(
            ', ',
            array_map(
                function ($arrKVPair){
                    return '('.$this->expandValues($arrKVPair).')';
                }, $arrKVPairs
            )
        );

        return "INSERT INTO {$sTable} ({$sColumns}) VALUES {$sValuesList}";
    }


    public function compileUpdate(QueryBuilder $queryBuilder, $arrKVPair)
    {
        $sTable = $this->wrapTable($queryBuilder->m_componentFrom);

        $sSetValueList = implode(
            ', ',
            array_map(
                function ($value, $sColumn){
                    return $this->wrap($sColumn).' = '.$this->handleValue($value);
                }, $arrKVPair, array_keys($arrKVPair)
            )
        );

        $sWhereConditionsSql = $this->compileWheres($queryBuilder);

        return "UPDATE {$sTable} SET {$sSetValueList} {$sWhereConditionsSql}";
    }


    public function expandValues($arrValues = [])
    {
        return implode(', ', array_map([$this, 'handleValue'], $arrValues));
    }


    public function compileDelete(QueryBuilder $queryBuilder)
    {
        $sWhereConditionsSql = $this->compileWheres($queryBuilder);

        return rtrim('DELETE FROM '.$this->wrapTable($queryBuilder->m_componentFrom).' '.$sWhereConditionsSql);
    }

}
