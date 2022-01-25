<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-06-08 15:53:14 CST
 *  Description:     DateTime.php's function description
 *  Version:         1.0.0.20180608-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-06-08 15:53:14 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

use DateTime;

class DateTimeUtil
{

    /**
     * 计算天数差异
     * @param DateTime $dateTimeA
     * @param DateTime $dateTimeB
     * @return int 正数表示B超过的天数【A----->B】，负数表示【B----->A】
     */
    public static function diffDays(DateTime $dateTimeA, DateTime $dateTimeB)
    {
        $dateInterval = $dateTimeA->diff($dateTimeB);
        $nDiffDays = (int)$dateInterval->format('$r%a');

        return $nDiffDays;
    }


    /**
     * 计算秒数差异
     * @param DateTime $dateTimeA
     * @param DateTime $dateTimeB
     * @return int 正数表示B超过的秒数【A----->B】，负数表示【B----->A】
     */
    public static function diffSeconds(DateTime $dateTimeA, DateTime $dateTimeB)
    {
        return $dateTimeB->getTimestamp() - $dateTimeA->getTimestamp();
    }


    /**
     * @param int $nSeconds 秒数
     * @param array $arrUnitsName 时间单位，按照秒，分，时，天的形式指定，每个元素可以为一个数组来指定单数的形式
     * @return string
     */
    public static function formatSeconds($nSeconds, $arrUnitsName = [['seconds', 'second'], ['minutes', 'minute'], ['hours', 'hour'], ['days', 'day']])
    {
        $sSign = ($nSeconds < 0) ? '-' : '';
        $nSeconds = abs($nSeconds);

        $sFormatted = '';
        $nSecondParts   = $nSeconds % 60;
        $nMinuteParts   = intval($nSeconds / 60) % 60;
        $nHourParts     = intval($nSeconds / 3600) % 24;
        $nDayParts      = intval($nSeconds / 86400);

        $sFormatted .= self::getFormattedUnits($nDayParts, $arrUnitsName[3]);
        $sFormatted .= self::getFormattedUnits($nHourParts, $arrUnitsName[2]);
        $sFormatted .= self::getFormattedUnits($nMinuteParts, $arrUnitsName[1]);
        $sFormatted .= self::getFormattedUnits($nSecondParts, $arrUnitsName[0], true);

        return $sSign.$sFormatted;
    }


    /**
     * 生成单复数形式的值的表示
     * @param int $nNumber 值
     * @param array $arrUnitsName ['复数单位', '可选的单数单位']
     * @param bool $bKeepIfZero 即使为0，依然保留这个单位的表示，如"0秒"
     * @return string
     */
    public static function getFormattedUnits($nNumber, $arrUnitsName, $bKeepIfZero = false)
    {
        $arrUnitsName = (array)$arrUnitsName;
        if (count($arrUnitsName) < 1){
            return '';
        }

        $sUnit = $arrUnitsName[0];
        if (($nNumber <= 1) && isset($arrUnitsName[1])){
            $sUnit = $arrUnitsName[1];
        }

        if (empty($nNumber)){
            if ($bKeepIfZero){
                return "0{$sUnit}";
            }

            return '';
        }

        return "{$nNumber}{$sUnit}";
    }

}
