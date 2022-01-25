<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:06:13 CST
 *  Description:     Arr.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:06:13 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

use ArrayAccess;
use Capsheaf\Support\Traits\MetaTrait;
use Closure;
use InvalidArgumentException;
use Iterator;

class Arr
{

    use MetaTrait;


    /**
     * 从数组中随机选择一个或者几个元素并返回，它内部使用了伪随机数产生算法，所以不适合密码学场景，
     * @param array $arrArray 数组集合，需要保证至少一个元素，否则抛出异常
     * @param null|int $nNeed 为null时仅仅取一个值，值得范围为1-元素个数，否则抛出异常，注意返回值为一个元素时的两种形式
     * @return array|mixed 返回数组或者单个值，当指定的$nNeed参数不为null时，返回数组的形式（包括一个元素时），为null表示仅返回单个元素（非数组的形式）
     * @throws InvalidArgumentException
     */
    public static function random($arrArray, $nNeed = null)
    {
        if (($nRequest = $nNeed ?: 1) > ($nAvailable = count($arrArray))){
            throw new InvalidArgumentException("Request {$nRequest} has to be between 1 and {$nAvailable} in the array.");
        }

        if ($nNeed == null){
            //第二个参数默认为1，此时返回的形式为单个值
            return array_rand($arrArray);
        }

        //注意array_rand第二个参数为1或者不指定时，返回的元素是单个下标而不是数组的下标组成的数组
        $arrKeys = array_rand($arrArray, $nNeed);
        $arrRandoms = [];
        foreach ((array)$arrKeys as $nKey){
            $arrRandoms[] = $arrArray[$nKey];
        }

        return $arrRandoms;

    }


    /**
     * 判断数组中是否存在某些键（单个可以直接用string|int表示）
     * @param array $arrArray 多维数组可以用.标记
     * @param array|string|int $keys 键名串（可以使用.）或者键名数组，如果是数组，则全部都需要存在才返回true
     * @return bool
     */
    public static function has($arrArray, $keys)
    {
        if (is_null($keys) || !$arrArray){
            return false;
        }

        $arrKeys = is_array($keys) ? $keys : [$keys];

        foreach ($arrKeys as $sPartKey){
            //存在其中的一个键则继续判断数组中其它的键是否也存在
            if (self::exists($arrArray, $sPartKey)){
                continue;
            }

            //下面处理循环中不存在该键的情况，.标记存在就存在，不存在就返回false表示数组中该带.的键是不存在的，整体不满足
            //处理带.的键名
            $arrTemp = $arrArray;
            foreach (explode('.', $sPartKey) as $sSubKey){
                if (static::isArray($arrTemp) && static::exists($arrTemp, $sSubKey)){
                    //注意返回不一定是array，这里是为了方便
                    $arrTemp = $arrTemp[$sSubKey];
                } else {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * 设置数组的值，支持.标记设置多维数组
     * @param array $arrArray
     * @param string $sKey
     * @param mixed $value
     * @return mixed
     */
    public static function set(&$arrArray, $sKey, $value)
    {
        //键名为null表示整体设置
        if (is_null($sKey)){
            return $arrArray = $value;
        }

        $arrKeys = explode('.', $sKey);
        $arrDeepRef = &$arrArray;
        while (count($arrKeys) > 1){
            //取出最靠前的键名
            $sPartKey = array_shift($arrKeys);

            //中间的过程要是没有这个键，则创建一个空数组，如'a.[b]'中没有'a.[b].c.d'中的c（不会到d）
            if (!isset($arrDeepRef[$sPartKey]) || !is_array($arrDeepRef[$sPartKey])){
                $arrDeepRef[$sPartKey] = [];
            }

            //这步很关键，它将当前要操作的数组往更深的一层推进，因为要修改，所以仍然使用引用的形式
            $arrDeepRef = &$arrDeepRef[$sPartKey];
        }

        //设置最后一层数组的值，注意这里是引用
        $arrDeepRef[array_shift($arrKeys)] = $value;

        //返回之后一层设置的值，注意不是返回修改后的数组，不要误导！！
        return $arrArray;
    }


    /**
     * 返回数组对应键的值，支持.标记获取多维数组中的值
     * @param array|ArrayAccess $arrArray
     * @param string $sKey 注意的是下标为【数组字符串】与【整数】是等价的，即："0"==0，可以使用"sSomeKey.23.0"来取得数值型下标的值
     * @param Closure|mixed $default
     * @return mixed
     */
    public static function get($arrArray, $sKey, $default = null)
    {
        if (!static::isArray($arrArray)){
            //处理闭包参数的情况
            return value($default);
        }

        if (is_null($sKey)){
            return $arrArray;
        }

        if (self::exists($arrArray, $sKey)){
            return $arrArray[$sKey];
        }

        //处理带.的键名
        foreach (explode('.', $sKey) as $sSubKey){
            if (static::isArray($arrArray) && static::exists($arrArray, $sSubKey))
            {
                //注意返回不一定是array，这里是为了方便
                $arrArray = $arrArray[$sSubKey];
            }else{
                return value($default);
            }
        }

        return $arrArray;
    }


    /**
     * 从（多维）数组中移除一个值
     * @param &$arrArray
     * @param null|string|int $sKey
     */
    public static function remove(&$arrArray, $sKey = null)
    {
        if (is_null($sKey)){
            return;
        }

        //对于第一级中就是存在sKey的情况，比如可能存在"a.b.c"，提前这样处理，可以避免第一级中就是这种情况
        if (array_key_exists($sKey, $arrArray)){
            unset($arrArray[$sKey]);
            return;
        }

        $arrKeys = explode('.', $sKey);
        $arrDeepRef = &$arrArray;
        while (count($arrKeys) > 1){
            //取出最靠前的键名,并删除
            $sPartKey = array_shift($arrKeys);

            //中间的过程要是没有这个键，则创建一个空数组，如'a.[b]'中没有'a.[b].c.d'中的c（不会到d）
            if (isset($arrDeepRef[$sPartKey]) && is_array($arrDeepRef[$sPartKey])){
                //这步很关键，它将当前要操作的数组往更深的一层推进，因为要修改，所以仍然使用引用的形式
                //注意这里直接使用传入的函数参数，
                $arrDeepRef = &$arrDeepRef[$sPartKey];
            } else {
                break;
            }
        }

        unset($arrDeepRef[array_shift($arrKeys)]);
    }


    /**
     * 判断是否为一个有效的数组
     * @param array|ArrayAccess|mixed $arrArray
     * @return bool
     */
    public static function isArray($arrArray)
    {
        return is_array($arrArray) || $arrArray instanceof ArrayAccess;
    }


    /**
     * 判断数组中对应键是否存在
     * @param array|ArrayAccess $arrArray
     * @param string|int $key
     * @return bool
     */
    public static function exists($arrArray, $key)
    {
        if ($arrArray instanceof ArrayAccess){
            return $arrArray->offsetExists($key);
        }

        return array_key_exists($key, $arrArray);
    }


    /**
     * 判断数组是否为关联数组
     * @param array $arrArray
     * @return bool
     */
    public static function isAssoc($arrArray)
    {
        $arrKeys = array_keys($arrArray);

        //数组可以直接用部分普通的比较符（大于小于不能使用，可以使用+，==，===，!=，<>，!==）
        //注意的是+不同于array_merge,+后面数组的相同键名（包括数字键）不会出现在结果中，数组即使是KV相同（包括数字键）但顺序不同都不能为全等(但==可以)
        //http://php.net/manual/en/language.operators.array.php
        return array_keys($arrKeys) !== $arrKeys;
    }


    /**
     * 从数组中根据元素值（不是键名或者下标）移除一个数组元素
     * @param array $arrArray
     * @param mixed $value 元素值
     * @return bool 返回true时表示对应值的元素存在，false时表示原来的元素并不存在，大多数时候忽略该返回值
     */
    public static function unsetValue($arrArray, $value)
    {
        $nIndex = array_search($value, $arrArray);
        if ($nIndex !== false){
            unset($arrArray[$nIndex]);

            return true;
        }

        return false;
    }


    /**
     * 若不是一个数组则返回一个经过包装的数组
     * @param mixed $value
     * @return array
     */
    public static function wrap($value)
    {
        return is_array($value) ? $value : [$value];
    }


    /**
     * 获得乱序后的数组（不影响原数组）
     * @param array $arrArray
     * @return mixed
     */
    public static function shuffle($arrArray)
    {
        //注意是引用传值
        shuffle($arrArray);

        return $arrArray;
    }


    /**
     * 对数组的多个字段进行排序，注意直接操作原数组（同时返回）
     * @param array $arrArray
     * @param array $arrSortFields
     * [<br>
     *      ['字段1', 可选顺序, 可选标志],<br>
     *      ['字段2', 可选顺序, 可选标志],<br>
     * ]<br>
     * 其中：<br>
     * 1）顺序： 【SORT_ASC】 按照上升顺序排序， SORT_DESC 按照下降顺序排序。<br>
     * 2）标志：<br>
     *    【SORT_REGULAR】 - 将项目按照通常方法比较（不修改类型）<br>
     *    SORT_NUMERIC - 按照数字大小比较<br>
     *    SORT_STRING - 按照字符串比较<br>
     *    SORT_LOCALE_STRING - 根据当前的本地化设置，按照字符串比较。 它会使用 locale 信息，可以通过 setlocale() 修改此信息。<br>
     *    SORT_NATURAL - 以字符串的"自然排序"，类似 natsort()<br>
     *    SORT_FLAG_CASE - 可以组合 (按位或 OR) SORT_STRING 或者 SORT_NATURAL 大小写不敏感的方式排序字符串。<br>
     *<br>
     *
     * @return array 返回排序后的数组
     * @throws InvalidArgumentException
     */
    public static function sortByFields(&$arrArray, $arrSortFields)
    {
        $arrArgs = [];
        //循环每个字段选项
        foreach ($arrSortFields as $arrFieldOptions){
            $arrFieldValues = [];
            //对应字段值的集合
            foreach ($arrArray as $arrEach){
                if (!key_exists($arrFieldOptions[0], $arrEach)) {
                    throw new InvalidArgumentException("Key {$arrFieldOptions[0]} of array that going to be sort does not exists.");
                }
                $arrFieldValues[] = $arrEach[$arrFieldOptions[0]];
            }

            $arrArgs[] = $arrFieldValues;
            //排序
            $arrArgs[] = isset($arrFieldOptions[1]) ? $arrFieldOptions[1] : SORT_ASC;
            //标志
            $arrArgs[] = isset($arrFieldOptions[2]) ? $arrFieldOptions[2] : SORT_REGULAR;
        }

        $arrArgs[] = &$arrArray;

        call_user_func_array('array_multisort', $arrArgs);

        return $arrArray;
    }


    /**
     * 扁平化数组
     * @param array $arrArray
     * @param int $nDepth
     * @return array
     */
    public static function flatten($arrArray, $nDepth = INF)
    {
        return array_reduce(
            $arrArray, function($arrResult, $current) use ($nDepth){
                if (!is_array($current)){
                    return array_merge($arrResult, [$current]);
                } elseif ($nDepth === 1) {
                    return array_merge($arrResult, array_values($current));
                } else {
                    return array_merge($arrResult, static::flatten($current, $nDepth - 1));
                }
            }, []
        );
    }


    /**
     * 找到第一个符合条件的元素
     * @param array|ArrayAccess|Iterator $arrArray 可以foreach的集合
     * @param null|Closure $fnFilter
     * @param null|mixed $default
     * @return mixed
     */
    public static function first($arrArray, $fnFilter = null, $default = null)
    {
        if (is_null($fnFilter)){
            if (empty($arrArray)){
                return value($default);
            }
            foreach ($arrArray as $item){
                return $item;
            }
        }

        foreach ($arrArray as $key => $item){
            if (call_user_func($fnFilter, $item, $key)){
                return $item;
            }
        }

        return value($default);
    }


    /**
     * 找到最后一个符合条件的元素
     * @param array|ArrayAccess|Iterator $arrArray 可以foreach的集合
     * @param null|Closure $fnFilter
     * @param null|mixed $default
     * @return mixed
     */
    public static function last($arrArray, $fnFilter = null, $default = null)
    {
        return self::first(array_reverse($arrArray), $fnFilter, $default);
    }


    /**
     * 仅仅取得数组中指定键名构成的新数组
     * @param array $arrArray
     * @param array|string $arrKeys ['key1', 'key2']或者'keySingle'
     * @return array
     */
    public static function only($arrArray, $arrKeys)
    {
        return array_intersect_key($arrArray, array_flip((array)$arrKeys));
    }


    /**
     * 取得当前深度的筛选串
     * @param string $sField
     * @param int $nDepth 当前遍历数组深度
     * @return string
     */
    static function getFields($sField, $nDepth = 1)
    {
        $arrParts = explode('.', $sField, $nDepth + 1);
        if (count($arrParts) > 1 && count($arrParts) > $nDepth) {
            array_pop($arrParts);
        }
        $sFieldsOfTheDepth = implode('.', $arrParts);

        return $sFieldsOfTheDepth;
    }


    /**
     * 取得字符串的总深度
     * @param string $sPath
     * @return int
     */
    static function getTotalDepth($sPath)
    {
        $nFieldTotalDepth = substr_count($sPath, '.') + 1;
        return $nFieldTotalDepth;
    }


    /**
     * 去除多维数组中空子数组
     * @param $arrayInput
     * @return mixed
     */
    static function removeEmptyArray(&$arrayInput)
    {
        foreach ($arrayInput as $sInputKey => $arrInputValue) {

            if (empty($arrInputValue)) {
                unset($arrayInput[$sInputKey]);
            } elseif (is_array($arrInputValue)) {
                self::removeEmptyArray($arrInputValue);
            } else {
                break;
            }
        }

        return $arrayInput;
    }


    /**
     * 返回字符串的第一个单词 如: A.B.C 中的A
     * @param string $sPath
     * @return string
     */
    static function handlePath($sPath)
    {
        $nFirstPath = strpos($sPath, '.');
        if ($nFirstPath) {
            $sFirstPath = substr($sPath,0, $nFirstPath);
        } else {
            return $sPath;
        }

        return $sFirstPath;
    }


    /**
     * 递归比较子数组和筛选数组的路径串, 并筛选
     * @param array $arrSubArray 子数组
     * @param int $nDepth 实际数组下标深度
     * @param array $arrFields 筛选数组
     * @param string $sPartPath 源数组到子数组的实际路径串
     * @param string $sMethodSource 方法名 'exceptFields', 'onlyFields'
     * @return array 返回经过字段筛选后的数组
     */
    static function pathFilter(&$arrSubArray, $nDepth, $arrFields, $sPartPath, $sMethodSource)
    {
        foreach ($arrFields as $sField) {
            $sTheDepthFields     = self::getFields($sField, $nDepth);
            $sTheTotalDepthField = self::getTotalDepth($sField);
            $nNowDepthFields     = self::getTotalDepth($sTheDepthFields);
            $bIsWildCard         = strpos($sField, '*') == 0 ? true : false;

            foreach ($arrSubArray as $sSubKey => $arrArray) {
                $sPartPath             .= '.'.$sSubKey;
                $sPartPath              = ltrim($sPartPath, ".");
                $sFirstPartPath         = self::handlePath($sPartPath);
                $sTheTotalDepthPartPath = self::getTotalDepth($sPartPath);

                if ($bIsWildCard) {
                    $sTheDepthFields = str_replace('*', $sFirstPartPath, $sTheDepthFields);
                }
                $bJudge = $sTheDepthFields == $sPartPath ? true : false;

                switch ($sMethodSource) {
                    case 'exceptFields':

                        if ($bJudge && ($sTheTotalDepthPartPath == $sTheTotalDepthField)) {
                            unset($arrSubArray[$sSubKey]);
                            $sPartPath = substr($sPartPath, 0, strlen($sPartPath) - strlen($sSubKey) - 1);
                            continue 2;
                        } elseif (is_array($arrArray)) {
                            $nDepth++;
                            $arrSubArray[$sSubKey] = self::pathFilter($arrArray, $nDepth, $arrFields, $sPartPath, $sMethodSource);
                        }

                        break;
                    case 'onlyFields':

                        if (!$bJudge && ($nNowDepthFields == $sTheTotalDepthPartPath)) {
                            unset($arrSubArray[$sSubKey]);
                            $sPartPath = substr($sPartPath, 0, strlen($sPartPath) - strlen($sSubKey) - 1);
                            continue 2;
                        } elseif (is_array($arrArray)) {
                            $nDepth++;
                            $arrSubArray[$sSubKey] = self::pathFilter($arrArray, $nDepth, $arrFields, $sPartPath, $sMethodSource);
                        } elseif ($sTheTotalDepthField > $sTheTotalDepthPartPath) {
                            unset($arrSubArray[$sSubKey]);
                        }
                        self::removeEmptyArray($arrSubArray);

                        break;
                }

                if (strlen($sPartPath) > strlen($sSubKey)){
                    $sPartPath = substr($sPartPath, 0, strlen($sPartPath) - strlen($sSubKey) - 1);
                } else {
                    $sPartPath = '';
                }

                if ($bIsWildCard ) {
                    $sFirstFields = self::handlePath($sTheDepthFields);
                    $sTheDepthFields = str_replace($sFirstFields, '*', $sTheDepthFields);
                }
            }
        }

        return $arrSubArray;
    }


    /**
     * 筛选数组中的字段，仅保留指定的字段
     * @param array $arrArray 要筛选的源数组
     * @param string|array $arrFields 如：'*.meta.user_id'
     */
    public static function onlyFields(&$arrArray, $arrFields)
    {
        $arrFields = (array)$arrFields;
        self::pathFilter($arrArray, 1, $arrFields, '', 'onlyFields');
        self::removeEmptyArray($arrArray);
    }


    /**
     * 筛选数组中的字段，除了指定的字段，其它字段全部保留
     * @param array $arrArray 要筛选的源数组
     * @param string|array $arrFields 如：['*.meta.password', '*.deleted_at']
     */
    public static function exceptFields(&$arrArray, $arrFields)
    {
        $arrFields = (array)$arrFields;
        self::pathFilter($arrArray, 1, $arrFields, '', 'exceptFields');
        self::removeEmptyArray($arrArray);
    }

}
