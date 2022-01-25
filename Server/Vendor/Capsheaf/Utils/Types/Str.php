<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:06:18 CST
 *  Description:     Str.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:06:18 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

use Capsheaf\Support\Traits\MetaTrait;

class Str
{

    use MetaTrait;

    protected static $m_sCamelCache = [];


    /**
     * 判断字符串是否以某个字符串开头（也可以同时指定多个，符合其中一个即可）
     * @param string $sTarget
     * @param string|array $startsStrings
     * @return bool
     */
    public static function startsWith($sTarget, $startsStrings)
    {
        foreach ((array)$startsStrings as $sStartString){
            if ($startsStrings != '' && substr($sTarget, 0, strlen($sStartString)) === (string)$sStartString) {
                return true;
            }
        }

        return false;
    }


    /**
     * 判断字符串是否以某个字符串结尾（也可以同时指定多个，符合其中一个即可）
     * @param string $sTarget
     * @param string|array $endsStrings
     * @return bool
     */
    public static function endsWith($sTarget, $endsStrings)
    {
        foreach ((array)$endsStrings as $sEndString){
            if (substr($sTarget, -strlen($sEndString)) === (string)$sEndString) {
                return true;
            }
        }

        return false;
    }


    /**
     * 多字节的字符串截取
     * @param string $sTarget
     * @param int $nStart
     * @param null|int $nLength
     * @return string
     */
    public static function subStr($sTarget, $nStart, $nLength = null)
    {
        return mb_substr($sTarget, $nStart, $nLength, 'UTF-8');
    }


    /**
     * 字符串转换为大写
     * @param string $sTarget
     * @return string
     */
    public static function upper($sTarget)
    {
        return mb_strtoupper($sTarget, 'UTF-8');
    }


    /**
     * 字符串转换为小写
     * @param string $sTarget
     * @return string
     */
    public static function lower($sTarget)
    {
        return mb_strtolower($sTarget, 'UTF-8');
    }


    /**
     * 将字符串转换为标题的形式（每个单词首字母大写）
     * @param string $sTarget
     * @return string
     */
    public static function title($sTarget)
    {
        return mb_convert_case($sTarget, MB_CASE_TITLE, 'UTF-8');
    }


    /**
     * 字符串首字母大写
     * @param string $sTarget
     * @return string
     */
    public static function ucFirst($sTarget)
    {
        return static::upper(static::subStr($sTarget, 0, 1)).static::subStr($sTarget, 1);
    }


    /**
     * 多直接的字符串长度
     * @param $sTarget
     * @param null $sEncoding
     * @return int
     */
    public static function length($sTarget, $sEncoding = null)
    {
        if ($sEncoding) {
            return mb_strlen($sTarget, $sEncoding);
        }

        return mb_strlen($sTarget);
    }


    /**
     * 检测字符串中，是否包含某个子字符串，一旦找到其中一个子字符串则返回true
     * @param string $sTarget
     * @param string|array $toFind 单个查找的字符串或者字符串数组
     * @return bool
     */
    public static function contains($sTarget, $toFind)
    {
        foreach ((array)$toFind as $sToFind){
            if ($sToFind != '' && mb_strpos($sTarget, $sToFind) !== false){
                return true;
            }
        }

        return false;
    }


    /**
     * 将camel-case或camel_case或CAMEL_CASE形式转换为CamelCase的形式
     * @param string $sTarget
     * @return string
     */
    public static function camelCase($sTarget)
    {
        $sCacheKey = strtolower($sTarget);
        if (isset(static::$m_sCamelCache[$sCacheKey])) {
            return static::$m_sCamelCache[$sCacheKey];
        }
        $sCacheKey = ucwords(str_replace(['_', '-'], ' ', $sCacheKey));

        return static::$m_sCamelCache[$sCacheKey] = str_replace(' ', '', $sCacheKey);
    }

}
