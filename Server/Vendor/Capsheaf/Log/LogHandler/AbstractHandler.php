<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 16:14:22 CST
 *  Description:     AbstractHandler.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 16:14:22 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log\LogHandler;

use Capsheaf\Log\Formatter\FormatterInterface;
use Capsheaf\Log\Formatter\LineFormatter;
use Capsheaf\Log\LogLevel;
use Exception;
use InvalidArgumentException;
use LogicException;

abstract class AbstractHandler
{

    protected $m_nMinLevel = LogLevel::DEBUG;

    /**
     * @var bool 本handle处理完成之后是否还让其它的handle处理
     */
    protected $m_bBubble = true;

    protected $m_formatter;
    protected $m_arrInnerProcessors = [];


    public function __construct($nMinLevel = LogLevel::DEBUG, $bBubble = true)
    {
        $this->setMinLevel($nMinLevel);
        $this->m_bBubble = $bBubble;
    }


    /**
     * 根据日志的严重等级和Handler的最小日志等级来判断该Handler是否会处理该日志记录
     * @param array $arrRecord
     * @return bool
     */
    public function willHandle($arrRecord = [])
    {
        return $arrRecord['level'] >= $this->m_nMinLevel;
    }


    /**
     * 处理一条日志记录
     * @param array $arrRecord
     * @return bool 返回false不再需要后续处理，true表示还可以向后处理
     */
    public function handle($arrRecord = [])
    {
        if (!$this->willHandle($arrRecord)){
            return true;
        }

        $arrRecord = $this->processRecord($arrRecord);
        $arrRecord['formatted'] = $this->getFormatter()->format($arrRecord);
        $this->write($arrRecord);

        return $this->m_bBubble;
    }


    abstract protected function write($arrRecord = []);


    public function handleBatch($arrRecordsList = [])
    {
        foreach ($arrRecordsList as $arrRecord){
            $this->handle($arrRecord);
        }
    }


    public function setMinLevel($level)
    {
        $this->m_nMinLevel = LogLevel::parseLevel($level);

        return $this;
    }


    public function getMinLevel()
    {
        return $this->m_nMinLevel;
    }


    public function setFormatter(FormatterInterface $formatter)
    {
        $this->m_formatter = $formatter;

        return $this;
    }


    public function getFormatter()
    {
        if (!$this->m_formatter) {
            $this->m_formatter = $this->getDefaultFormatter();
        }

        return $this->m_formatter;
    }


    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }


    /**
     * 添加一个Handler的内部消息处理器，注意区别外部的MessageProcessors
     * @param callable $fnCallback
     * @return $this
     */
    public function pushMessageProcessor($fnCallback)
    {
        if (!is_callable($fnCallback)){
            throw new InvalidArgumentException('Message processors must be an valid callable (callback or object with an __invoke method), '.var_export($fnCallback, true).' given.');
        }

        array_unshift($this->m_arrInnerProcessors, $fnCallback);

        return $this;
    }


    public function popMessageProcessor()
    {
        if (!$this->m_arrInnerProcessors){
            throw new LogicException('Can not pop an empty message processor of stack.');
        }

        return array_shift($this->m_arrInnerProcessors);
    }


    public function getMessageProcessors()
    {
        return $this->m_arrInnerProcessors;
    }


    protected function processRecord($arrRecord = [])
    {
        foreach ($this->m_arrInnerProcessors as $fnInnerProcessor){
            $arrRecord = call_user_func($fnInnerProcessor, $arrRecord);
        }

        return $arrRecord;
    }


    protected function close()
    {

    }


    public function __destruct()
    {
        try {
            $this->close();
        } catch (Exception $e) {
            // do nothing
        }
    }

}
