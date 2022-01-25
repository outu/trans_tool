<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-10 10:00:51 CST
 *  Description:     ProcessTimeoutException.php's function description
 *  Version:         1.0.0.20180410-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-10 10:00:51 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\Exception;

use Capsheaf\Process\Process;
use LogicException;
use RuntimeException;

class ProcessTimeoutException extends RuntimeException
{

    const TYPE_GENERAL  = 1;
    const TYPE_IDLE     = 2;

    private $m_process;
    private $m_nTimeoutType;


    /**
     * ProcessTimeoutException constructor.
     * @param Process $process
     * @param int $nTimeoutType Process::TYPE_GENERAL|Process::TYPE_IDLE
     */
    public function __construct(Process $process, $nTimeoutType)
    {
        $this->m_process = $process;
        $this->m_nTimeoutType = $nTimeoutType;

        parent::__construct(
            sprintf(
                'Process "%s" exceeded the timeout of %s seconds.',
                $this->m_process->getCommandLine(),
                $this->getExceededTimeout()
            )
        );
    }


    /**
     * 获取进程实例
     * @return Process
     */
    public function getProcess()
    {
        return $this->m_process;
    }


    /**
     * 是否是普通的运行时间超时
     * @return bool
     */
    public function isGeneralTimeout()
    {
        return $this->m_nTimeoutType === self::TYPE_GENERAL;
    }


    /**
     * 是否是IDLE超时
     * @return bool
     */
    public function isIdleTimeout()
    {
        return $this->m_nTimeoutType === self::TYPE_IDLE;
    }


    /**
     * 获取设置的超时时间
     * @return int
     */
    public function getExceededTimeout()
    {
        switch ($this->m_nTimeoutType){
            case self::TYPE_GENERAL:
                return $this->m_process->getTimeout();
            case self::TYPE_IDLE:
                return $this->m_process->getIdleTimeout();
            default:
                throw new LogicException("Unknown timeout type {$this->m_nTimeoutType}.");
        }
    }

}
