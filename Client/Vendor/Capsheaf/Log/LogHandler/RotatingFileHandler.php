<?php
namespace Capsheaf\Log\LogHandler;

use Capsheaf\Log\LogLevel;
use DateTime;

class RotatingFileHandler extends StreamHandler
{

    const FILE_PER_DAY = 'Y-m-d';
    const FILE_PER_MONTH = 'Y-m';
    const FILE_PER_YEAR = 'Y';

    protected $m_sBaseFilePathName;

    protected $m_sDateFormat;

    protected $m_datetimeNextDay;

    /**
     * @var string 日志文件名称，不包含前面目录路径
     */
    protected $m_sFileNameFormat = '{filename}-{date}';

    /**
     * @var null|bool null第一次，false不需要，true需要切换
     */
    protected $m_bTriggerRotate = null;

    /**
     * 日志保留最大个数
     * @var int
     */
    protected $m_nMaxKeepFiles;


    public function __construct($sBaseFilePathName, $nMaxKeepFiles = 0, $nMinLevel = LogLevel::DEBUG, $bBubble = true, $nStreamPermission = 0777, $bUseLock = false)
    {
        $this->m_sBaseFilePathName = $sBaseFilePathName;
        $this->m_nMaxKeepFiles = $nMaxKeepFiles;
        $this->m_sDateFormat = static::FILE_PER_DAY;

        $this->m_datetimeNextDay = new DateTime('tomorrow');

        parent::__construct($this->getTimedFileName(), $nMinLevel, $bBubble, $nStreamPermission, $bUseLock);
    }


    public function setMaxKeepFiles($nMaxKeepFiles = 0)
    {
        $this->m_nMaxKeepFiles = $nMaxKeepFiles;
    }


    protected function getTimedFileName()
    {
        $arrFileInfo = pathinfo($this->m_sBaseFilePathName);
        $sTimedFileName = str_replace(
            ['{filename}', '{date}'],
            [$arrFileInfo['filename'], date($this->m_sDateFormat)],
            $arrFileInfo['dirname'] . '/' . $this->m_sFileNameFormat
        );
        if (!empty($arrFileInfo['extension'])) {
            $sTimedFileName .= '.'.$arrFileInfo['extension'];
        }

        return $sTimedFileName;
    }


    public function write($arrRecord = [])
    {
        //第一次写的情况，先表名需要切换，然后再close，再在close中切换
        if ($this->m_bTriggerRotate === null){
            $this->m_bTriggerRotate = !file_exists($this->m_sStreamUrl);
        }

        if ($this->m_datetimeNextDay <= $arrRecord['datetime']){
            $this->m_bTriggerRotate = true;
            $this->close();
        }

        //父write会自动创建stream
        parent::write($arrRecord);
    }


    public function close()
    {
        parent::close();

        if ($this->m_bTriggerRotate){
            $this->rotate();
        }
    }


    public function rotate()
    {
        $this->m_sStreamUrl = $this->getTimedFileName();
        $this->m_datetimeNextDay = new DateTime('tomorrow');

        //后续处理最大保留个数

        if ($this->m_nMaxKeepFiles === 0){
            return;
        }

        $arrLogFiles = glob($this->getGlobPattern());
        if ($this->m_nMaxKeepFiles >= count($arrLogFiles)) {
            return;
        }

        //排序文件名
        usort($arrLogFiles, function ($a, $b) {
            return strcmp($b, $a);
        });

        foreach (array_slice($arrLogFiles, $this->m_nMaxKeepFiles) as $sFile) {
            if (is_writable($sFile)) {
                //避免多个进程/线程同时删除报错
                set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                    return false;
                });
                unlink($sFile);
                restore_error_handler();
            }
        }

        $this->m_bTriggerRotate = false;
    }


    protected function getGlobPattern()
    {
        $arrFileInfo = pathinfo($this->m_sBaseFilePathName);
        $sGlob = str_replace(
            ['{filename}', '{date}'],
            [$arrFileInfo['filename'], '[0-9][0-9][0-9][0-9]*'],
            $arrFileInfo['dirname'] . '/' . $this->m_sFileNameFormat
        );
        if (!empty($arrFileInfo['extension'])) {
            $sGlob .= '.'.$arrFileInfo['extension'];
        }

        return $sGlob;
    }
}