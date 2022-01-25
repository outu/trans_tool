<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 22:52:16 CST
 *  Description:     QueryBuilder.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 22:52:16 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

use Capsheaf\Utils\Types\Arr;
use Closure;
use InvalidArgumentException;

class QueryBuilder
{

    /**
     * 连接
     * @var AbstractConnection
     */
    protected $m_connection;

    /**
     * SQL编译器
     * @var SqlCompiler
     */
    protected $m_sqlCompiler;

    /**
     * FROM的数据表
     * @var string
     */
    public $m_componentFrom;

    /**
     * DISTINCT
     * @var bool
     */
    public $m_componentDistinct = false;

    /**
     * COUNT AVG等，形式如：['function' => 'COUNT', 'columns' => ['*']]
     * @var array
     */
    public $m_componentAggregate;

    /**
     * SELECT要筛选的字段列表
     * @var array
     */
    public $m_componentColumns;

    /**
     * WHERE部分语句
     * @var array
     */
    public $m_componentWheres;

    /**
     * GROUP BY部分语句
     * @var array
     */
    public $m_componentGroups;

    /**
     * HAVING
     * @var array
     */
    public $m_componentHaving;

    /**
     * ORDER
     * @var array
     */
    public $m_componentOrders;

    /**
     * OFFSET
     * @var int
     */
    public $m_componentOffset;

    /**
     * LIMIT
     * @var int
     */
    public $m_componentLimit;

    protected $m_arrOperators = [
        '=',        //Equal
        '<>',       //Not equal. Note: In some versions of SQL this operator may be written as !=
        '>',        //Greater than
        '<',        //Less than
        '>=',       //Greater than or equal
        '<=',       //Less than or equal
        'BETWEEN', 	//Between an inclusive range
        'LIKE',     //Search for a pattern
        'IN',       //To specify multiple possible values for a column'
    ];

    /**
     * 每种组件各自的绑定，用于生成 ?,?,?,? 和绑定的具体值
     * @var array
     */
    public $m_arrBindings = [
        'select'    => [],
        'join'      => [],
        'where'     => [],
        'having'    => [],
        'order'     => [],
        'union'     => [],
    ];


    public function __construct(AbstractConnection $connection, SqlCompiler $sqlCompiler = null)
    {
        $this->m_connection = $connection;
        $this->m_sqlCompiler = $sqlCompiler ?: $connection->getSqlCompiler();
    }


    public function clear()
    {
        //清除绑定
        foreach ($this->m_arrBindings as $sKey => $value){
            $this->m_arrBindings[$sKey] = [];
        }
    }


    public function getColumns()
    {
        return $this->m_componentColumns;
    }


    public function setColumns($arrColumns = [])
    {
        $this->m_componentColumns = $arrColumns;
    }


    /**
     * 指定SELECT的字段（注意会覆盖以前设置的字段集合）
     * @param array|string|string[]|RawSql|RawSql[] $arrColumns 字段列表，形式为：['a','b']或'a'或'a','b','c'
     * @return $this
     */
    public function select($arrColumns = ['*'])
    {
        $this->m_componentColumns = is_array($arrColumns) ? $arrColumns : func_get_args();

        return $this;
    }


    /**
     * 添加一个原始SQL语句作为SELECT出的一个字段，并在绑定中添加需要绑定的值
     * @param string $sRawSql
     * @param array $arrBindings
     * @return $this
     */
    public function selectRaw($sRawSql, $arrBindings = [])
    {
        $this->addSelectColumn(new RawSql($sRawSql));

        if ($arrBindings){
            $this->addBinding($arrBindings, 'select');
        }

        return $this;
    }


    /**
     * 添加一个/多个SELECT的字段（包含RawSql类型的字段来保留原样）
     * @param string|string[]|RawSql|RawSql[] $arrColumns SELECT 的字段字符串或者RawSql的单个值或者数组集合，或者指定为多个参数
     * @return $this
     */
    public function addSelectColumn($arrColumns)
    {
        $arrColumns = is_array($arrColumns) ? $arrColumns : func_get_args();

        $this->m_componentColumns = array_merge((array)$this->m_componentColumns, $arrColumns);

        return $this;
    }


    /**
     * 添加一个子查询，与selectRaw不同的是它可以通过一个新的QueryBuilder来构建一个完整的SELECT查询，可以使用内在的API。
     * @param QueryBuilder|Closure|string $subQueryBuilderOrFnCallbackOrRawSql 一个新的QueryBuilder实例，或者直接就是纯SQL语句(不能绑定)，或者是一个回调函数，由程序自动创建一个QueryBuilder实例传入回调函数的第一个参数，可以在回调函数中对该实例进行操作
     * @param string $sSelectAsName 别名
     * @return QueryBuilder
     * 例如： <br>
     * DB::table('users')->select('id')->selectSub(function($queryBuilder){ <br>
     *      $queryBuilder->select('some')->from('roles')->where(); <br>
     * }, 'roleTB') <br>
     * ->get(); <br>
     *
     * 例如： <br>
     * USE AdventureWorks2016; <br>
     * GO <br>
     * SELECT Ord.SalesOrderID, Ord.OrderDate, <br>
     *      (SELECT MAX(OrdDet.UnitPrice) <br>
     *       FROM Sales.SalesOrderDetail AS OrdDet <br>
     *       WHERE Ord.SalesOrderID = OrdDet.SalesOrderID) AS MaxUnitPrice <br>
     * FROM Sales.SalesOrderHeader AS Ord; <br>
     * GO <br>
     * @see https://docs.microsoft.com/en-us/sql/relational-databases/performance/subqueries?view=sql-server-2017
     */
    public function selectSub($subQueryBuilderOrFnCallbackOrRawSql, $sSelectAsName)
    {
        if ($subQueryBuilderOrFnCallbackOrRawSql instanceof Closure){
            $fnCallback = $subQueryBuilderOrFnCallbackOrRawSql;

            $fnCallback($subQueryBuilderOrFnCallbackOrRawSql = $this->newQueryBuilder());
        }

        list($sRawSql, $arrBindings) = $this->parseSubSelect($subQueryBuilderOrFnCallbackOrRawSql);

        return $this->selectRaw(
            '('.$sRawSql.') AS '.$this->m_sqlCompiler->wrap($sSelectAsName),
            $arrBindings
        );
    }


    /**
     * 根据当前的连接和编译器新建一个新的QueryBuilder
     * @return QueryBuilder
     */
    public function newQueryBuilder()
    {
        return new static($this->m_connection, $this->m_sqlCompiler);
    }


    /**
     * 克隆一个当前的QueryBuilder实例
     * @param array $arrExceptComponents 除去的Components
     * @param array $arrExceptBindings 除去的Bindings
     * @return QueryBuilder
     */
    public function cloneQueryBuilder($arrExceptComponents = [], $arrExceptBindings = [])
    {
        $clone = clone $this;

        //去除成员变量形式的component
        foreach ($arrExceptComponents as $sExceptionComponent){
            $sExceptionComponent = 'm_component'.ucfirst($sExceptionComponent);
            $clone->{$sExceptionComponent} = null;
        }

        //去除arrBindings中的某项绑定
        foreach ($arrExceptBindings as $sExceptBinding){
            $this->m_arrBindings[$sExceptBinding] = [];
        }

        return $clone;
    }


    /**
     * @param QueryBuilder|string $subQueryBuilderOrRawSql
     * @return array
     * @throws InvalidArgumentException
     */
    public function parseSubSelect($subQueryBuilderOrRawSql)
    {
        if ($subQueryBuilderOrRawSql instanceof self){
            return [$subQueryBuilderOrRawSql->getSelectSql(), $subQueryBuilderOrRawSql->getBindingsList()];
        } elseif (is_string($subQueryBuilderOrRawSql)) {
            return [$subQueryBuilderOrRawSql, []];
        } else {
            throw new InvalidArgumentException('Invalid sub select.');
        }
    }


    /**
     * 例如：
     * SELECT DISTINCT <column_name>
     * FROM <table_name>
     * WHERE <conditions>;
     * @return $this
     */
    public function distinct()
    {
        $this->m_componentDistinct = true;

        return $this;
    }


    /**
     * FROM 如：'MyTable'或者 RawSql('Tb1, Tb2') 或者 RawSql(子Select)
     * @param string|RawSql $from
     * @return $this
     */
    public function from($from)
    {
        $this->m_componentFrom = $from;

        return $this;
    }


    public function raw($sRawSql)
    {
        return $this->m_connection->raw($sRawSql);
    }


    /**
     * 获取查询的全部记录
     * @param array $arrColumns 这里的指定字段优先级更高，但是注意不会覆盖原来的select中指定的字段
     * @return array 注意仅返回数组的形式
     */
    public function get($arrColumns = ['*'])
    {
        //临时备份字段设置
        $arrOldColumns = $this->m_componentColumns;

        //同select函数
        $this->m_componentColumns = is_array($arrColumns) ? $arrColumns : func_get_args();

        //执行SELECT查询
        $arrResult = $this->runSelect();

        //恢复临时字段设置
        $this->m_componentColumns = $arrOldColumns;

        return $arrResult;
    }


    /**
     * 获取第一条记录，没有则返回null
     * @param string|string[] $arrColumns
     * @return array|null 返回第一条记录构成的数组或者null表示没有
     */
    public function first($arrColumns = ['*'])
    {
        //同select函数
        $arrColumns = is_array($arrColumns) ? $arrColumns : func_get_args();

        return Arr::first($this->limit(1)->get($arrColumns));
    }


    /**
     * 根据id查询一条记录
     * @param int|string $nId
     * @param array|null $arrColumns 返回查询的记录或者null
     * @return array|null 返回第一条记录构成的数组或者null表示没有
     */
    public function find($nId, $arrColumns = ['*'])
    {
        return $this->where('id', '=', $nId)->first($arrColumns);
    }


    /**
     * 获取第一条记录的对应单个字段直接的值
     * @param string $sColumn
     * @param null|mixed $default
     * @return string|mixed 返回查询字段的值或者在没有查询到的情况下返回指定的默认值
     */
    public function value($sColumn, $default = null)
    {
        //返回的形式通过转换为数组，结果如：['name' => 'tsoftware']或者表示没有的空数组[]，其中注意(array)null结果为空数组而不是带有null元素的数组。
        $arrFirst = (array)$this->first([$sColumn]);

        //注意字段的获取不使用数组形式，而将这条First记录当作一个仅含一个元素的数组
        return count($arrFirst) ? reset($arrFirst) : $default;
    }


    public function plunk($sColumn)
    {

    }


    /**
     * ORDER BY
     * @param string $sColumn
     * @param string $sDirection
     * @return $this
     */
    public function orderBy($sColumn, $sDirection = 'ASC')
    {
        $this->m_componentOrders[] = [
            'column'    => $sColumn,
            'direction' => $sDirection,
        ];

        return $this;
    }


    /**
     * ORDER BY 原始的SQL串，可以绑定变量
     * @param string $sSql
     * @param array $arrBindings
     * @return $this
     */
    public function orderByRaw($sSql, $arrBindings = [])
    {
        $this->m_componentOrders[] = [
            'type'      => 'Raw',
            'sql'    => $sSql,
        ];

        $this->addBinding($arrBindings, 'order');

        return $this;
    }


    /**
     * ORDER BY XXX DESC
     * @param string $sColumn
     * @return QueryBuilder
     */
    public function orderByDesc($sColumn)
    {
        return $this->orderBy($sColumn, 'DESC');
    }


    /**
     * 根据字段排序，最新的记录放在结果集前
     * @param string $sColumn
     * @return QueryBuilder
     */
    public function latest($sColumn = 'created_at')
    {
        return $this->orderBy($sColumn, 'DESC');
    }


    /**
     * 根据字段排序，最旧的记录放在结果集前面
     * @param string $sColumn
     * @return QueryBuilder
     */
    public function oldest($sColumn = 'created_at')
    {
        return $this->orderBy($sColumn, 'ASC');
    }


    public function chunk()
    {

    }


    public function count($sColumn = '*')
    {
        return (int)$this->aggregate(__FUNCTION__, [$sColumn]);
    }


    public function max($sColumn = '*')
    {
        return $this->aggregate(__FUNCTION__, [$sColumn]);
    }


    public function min($sColumn = '*')
    {
        return $this->aggregate(__FUNCTION__, [$sColumn]);
    }


    public function avg($sColumn = '*')
    {
        return $this->aggregate(__FUNCTION__, [$sColumn]);
    }


    public function sum($sColumn = '*')
    {
        return $this->aggregate(__FUNCTION__, [$sColumn]) ?: 0;
    }


    protected function aggregate($sSqlAggregateFunction, $arrColumns = ['*'])
    {
        $clone = $this->cloneQueryBuilder(['columns'], ['select']);

        $clone->m_componentAggregate = [
            'function'  => strtoupper($sSqlAggregateFunction),
            'columns'   => $arrColumns
        ];

        //若没有指定GROUP BY
        if (empty($clone->m_componentGroups)){
            $clone->m_componentOrders = null;
            $clone->m_arrBindings['order'] = [];
        }

        $arrRows = $clone->get($arrColumns);

        if (!empty($arrRows)){
            return array_change_key_case((array)$arrRows[0])['aggregate'];
        }

        return null;
    }


    /**
     * 判断当前查询是否有记录
     * 例如：SELECT EXISTS (SELECT * FROM `tf_table`) AS `EXISTS`;
     * @return bool TRUE|FALSE
     */
    public function exists()
    {
        $arrRow = $this->m_connection->select(
            $this->m_sqlCompiler->compileExists($this),
            $this->getBindingsList()
        );

        if (isset($arrRow[0])){
            $arrRow = (array)$arrRow[0];

            return (bool)$arrRow['exists'];
        }

        return false;
    }


    public function join()
    {

    }


    public function leftJoin()
    {

    }


    public function rightJoin()
    {

    }


    public function crossJoin()
    {

    }


    public function union()
    {

    }


    /**
     * WHERE筛选（也可连续操作，whereXX系列函数参数形式基本一致） <br>
     * <pre>
     * 例如：
     * ->where('ColA', 'EqualToValA') 字段相等
     * ->where('ColB', '=', 'HasOperatorValB') 字段和值直接可以指定操作运算符
     * ->where(['name' => 'admin', 'role' => 'super']) 同时指定多个条件
     * ->where([['name', '=', 'admin'], ['role', '=', 'writer']]) 同时指定多个指定操作运算符的条件
     * </pre>
     * @param string|array $columnOrNestedWheres 键名或者条件构成的数组
     * @param null|string|mixed $sOperatorOrValue 操作符或者直接就是使用=操作符的值
     * @param null|string $sValue 值
     * @param string $sWhereBoolean 组装该WHERE使用的是AND还是OR，参考orWhere系列
     * @return $this|QueryBuilder
     */
    public function where($columnOrNestedWheres, $sOperatorOrValue = null, $sValue = null, $sWhereBoolean = 'AND')
    {
        //对于第一个参数直接传入多个条件数组
        if (is_array($columnOrNestedWheres)){
            return $this->addNestedWheres($columnOrNestedWheres, $sWhereBoolean);
        }


        //对于第一个参数是一个回调函数，则表示它是一个Nested Where(如WHERE (A OR B) AND C)，编码是可以直接使用函数->whereNestedForCallback()
        if ($columnOrNestedWheres instanceof Closure){
            return $this->whereNestedForCallback($columnOrNestedWheres, $sWhereBoolean);
        }

        list($sValue, $sOperator) = $this->prepareWhereOperatorOrValue($sOperatorOrValue, $sValue, func_num_args() == 2);

        $this->m_componentWheres[] = [
            'type'      => 'Basic',
            'column'    => $columnOrNestedWheres,
            'operator'  => $sOperator,
            'value'     => $sValue,
            'boolean'   => $sWhereBoolean,
        ];

        if (!$sValue instanceof RawSql){
            $this->addBinding($sValue, 'where');
        }

        return $this;
    }


    /**
     * @param array $arrNestedWheres 形式如【['name'=>'admin', 'role'=>'super']】直接使用等作为操作符，或者【[['name', '=', 'admin'], ['role', '<>', 'writer']]】可以指定其它操作符
     * @param string $sWhereBoolean
     * @param string $sNestedQueryBuilderMethodUsed
     * @return QueryBuilder
     */
    protected function addNestedWheres($arrNestedWheres, $sWhereBoolean = 'AND', $sNestedQueryBuilderMethodUsed = 'where')
    {
        return $this->whereNestedForCallback(
            function ($queryBuilder) use ($arrNestedWheres, $sNestedQueryBuilderMethodUsed){
                foreach ($arrNestedWheres as $key => $value){
                    if (is_numeric($key) && is_array($value)){
                        call_user_func_array([$queryBuilder, $sNestedQueryBuilderMethodUsed], $value);
                    } else {
                        $queryBuilder->{$sNestedQueryBuilderMethodUsed}($key, '=', $value);
                    }
                }
            }, $sWhereBoolean
        );
    }


    /**
     * 添加一个function(QueryBuilder)类型的回调函数作为：WHERE (【子WHERE】) AND，结果是向回调函数传入自动创建的QueryBuilder，而便于操作，最后将这个QueryBuilder的Where部分放到当前$this的Where中
     * @param Closure $fnCallback
     * @param string $sWhereBoolean
     * @return QueryBuilder
     */
    public function whereNestedForCallback(Closure $fnCallback, $sWhereBoolean = 'AND')
    {
        call_user_func($fnCallback, $queryBuilder = $this->newQueryBuilder()->from($this->m_componentFrom));

        return $this->whereNestedForQueryBuilder($queryBuilder, $sWhereBoolean);
    }


    /**
     * 直接添加一个QueryBuilder类型的：WHERE (【子WHERE】) AND，结果是会将这个QueryBuilder中Where部分放到当前$this的Where中
     * @param QueryBuilder $queryBuilder
     * @param string $sWhereBoolean
     * @return $this
     */
    public function whereNestedForQueryBuilder(QueryBuilder $queryBuilder, $sWhereBoolean = 'AND')
    {
        if (count($queryBuilder->m_componentWheres)){
            $this->m_componentWheres[] = [
                'type'          => 'Nested',
                'boolean'       => $sWhereBoolean,
                'queryBuilder'  => $queryBuilder,//编译时直接获取该QueryBuilder对象Where后面的串
            ];

            //将Nested的QueryBuilder对象中绑定的拿出，并添加到【当前QueryBuilder的绑定】
            $this->addBinding($queryBuilder->getBindings(), 'where');
        }

        return $this;
    }


    protected function prepareWhereOperatorOrValue($sOperatorOrValue, $sValue, $bUseDefault = false)
    {
        if ($bUseDefault){
            return [$sOperatorOrValue, '='];
        } elseif (!$this->checkOperatorAndValue($sOperatorOrValue, $sValue)) {
            throw new InvalidArgumentException("Invalid operator:'{$sOperatorOrValue}' and value:'{$sValue}'.");
        }

        return [$sValue, $sOperatorOrValue];
    }


    /**
     * 判断WHERE语句中的Operator和Value是否有效
     * @param string $sOperator
     * @param string $sValue
     * @return bool
     */
    protected function checkOperatorAndValue($sOperator, $sValue)
    {
        if (is_null($sValue) || !$this->checkOperator($sOperator)){
            return false;
        }

        return true;
    }


    /**
     * 检查WHERE的Operator是否在指定的列表中
     * @param string $sOperator
     * @return bool
     */
    protected function checkOperator($sOperator)
    {
        return in_array(strtoupper($sOperator), $this->m_arrOperators, true) ||
            in_array(strtoupper($sOperator), $this->m_sqlCompiler->getOperators(), true);
    }


    /**
     * 添加单个或多个参数绑定到不同的类型
     * @param mixed|array $bindingValues
     * @param string $sType
     * @return $this
     * @throws InvalidArgumentException
     */
    protected function addBinding($bindingValues, $sType = 'where')
    {
        if (!array_key_exists($sType, $this->m_arrBindings)){
            throw new InvalidArgumentException("Invalid binging type:{$sType}.");
        }

        if (is_array($bindingValues)){
            //若为数组形式的绑定则merge到原来对应类型的数组
            $this->m_arrBindings[$sType]    = array_values(array_merge($this->m_arrBindings[$sType], $bindingValues));
        } else {
            //若为单个绑定则附加到原来对应类型的数组
            $this->m_arrBindings[$sType][]  = $bindingValues;
        }

        return $this;
    }


    /**
     * 获得数组形式的绑定
     * @return array
     */
    public function getBindings()
    {
        return $this->m_arrBindings;
    }


    /**
     * 获得一维数组形式的绑定
     * @return array
     */
    public function getBindingsList()
    {
        return Arr::flatten($this->m_arrBindings);
    }


    /**
     * 添加原始SQL作为的WHERE条件的语句
     * @param string $sRawSql
     * @param array $arrBindings
     * @param string $sWhereBoolean
     * @return $this
     */
    public function whereRaw($sRawSql, $arrBindings = [], $sWhereBoolean = 'AND')
    {
        $this->m_componentWheres[] = [
            'type'      => 'Raw',
            'sql'       => $sRawSql,
            'boolean'   => $sWhereBoolean
        ];

        $this->addBinding((array)$arrBindings, 'where');

        return $this;
    }


    public function orWhere($columnOrMultipleWheres, $sOperatorOrValue = null, $sValue = null)
    {
        list($sValue, $sOperator) = $this->prepareWhereOperatorOrValue($sOperatorOrValue, $sValue, func_num_args() == 2);

        return $this->where($columnOrMultipleWheres, $sOperator, $sValue, 'OR');
    }


    public function orWhereRaw($sRawSql, $arrBindings = [])
    {
        return $this->whereRaw($sRawSql, $arrBindings, 'OR');
    }


    public function whereBetween($sColumn, $arrBetweenRange, $sWhereBoolean = 'AND', $bNotBetween = false)
    {
        $this->m_componentWheres[] = [
            'type'      => 'Between',
            'column'    => $sColumn,
            'range'     => $arrBetweenRange,
            'boolean'   => $sWhereBoolean,
            'not'       => $bNotBetween,
        ];

        $this->addBinding((array)$arrBetweenRange, 'where');

        return $this;
    }


    public function orWhereBetween($sColumn, $arrBetweenRange)
    {
        return $this->whereBetween($sColumn, $arrBetweenRange, 'OR', false);
    }


    public function whereNotBetween($sColumn, $arrBetweenRange)
    {
        return $this->whereBetween($sColumn, $arrBetweenRange, 'AND', true);
    }


    public function orWhereNotBetween($sColumn, $arrBetweenRange)
    {
        return $this->whereBetween($sColumn, $arrBetweenRange, 'OR', true);
    }


    public function whereIn($sColumn, $arrInRange, $sWhereBoolean = 'AND', $bNotIn = false)
    {
        $sInType = $bNotIn ? 'NotIn' : 'In';
        $this->m_componentWheres[] = [
            'type'      => $sInType,
            'boolean'   => $sWhereBoolean,
            'column'    => $sColumn,
            'range'     => $arrInRange,
        ];

        foreach ($arrInRange as $oneInItem){
            if (!$oneInItem instanceof RawSql){
                $this->addBinding($oneInItem, 'where');
            }
        }

        return $this;
    }


    public function orWhereIn($sColumn, $arrInRange)
    {
        $this->whereIn($sColumn, $arrInRange, $sWhereBoolean = 'OR', $bNotIn = false);

        return $this;
    }


    public function whereNotIn($sColumn, $arrInRange)
    {
        $this->whereIn($sColumn, $arrInRange, $sWhereBoolean = 'AND', $bNotIn = true);

        return $this;
    }


    public function orWhereNotIn($sColumn, $arrInRange)
    {
        $this->whereIn($sColumn, $arrInRange, $sWhereBoolean = 'OR', $bNotIn = true);

        return $this;
    }


    public function whereNull($sColumn, $sWhereBoolean = 'AND', $bNotNull = false)
    {
        $sNullType = $bNotNull ? 'NotNull' : 'Null';

        $this->m_componentWheres[] = [
            'type'      => $sNullType,
            'column'    => $sColumn,
            'boolean'   => $sWhereBoolean,
        ];

        return $this;
    }


    public function orWhereNull($sColumn)
    {
        return $this->whereNull($sColumn, 'OR', false);
    }


    public function whereNotNull($sColumn)
    {
        return $this->whereNull($sColumn, 'AND', true);
    }


    public function orWhereNotNull($sColumn)
    {
        return $this->whereNull($sColumn, 'OR', true);
    }


    public function whereColumn()
    {

    }


    /**
     * 例如：
     * SELECT SupplierName
     * FROM Suppliers
     * WHERE EXISTS (SELECT ProductName FROM Products WHERE SupplierId = Suppliers.supplierId AND Price = 22);
     */
    public function whereExists()
    {

    }


    public function groupBy($arrColumns)
    {
        $arrColumns = is_array($arrColumns) ? $arrColumns : func_get_args();

        $this->m_componentGroups = array_merge((array)$this->m_componentGroups, $arrColumns);

        return $this;
    }


    public function having()
    {

    }


    public function havingRaw()
    {

    }


    public function offset($nOffset)
    {
        $this->m_componentOffset = $nOffset;

        return $this;
    }


    public function limit($nLimit)
    {
        $this->m_componentLimit = $nLimit;

        return $this;
    }


    /**
     * 插入单条['name'=>'tsoftware']或者同时插入多条[[], [], ...]记录，（注意多条记录字段必须相同）
     * @param array $arrKVPairs 一维或者二维数组
     * @return array|bool|mixed
     */
    public function insert($arrKVPairs)
    {
        if (empty($arrKVPairs)){
            return true;
        }

        //为了统一，将一维数组转换为二维数组
        if (!is_array(reset($arrKVPairs))){
            //若为一维数组
            $arrKVPairs = [$arrKVPairs];
        } else {
            //若已经为二维数组
            foreach ($arrKVPairs as $nIndex => $arrKVs){
                //将要插入的单条记录的字段顺序统一
                ksort($arrKVs);
                //修改写回
                $arrKVPairs[$nIndex] = $arrKVs;
            }
        }

        return $this->m_connection->insert(
            $this->m_sqlCompiler->compileInsert($this, $arrKVPairs),
            Arr::flatten($arrKVPairs, 1)
        );
    }


    public function insertGetId($arrKVPair)
    {
        if ($this->insert($arrKVPair)){
            return $this->m_connection->getLastInsertedId();
        }

        return null;
    }


    public function update($arrKVPair)
    {
        return $this->m_connection->update(
            $this->m_sqlCompiler->compileUpdate($this, $arrKVPair),
            $this->prepareUpdateBindings($arrKVPair)
        );
    }


    private function prepareUpdateBindings($arrKVPair)
    {
        return array_merge($arrKVPair, $this->getBindingsList());
    }


    public function insertOrUpdate($arrKVPair)
    {

    }


    public function increment()
    {

    }


    public function decrement()
    {

    }


    public function delete($nId = null)
    {
        if (!is_null($nId)){
            $this->where($this->m_componentFrom.'.id', '=', $nId);
        }

        return $this->m_connection->delete(
            $this->m_sqlCompiler->compileDelete($this), $this->getBindingsList()
        );


    }


    public function truncate()
    {

    }


    protected function runSelect()
    {
        return $this->m_connection->select(
            $this->getSelectSql(),
            $this->getBindingsList()
        );
    }


    public function getSelectSql()
    {
        return $this->m_sqlCompiler->compileSelect($this);
    }


    /**
     * 查询并返回结果集
     * @param string $sSql
     * @param array $arrBindings
     * @return array
     */
    public function query($sSql, $arrBindings = [])
    {
        return $this->m_connection->select($sSql, $arrBindings);
    }


    /**
     * 执行Sql语句，返回执行成功与否或者影响的行数
     * @param string $sSql
     * @param array $arrBindings
     * @param bool $bRetAffected
     * @return bool|int|string
     */
    public function execute($sSql, $arrBindings = [], $bRetAffected = false)
    {
        if ($bRetAffected){
            return $this->m_connection->affectingStatement($sSql, $arrBindings);
        }

        return $this->m_connection->statement($sSql, $arrBindings);
    }
}
