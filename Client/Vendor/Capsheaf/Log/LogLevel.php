<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-28 22:47:14 CST
 *  Description:     LogLevel.php's function description
 *  Version:         1.0.0.20180328-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-28 22:47:14 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log;

use InvalidArgumentException;

/**
 * Class LogLevel
 * @package Capsheaf\Log
 * @see https://tools.ietf.org/html/rfc5424
 */
final class LogLevel
{

    /**
     * Urgent alert.
     * system is unusable
     * 特别紧急的警告
     */
    const EMERGENCY = 800;

    /**
     * Action must be taken immediately
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     * 必须采取操作的警告，如：数据库关闭，然后需要短信通知的情况
     */
    const ALERT     = 700;

    /**
     * critical conditions
     * Example: Application component unavailable, unexpected exception.
     * 每个严重的错误事件将会导致应用程序的退出，如：组件不可用，非预期的异常
     */
    const CRITICAL  = 600;

    /**
     * error conditions
     * Runtime errors
     * 指出虽然发生错误事件，但仍然不影响系统的继续运行。
     */
    const ERROR     = 500;

    /**
     * warning conditions,
     * undesirable things that are not necessarily wrong.
     * Examples: Use of deprecated APIs, poor use of an API,
     */
    const WARNING   = 400;

    /**
     * normal but significant condition,
     * Uncommon events
     */
    const NOTICE    = 300;

    /**
     * informational messages,Interesting events
     * Examples: User logs in, SQL logs.
     */
    const INFO      = 200;

    /**
     * debug-level messages
     * Detailed debug information
     */
    const DEBUG     = 100;

    protected static $m_arrLevels = [
        self::EMERGENCY => 'emergency',
        self::ALERT     => 'alert',
        self::CRITICAL  => 'critical',
        self::ERROR     => 'error',
        self::WARNING   => 'warning',
        self::NOTICE    => 'notice',
        self::INFO      => 'info',
        self::DEBUG     => 'debug',
    ];


    /**
     * 解析日志等级
     * @param string|int $level 字符串常量或者类中的整型常量
     * @return int
     * @throws InvalidArgumentException if use an undefined level string
     */
    public static function parseLevel($level)
    {
        if (is_string($level)){
            if (defined(__CLASS__.'::'.strtoupper($level))) {
                return constant(__CLASS__.'::'.strtoupper($level));
            }

            throw new InvalidArgumentException("Level {$level} is not defined, use one of:".implode(', ', self::$m_arrLevels));
        }

        return $level;
    }


    /**
     * 根据int型的日志等级，获得对应等级的字符串描述
     * @param int $nLevel
     * @return string
     */
    public static function getLevelName($nLevel)
    {
        if (!isset(self::$m_arrLevels[$nLevel])){
            throw new \InvalidArgumentException("Level number:{$nLevel} is not defined, use one of:".implode(', ', array_keys(self::$m_arrLevels)));
        }

        return self::$m_arrLevels[$nLevel];
    }


    /**
     * 获取全部支持的日志字符串描述和等级整数
     * @return array
     */
    public static function getLevels()
    {
        return array_flip(self::$m_arrLevels);
    }

}
