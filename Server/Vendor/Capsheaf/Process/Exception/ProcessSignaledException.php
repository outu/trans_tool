<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-12 00:44:00 CST
 *  Description:     ProcessSignaledException.php's function description
 *  Version:         1.0.0.20180412-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-12 00:44:00 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\Exception;

use Capsheaf\Process\Process;
use RuntimeException;

class ProcessSignaledException extends RuntimeException
{

    protected $m_process;


    public function __construct(Process $process)
    {
        $this->m_process = $process;
        parent::__construct(sprintf('The process has been signaled with signal "%s".', $process->getTermSignal()));
    }


    public function getProcess()
    {
        return $this->m_process;
    }


    public function getSignal()
    {
        return $this->m_process->getTermSignal();
    }

}
