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
use ClientApp\Process\Trans\TransProcess;
use swoole_process;

class TransModule extends AbstractModule
{
    protected $m_sModuleName = 'TRANS_MODULE';

    private $m_arrTransWorker = [];

    private $m_nScannerWorker;

    //private $m_arrTransObj;


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
                    $scanner_process = new swoole_process(
                        function (swoole_process $process){
                            //https://wiki.swoole.com/wiki/page/214.html
                            (new ScannerProcess())->run($process->pid);
                            //此次扫描结束，生成进程结束标志（pid_finished）, 后期将文件通信的方式改为进程间通信
                            file_put_contents(RUNTIME_PATH . $process->pid . "finished", "");
                            $process->exit(0);
                        }, false
                    );
                    $this->m_nScannerWorker = $scanner_process->start();
                } else {
                    $this->m_app['log']->info("scanner running...");
                }
            }

            sleep(1);
            //启动TRANS
            $nUnCompletedTransListCount = $transList->getTransListCount();

            if (empty($nUnCompletedTransListCount)){
                $this->m_app['log']->info("no trans list to do...");
            } else {
                if ($nUnCompletedTransListCount > $this->m_config->get('trans')){
                    //判断当前正在运行的传输进程是否达到最大限度
                    if (count($this->m_arrTransWorker) >= $this->m_config->get('trans')){
                        $this->m_app['log']->info("The number of transmission processes has reached the configured upper limit. No new transmission processes will be started.");
                    } else {
                        $nNewTransProcessNum = $this->m_config->get('trans') - count($this->m_arrTransWorker);
                        for ($i = 1; $i<=$nNewTransProcessNum; $i++){
                            $trans_process = new swoole_process([$this, 'startTransProcess'], false);
                            $this->m_arrTransWorker[] = $trans_process->start();
                            //$this->m_arrTransObj[] = $trans_process;
                        }
                    }
                }
            }



            sleep(5);


            //扫描线程存活判断
            $this->m_app['log']->info("Scan thread survival judgment");

            if ($this->m_nScannerWorker){
                if (file_exists(RUNTIME_PATH . $this->m_nScannerWorker . "finished")){
                    $this->m_nScannerWorker = null;
                    @unlink(RUNTIME_PATH . $this->m_nScannerWorker . "finished");
                }
            } else {
                $this->m_nScannerWorker = null;
            }

            //传输线程存活判断
            $this->m_app['log']->info("Trans thread survival judgment");
            if (!empty($this->m_arrTransWorker)){
                foreach ($this->m_arrTransWorker as $nKey => $nTransPid){
                    //swoole->read阻塞读取，暂时处理为文件通信
                    if (file_exists(RUNTIME_PATH . $nTransPid . "finished")){
                        unset($this->m_arrTransWorker[$nKey]);
                        @unlink(RUNTIME_PATH . $nTransPid . "finished");
                    }
                }
            }


            //处理所有离线的传输列表记录

            //回收结束运行的子进程
            swoole_process::wait(false);
        }
    }


    public function listenTransProcess()
    {



    }


    public function startTransProcess(swoole_process $worker)
    {
        (new TransProcess())->run($worker->pid);
        file_put_contents(RUNTIME_PATH . $worker->pid . "finished", "");
        $worker->exit(0);
    }

}