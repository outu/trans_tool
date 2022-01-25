<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:22 CST
 *  Description:     Mutex.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:22 CST initialized the file
 ******************************************************************************/


namespace Capsheaf\Process\Mutex;

use SyncMutex;
class Mutex extends SyncMutex
{

    public static function SystemMutex($sMutexName = null){
        if (windows_os()){
            return new Mutex($sMutexName);
        } else {
            return new FileMutex($sMutexName);
        }
    }




    /**
     * 创建或者打开一个互斥量
     * @param string|null $sMutexName 互斥量名称，为null时表示匿名
     * @throws \Exception 不能创建或者打开该互斥量时抛出异常
     */
    public function __construct($sMutexName = null)
    {
        parent::__construct($sMutexName);
    }


    /**
     * 等待互斥量，将互斥量的内部计数器加1
     * @param int $nWaitMilliseconds 等待的时间（毫秒）
     * @return bool 返回true表示获得锁成功，false失败
     */
    public function lock($nWaitMilliseconds = -1)
    {
        return parent::lock($nWaitMilliseconds);
    }


    /**
     * 减少一次互斥量的内部计数器，当计数器次数达到0时，释放该互斥量
     * @param bool $bReleaseAll 是否一次性释放全部互斥量计数器
     * @return bool 成功释放返回true，失败返回false
     */
    public function unlock($bReleaseAll = false)
    {
        return parent::unlock($bReleaseAll);
    }

}
