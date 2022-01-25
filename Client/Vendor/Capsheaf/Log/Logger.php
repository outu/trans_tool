<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:07 CST
 *  Description:     Log.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:07 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log;

use Capsheaf\Log\LogHandler\AbstractHandler;
use Capsheaf\Support\Traits\MetaTrait;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;

class Logger extends AbstractLogger
{

    use MetaTrait;

    /**
     * 日志器名称
     * @var string
     */
    protected $m_sChannelName;

    /**
     * 日志器中的消息处理Handler数组
     * @var AbstractHandler[]
     */
    protected $m_arrLogHandlers = [];

    /**
     * 日志记录处理器组件列表
     * @var callable[]
     */
    protected $m_arrMessageProcessors = [];

    /**
     * @var DateTimeZone
     */
    protected $m_dateTimeZone;


    /**
     * Logger constructor.
     * @param string $sChannelName Channel名称
     * @param AbstractHandler[] $arrLogHandlers Handlers列表
     * @param callable[] $arrMessageProcessor Processors列表
     * @param DateTimeZone|null $dateTimeZone 时区设置，默认为php.ini中的时区，若php.ini没有设置，则为上海时区（北京时间）
     */
    public function __construct($sChannelName, $arrLogHandlers = [], $arrMessageProcessor = [], DateTimeZone $dateTimeZone = null)
    {
        $this->m_sChannelName = $sChannelName;
        $this->m_arrLogHandlers = (array)$arrLogHandlers;
        $this->m_arrMessageProcessors = (array)$arrMessageProcessor;
        $this->m_dateTimeZone = $dateTimeZone ?: new DateTimeZone(@date_default_timezone_get());
    }


    /**
     * 获取Channel名称
     * @return string
     */
    public function getChannelName()
    {
        return $this->m_sChannelName;
    }


    /**
     * 设置Channel名称
     * @param string $sChannelName
     * @return Logger
     */
    public function setChannelName($sChannelName)
    {
        $this->m_sChannelName = $sChannelName;

        return $this;
    }


    /**
     * 添加一条日志记录
     * @param int $nLevel 日志等级，使用LogLevel中的常量
     * @param string $sMessage 日志文本消息
     * @param array $arrContext 日志额外存储的上下文信息
     * @return bool 返回该日志是否被处理了
     */
    public function log($nLevel, $sMessage, array $arrContext = [])
    {
        $nLogHandlerIndex = null;

        //取得第一个愿意处理该日志等级的Handler下标
        foreach ($this->m_arrLogHandlers as $nKey => $logHandler){
            if ($logHandler->willHandle(['level'=>$nLevel])){
                $nLogHandlerIndex = $nKey;
                break;
            }
        }

        if ($nLogHandlerIndex === null){
            return false;
        }

        $sLevelName = LogLevel::getLevelName($nLevel);
        if (PHP_VERSION_ID < 70100) {
            //U: Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) Example: 1292177455
            //u: Microseconds (up to six digits) 	Example: 45 or 654321
            //手册中createFromFormat地上参数在使用ms时不生效，需要单独调用setTimezone这个方法
            $datetime = DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
            $datetime->setTimezone($this->m_dateTimeZone);
        } else {
            //PHP7.1版本之后自动带上毫秒
            $datetime = new DateTime('now', $this->m_dateTimeZone);
        }

        $arrRecord = [
            'message'   => $sMessage,
            'context'   => $arrContext,
            'level'     => $nLevel,
            'level_name' => $sLevelName,
            'channel'   => $this->m_sChannelName,
            'datetime'  => $datetime,
            'extra'     => []
        ];

        //$fnMessageProcessor是实际的回调函数或者可供调用的类类型::__invoke
        foreach ($this->m_arrMessageProcessors as $fnMessageProcessor){
            $arrRecord = call_user_func($fnMessageProcessor, $arrRecord);
        }

        reset($this->m_arrLogHandlers);
        while ($nLogHandlerIndex !== key($this->m_arrLogHandlers)){
            next($this->m_arrLogHandlers);
        }

        while ($logHandler = current($this->m_arrLogHandlers)){
            //false表示放弃后续的处理
            if ($logHandler->handle($arrRecord) === false){
                break;
            }

            next($this->m_arrLogHandlers);
        }

        return true;
    }


    /**
     * 在最前边添加一个日志处理器
     * @param AbstractHandler $handler
     * @return $this
     */
    public function pushLogHandler(AbstractHandler $handler)
    {
        array_unshift($this->m_arrLogHandlers, $handler);

        return $this;
    }


    /**
     * 弹出一个日志处理器
     * @return AbstractHandler
     * @throws LogicException if pop an empty handler of stack.
     */
    public function popLogHandler()
    {
        if (!$this->m_arrLogHandlers){
            throw new LogicException('Can not pop an empty handler of stack.');
        }

        return array_shift($this->m_arrLogHandlers);
    }


    /**
     * 清空并设置整个Handler列表
     * @param AbstractHandler[] $arrHandlers
     * @return $this
     */
    public function setHandlers($arrHandlers = [])
    {
        $this->m_arrLogHandlers = [];
        foreach (array_reverse($arrHandlers) as $handler) {
            $this->pushLogHandler($handler);
        }

        return $this;
    }


    /**
     * 获得整个Handler列表
     * @return AbstractHandler[]
     */
    public function getHandlers()
    {
        return $this->m_arrLogHandlers;
    }


    /**
     * 添加一个日志处理器
     * @param callable $fnCallback
     * @return $this
     */
    public function pushMessageProcessor($fnCallback)
    {
        if (!is_callable($fnCallback)){
            throw new InvalidArgumentException('Message processors must be an valid callable (callback or object with an __invoke method), '.var_export($fnCallback, true).' given.');
        }

        array_unshift($this->m_arrMessageProcessors, $fnCallback);

        return $this;
    }


    /**
     * POP一个日志处理器
     * @return callable
     */
    public function popMessageProcessor()
    {
        if (!$this->m_arrMessageProcessors){
            throw new LogicException('Can not pop an empty message processor of stack.');
        }

        return array_shift($this->m_arrMessageProcessors);
    }


    /**
     * 获取整个日志处理器列表
     * @return callable[]
     */
    public function getMessageProcessors()
    {
        return $this->m_arrMessageProcessors;
    }


    /**
     * 获取设置的时区
     * @return DateTimeZone
     */
    public function getDateTimeZone()
    {
        return $this->m_dateTimeZone;
    }


    /**
     * 设置时区
     * @param DateTimeZone $dateTimeZone
     * @return $this
     */
    public function setDateTimeZone(DateTimeZone $dateTimeZone)
    {
        $this->m_dateTimeZone = $dateTimeZone;

        return $this;
    }

}
