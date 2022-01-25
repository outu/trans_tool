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
     * @var string ��־�ļ����ƣ�������ǰ��Ŀ¼·��
     */
    protected $m_sFileNameFormat = '{filename}-{date}';

    /**
     * @var null|bool null��һ�Σ�false����Ҫ��true��Ҫ�л�
     */
    protected $m_bTriggerRotate = null;

    /**
     * ��־����������
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
        //��һ��д��������ȱ�����Ҫ�л���Ȼ����close������close���л�
        if ($this->m_bTriggerRotate === null){
            $this->m_bTriggerRotate = !file_exists($this->m_sStreamUrl);
        }

        if ($this->m_datetimeNextDay <= $arrRecord['datetime']){
            $this->m_bTriggerRotate = true;
            $this->close();
        }

        //��write���Զ�����stream
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

        //�����������������

        if ($this->m_nMaxKeepFiles === 0){
            return;
        }

        $arrLogFiles = glob($this->getGlobPattern());
        if ($this->m_nMaxKeepFiles >= count($arrLogFiles)) {
            return;
        }

        //�����ļ���
        usort($arrLogFiles, function ($a, $b) {
            return strcmp($b, $a);
        });

        foreach (array_slice($arrLogFiles, $this->m_nMaxKeepFiles) as $sFile) {
            if (is_writable($sFile)) {
                //����������/�߳�ͬʱɾ������
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