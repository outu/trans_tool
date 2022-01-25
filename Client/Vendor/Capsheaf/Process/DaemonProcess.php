<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-06-14 21:17:37 CST
 *  Description:     DaemonProcess.php's function description
 *  Version:         1.0.0.20180614-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-06-14 21:17:37 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process;

use CapsheafServer\Models\Process\AsyncCall;

class DaemonProcess
{
    protected $m_nCheckInterval;

    protected $m_arrJobs = [];

    protected $m_sProcessPath;


    public function __construct($sProcessPath = null, $nCheckInterval = 3)
    {
        $this->m_sProcessPath   = $sProcessPath;
        $this->m_nCheckInterval = $nCheckInterval;
    }


    public function startDaemonJobs()
    {
        while (true){

            $this->checkAndRun();

            sleep($this->m_nCheckInterval);
        }
    }


    protected function checkAndRun()
    {
        foreach ($this->m_arrJobs as $sJobName => &$arrJob){
            if (is_null($arrJob['process'])){

                //若这个进程不需要保活，且已经正常运行了一次，就不再管它是否启动了
                $bJustOnce = ($arrJob['keep_alive'] == false) && ($arrJob['run_times'] > 0);
                if ($bJustOnce){
                    continue;
                }

                $sCommandLine = $this->getCommandLine(
                    $arrJob['module'],
                    $arrJob['controller'],
                    $arrJob['action'],
                    $arrJob['parameters']
                );
                $sCommandLine = $this->m_sProcessPath.' '.$sCommandLine;
                $process = new Process($sCommandLine, RUNTIME_PATH, null, null, PHP_INT_MAX);

                $process->start();
                if ($process->isStarted()){
                    app('log')->info("Job runner: {$sJobName} is started successful. Path: {$sCommandLine}, Pid:".$process->getPid());
                    $arrJob['process'] = $process;
                    $arrJob['run_times']++;
                } else {
                    app('log')->info("Job runner: {$sJobName} is failed to start. command line: {$sCommandLine}", [$arrJob]);
                }
            } else {
                $process = $arrJob['process'];
                if ($process instanceof Process && !$process->isRunning()){
                    $sOutput = $process->getOutput();

                    app('log')->notice("Job runner: {$sJobName} stopped with exit code:".$process->getExitCode(), ['output' => $sOutput]);

                    $arrJob['process'] = null;
                }
            }
        }
    }


    protected function getCommandLine($sModule, $sController, $sAction, $arrParameters)
    {
        return AsyncCall::getCommandLineParameterString($sModule, $sController, $sAction, $arrParameters);
    }


    public function registerRunner($sJobName, $sModule, $sController, $sAction, $arrParameters, $bKeepAlive = true)
    {
        $this->m_arrJobs[$sJobName] = [
            'module'        => $sModule,
            'controller'    => $sController,
            'action'        => $sAction,
            'parameters'    => $arrParameters,
            'process'       => null,
            'keep_alive'    => $bKeepAlive,
            'run_times'     => 0,
        ];
    }

}
