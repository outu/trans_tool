<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-03 16:24:48 CST
 *  Description:     AsyncProcess.php's function description
 *  Version:         1.0.0.20180503-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-03 16:24:48 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\AsyncProcess;

use Capsheaf\Process\AsyncProcess\Concerns\AbstractAsyncProcess;
use Capsheaf\Process\AsyncProcess\Concerns\LinuxAsyncProcess;
use Capsheaf\Process\AsyncProcess\Concerns\WindowsAsyncProcess;
use RuntimeException;

class AsyncProcess
{

    /**
     * 具体的进程表示
     * @var AbstractAsyncProcess
     */
    protected $m_process;


    /**
     * AsyncProcess constructor.
     * @param string $sCommand 进程命令
     * @param null|string $sStdOutFile STDOUT文件路径，null表示不输出
     * @param null|string $sStdInFile STDIN文件路径，null表示无输入
     * @param null|false|string $sStdErrFile STDERR文件路径，null表示不关注，false表示使用STDOUT相同的输出
     * @throws RuntimeException Windows情况下若无法初始化COM组件则抛出
     */
    public function __construct($sCommand, $sStdOutFile = null, $sStdInFile = null, $sStdErrFile = false)
    {
        if (windows_os()){
            $this->m_process = new WindowsAsyncProcess($sCommand, $sStdOutFile, $sStdInFile, $sStdErrFile);
        } else {
            $this->m_process = new LinuxAsyncProcess($sCommand, $sStdOutFile, $sStdInFile, $sStdErrFile);
        }
    }


    /**
     * 启动进程
     * @return int 返回正常启动的进程PID
     * @throws RuntimeException 当不能启动时抛出异常
     */
    public function start()
    {
        return $this->m_process->start();
    }


    /**
     * 终止进程
     * @param string $sSignal
     * @return bool
     */
    public function stop($sSignal = '')
    {
        return $this->m_process->stop($sSignal);
    }


    /**
     * 通过指定的PID终止进程
     * @param int $nPid
     * @param string $sSignal
     * @return bool
     */
    public static function stopByPid($nPid, $sSignal = '')
    {
        if (windows_os()){
            return WindowsAsyncProcess::stopByPid($nPid, null);
        }

        return LinuxAsyncProcess::stopByPid($nPid, $sSignal);
    }


    /**
     * 获取该进程的PID
     * @return int
     */
    public function getPid()
    {
        return $this->m_process->getPid();
    }


    /**
     * 返回该进程的运行状态
     * @return bool
     */
    public function running()
    {
        return $this->m_process->running();
    }


    /**
     * 返回指定PID进程的运行情况
     * @param int $nPid
     * @return bool
     */
    public static function runningByPid($nPid)
    {
        if (windows_os()){
            return WindowsAsyncProcess::runningByPid($nPid);
        }

        return LinuxAsyncProcess::runningByPid($nPid);
    }

}
