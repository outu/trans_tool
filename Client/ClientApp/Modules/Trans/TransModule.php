<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 11:37:19 CST
 *  Description:     TransModule.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 11:37:19 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Modules\Trans;

use Capsheaf\Application\Application;
use ClientApp\Models\Client\TransList;
use ClientApp\Models\Client\TransTask;
use ClientApp\Modules\AbstractModule;
use ClientApp\Process\Scanner\ScannerProcess;

class TransModule extends AbstractModule
{
    protected $m_sModuleName = 'TRANS_MODULE';

    private $m_arrTransWorker;

    private $m_arrScannerWorker;

    private $m_arrTransObj;


    public function __construct(Application $app)
    {
        parent::__construct($app);
    }


    public function run()
    {
        $transTask = new TransTask();
        $transList = new TransList();

        while(true){
            $arrUnCompletedTask = $transTask->getTransTask();

            //启动SCANNER
            if (empty($arrUnCompletedTask)){
                $this->m_app['log']->info("no task...");
            } else {
                if (empty($this->m_arrTransWorker)){
                    $this->m_app['log']->info("existing task: " . json_encode($arrUnCompletedTask) . "start scanner...");
                    //启动scanner线程
                    $scanner_process = new \swoole_process(
                        function (\swoole_process $process){
                            //https://wiki.swoole.com/wiki/page/214.html
                            (new ScannerProcess($this))->run($process->pid);
                            //此次扫描结束，告诉父进程
                            $process->write($process->pid);
                            $process->exit(0);
                        }, false
                    );
                    $this->m_arrScannerWorker[] = $scanner_process->start();
                } else {
                    $this->m_app['log']->info("scanner running...");
                }
            }

            sleep(1);
            //启动TRANS
            $nUnCompletedTransListCount = $transList->getTransListCount();
            if (empty($arrUnCompletedTransList)){
                $this->m_app['log']->info("no trans list to do...");
            } else {
                if ($nUnCompletedTransListCount > $this->m_app['config']){
                    //判断当前正在运行的传输进程是否达到最大限度
                    if (count($this->m_arrTransWorker) >= $this->m_config->get('trans')){
                        $this->m_app['log']->info("The number of transmission processes has reached the configured upper limit. No new transmission processes will be started.");
                    } else {
                        $nNewTransProcessNum = $this->m_config->get('trans') - count($this->m_arrTransWorker);
                        for ($i = 1; $i<=$nNewTransProcessNum; $i++){
                            $trans_process = new \swoole_process([$this, 'startTransProcess'], false);
                            $this->m_arrTransWorker[] = $trans_process->start();
                            $this->m_arrTransObj[] = $trans_process;
                        }
                    }
                }
            }



            sleep(5);


            //扫描线程存活判断
            if (!empty($scanner_process)){
                $nScannerPid = $scanner_process->read();
                //后期还需增加进程锁判断，避免线程崩溃，无法响应read write
                if ($nScannerPid !== null){
                    $nKey = array_search($nScannerPid, $this->m_arrScannerWorker);
                    unset($this->m_arrScannerWorker[$nKey]);
                }
            }

            //传输线程存活判断
            if (!empty($this->m_arrTransObj)){
                foreach ($this->m_arrTransObj as $nObj => $hTransObj){
                    $nTransPid = $hTransObj->read();

                    if ($nTransPid != null){
                        $nKey = array_search($nTransPid, $this->m_arrTransWorker);
                        unset($this->m_arrTransWorker[$nKey]);
                        unset($this->m_arrTransObj[$nObj]);
                    }
                }
            }


            //处理所有离线的传输列表记录


            //垃圾线程回收
            \swoole_process::wait();
        }
    }

}