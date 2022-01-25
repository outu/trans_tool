<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-12-29 09:55:46 CST
 *  Description:     FileMutex.php's function description
 *  Version:         1.0.0.20181229-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-12-29 09:55:46 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\Mutex;

use Capsheaf\FileSystem\FileSystem;

class FileMutex
{

    private $m_hFile = null;
    private $m_sFilePath;
    private $m_bLocked;


    /**
     * 创建或者打开一个文件互斥量
     * @param string|null $sMutexName 互斥量名称，为null时表示匿名
     */
    public function __construct($sMutexName = null)
    {
        if (empty($sMutexName)){
            $sMutexFile = tempnam(sys_get_temp_dir(), 'Mutex');
        } else {
            $sMutexFile = sys_get_temp_dir()."/{$sMutexName}";
        }

        $this->m_sFilePath = $sMutexFile;
        $this->m_bLocked = false;
    }


    /**
     * 文件互斥检测
     * @param bool $bBlock 是否等待该文件互斥量
     * @return bool 返回true表示获得锁成功，false失败
     */
    public function lock($bBlock = false)
    {
        $this->m_hFile = fopen($this->m_sFilePath, "w");
        if (flock($this->m_hFile, $bBlock ? LOCK_EX : LOCK_EX|LOCK_NB)) {
            $this->m_bLocked = true;

            return true;
        }
        fclose($this->m_hFile);

        return false;
    }


    /**
     * 文件互斥释放
     * @return bool 成功释放返回true，失败返回false
     */
    public function unlock()
    {
        $bSuccess = true;
        if (is_resource($this->m_hFile)){
            $bSuccess = flock($this->m_hFile, LOCK_UN);
            fclose($this->m_hFile);
            FileSystem::delete($this->m_sFilePath);
            $this->m_sFilePath = '';
            $this->m_bLocked = false;
        }

        return $bSuccess;
    }


    public function __destruct()
    {
        if ($this->m_bLocked){
            $this->unlock();
        }
    }

}
