<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:06:21 CST
 *  Description:     Util.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:06:21 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils;

use Capsheaf\Support\Traits\MetaTrait;
use Exception;

class Util
{

    use MetaTrait;


    /**
     * 重复尝试
     * @param \Closure|string $fnCallback 回调函数或者回调函数名称，函数中【抛出异常表示失败】，不抛出异常表示成功
     * @param int $nRetryTimes 失败后的尝试次数，注意不含最开始的第一次
     * @param int $nSleepMilliseconds 失败后间隔时间，单位为毫秒，注意不是秒或者微秒
     * @return mixed
     * @throws Exception
     */
    public static function retry($fnCallback, $nRetryTimes = 0, $nSleepMilliseconds = 0)
    {
        //剩余机会等于设置的机会数
        $nLeftTimes = $nRetryTimes;

        RETRY_BEGINNING:
        try {
            //一开始并不占用“失败尝试次数”
            return $fnCallback();

        } catch (Exception $exception) {
            app('log')->warning("Retry got exception. [".get_class($exception)."]:{$exception->getMessage()}. Retrying...", ['trace' => explode("\n", $exception->getTraceAsString())]);

            //剩余机会<=0时，继续抛出表示失败
            if ($nLeftTimes <= 0){
                throw $exception;
            }

            //剩余尝试次数>=1，则减少一次机会
            $nLeftTimes--;

            if ($nSleepMilliseconds) {
                //函数使用的时微秒，1000*1000分之一秒
                usleep($nSleepMilliseconds * 1000);
            }

            goto RETRY_BEGINNING;
        }
    }


    /**
     * 获取函数的HASH值，用于判断两个函数是否是同一个函数
     * @param string|callable|object|array $fnFunction 形式可以为：函数名"funcName"，回调函数$funcName = function(){}，类静态函数['ClassName', 'funcName']，对象成员函数[$obj, 'funcName']
     * @return string
     */
    public static function getFunctionHash($fnFunction)
    {
        //函数名为字符串
        if (is_string($fnFunction)){
            return $fnFunction;
        }

        if (is_object($fnFunction)){
            //函数为Callable对象（回调函数对象），注意继承下来的类对象HASH不一样，所以函数也并不一致
            return spl_object_hash($fnFunction);
        } else {
            //指定的形式为数组的形式[$obj, 'funcName']或者['ClassName', 'funcName']的形式
            $fnFunction = (array)$fnFunction;
            if (is_object($fnFunction[0])){
                return spl_object_hash($fnFunction[0]).$fnFunction[1];
            } else {
                //注意的是根据类对象调用静态函数和类名直接调用静态函数这里会认为不是一个函数！
                return $fnFunction[0].$fnFunction[1];
            }
        }
    }


    /**
     * 版本比较（形式为0.0.0.0等，不支持alpha等后缀）
     * @param string $sVersionA
     * @param string $sVersionB
     * @param int $nCompareDepth 最多比较的子版本深度，可以用来设置为1来仅比较主版本
     * @return int -1为低于，0为等于，1为大于
     */
    public static function versionCompare($sVersionA, $sVersionB, $nCompareDepth = INF)
    {
        $arrVersionA = explode('.', $sVersionA);
        $arrVersionB = explode('.', $sVersionB);

        $nMaxDepth = max(count($arrVersionA), count($arrVersionB));

        for ($nDepth = 0; ($nDepth < $nCompareDepth && $nDepth < $nMaxDepth); $nDepth++){
            $nAPart = isset($arrVersionA[$nDepth]) ? (int)$arrVersionA[$nDepth] : 0;
            $nBPart = isset($arrVersionB[$nDepth]) ? (int)$arrVersionB[$nDepth] : 0;

            if ($nAPart > $nBPart){
                return 1;
            } elseif ($nAPart < $nBPart) {
                return -1;
            }
        }

        return 0;
    }


    /**
     * 生成UUID（v4，长度为36的字符串）
     * @return string
     */
    public static function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

}
