<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 17:20:45 CST
 *  Description:     Log.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 17:20:45 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log;

class LoggersFactory
{

    /**
     * @var Logger[]
     */
    protected $m_arrLoggers = [];

    protected $m_defaultLogger;


    /**
     * 添加一个Logger到管理的Loggers列表中
     * @param Logger $logger 要添加的Logger
     * @param bool $bSetAsDefaultLogger 是否设置为默认的Logger
     */
    public function addLogger(Logger $logger, $bSetAsDefaultLogger = false)
    {
        $this->m_arrLoggers[$logger->getChannelName()] = $logger;
        if ($bSetAsDefaultLogger) {
            $this->m_defaultLogger = $logger;
        }
    }


    /**
     * 根据Channel获取Logger
     * @param string $sChannelName
     * @return Logger|null
     */
    public function getLogger($sChannelName)
    {
        if (isset($this->m_arrLoggers[$sChannelName])){
            return $this->m_arrLoggers[$sChannelName];
        }

        return null;
    }


    /**
     * 获取默认的Logger
     * @return Logger 返回默认的Logger，若没有设置默认的Logger则返回最后一个添加的Logger
     */
    public function getCurrentLogger()
    {
        if (!empty($this->m_defaultLogger)){
            return $this->m_defaultLogger;
        }

        if (count($this->m_arrLoggers)){
            return end($this->m_arrLoggers);
        }

        return null;
    }


    /**
     * 设置使用的Channels
     * @param array|string $arrChannelNames Channel名称列表
     * @return LoggersCollection
     */
    public function useChannels($arrChannelNames = [])
    {
        $arrSelectedLoggers = [];

        foreach ((array)$arrChannelNames as $channelName){
            if (isset($this->m_arrLoggers[$channelName])){
                $arrSelectedLoggers[] = $this->m_arrLoggers[$channelName];
            }
        }
        //空的Channel暂时不报错

        return new LoggersCollection($arrSelectedLoggers);
    }


    /**
     * @param string  $sLoggerMethodName
     * @param array $arrArguments
     */
    public function __call($sLoggerMethodName, $arrArguments)
    {
        $currentLogger = $this->getCurrentLogger();
        call_user_func_array([$currentLogger, $sLoggerMethodName], $arrArguments);
    }

}
