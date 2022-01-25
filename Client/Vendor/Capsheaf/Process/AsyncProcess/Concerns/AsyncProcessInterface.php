<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-03 21:14:54 CST
 *  Description:     AsyncProcessInterface.php's function description
 *  Version:         1.0.0.20180503-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-03 21:14:54 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\AsyncProcess\Concerns;

use RuntimeException;

interface AsyncProcessInterface
{

    /**
     * 启动进程
     * @return int 返回正常启动的进程PID
     * @throws RuntimeException 当不能启动时抛出异常
     */
    public function start();


    /**
     * 终止进程
     * @param string $sSignal
     * @return bool
     */
    public function stop($sSignal = '');


    /**
     * 通过指定的PID终止进程
     * @param int $nPid
     * @param string $sSignal
     * @return bool
     */
    public static function stopByPid($nPid, $sSignal = '');


    /**
     * 获取该进程的PID
     * @return int
     */
    public function getPid();


    /**
     * 返回该进程的运行状态
     * @return bool
     */
    public function running();


    /**
     * 返回指定PID进程的运行情况
     * @param int $nPid
     * @return bool
     */
    public static function runningByPid($nPid);

}
