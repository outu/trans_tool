<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-30 10:53:33 CST
 *  Description:     StreamHandler.php's function description
 *  Version:         1.0.0.20180330-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-30 10:53:33 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log\LogHandler;

use Capsheaf\Log\LogLevel;
use InvalidArgumentException;
use LogicException;

/**
 * 用于处理fopen能够打开的各种stream，如文件，http，ftp，stdout等
 * Class StreamHandler
 * @package Capsheaf\Log\LogHandler
 * @see http://php.net/manual/en/wrappers.php
 */
class StreamHandler extends AbstractHandler
{

    protected $m_sStreamUrl;
    protected $m_hStream;
    protected $m_nStreamPermission;
    protected $m_bUseLock;
    protected $m_bDirectoryCreated = false;
    protected $m_sErrorMessage = '';


    /**
     * StreamHandler constructor.
     * @param string|resource $stream
     * @param int $nMinLevel
     * @param bool $bBubble
     * @param int|null $nStreamPermission 日志文件权限
     * @param bool $bUseLock
     */
    public function __construct($stream, $nMinLevel = LogLevel::DEBUG, $bBubble = true, $nStreamPermission = 0777, $bUseLock = false)
    {
        parent::__construct($nMinLevel, $bBubble);

        if (is_resource($stream)){
            $this->m_hStream = $stream;
        } elseif (is_string($stream)){
            $this->m_sStreamUrl = $stream;
        } else {
            throw new InvalidArgumentException('The stream must be a resource or string.');
        }

        $this->m_nStreamPermission = $nStreamPermission;
        $this->m_bUseLock = $bUseLock;
    }


    protected function write($arrRecord = [])
    {
        if (!is_resource($this->m_hStream)){
            $this->createStream();
        }

        //Acquire lock
        if ($this->m_bUseLock){
            flock($this->m_hStream, LOCK_EX);
        }

        $this->streamWrite($this->m_hStream, $arrRecord);

        //Release lock
        if ($this->m_bUseLock){
            flock($this->m_hStream, LOCK_UN);
        }
    }


    protected function createStream()
    {
        //注意0也能为文件路径，判断时要注意
        if ($this->m_sStreamUrl === null || $this->m_sStreamUrl === ''){
            throw new LogicException('Stream url is empty while creating a stream handle.');
        }

        $this->m_sErrorMessage = '';
        set_error_handler([$this, 'errorHandler']);
        $this->m_hStream = fopen($this->m_sStreamUrl, 'a');
        restore_error_handler();

        if ($this->m_nStreamPermission !== null){
            @chmod($this->m_sStreamUrl, $this->m_nStreamPermission);
        }

        if (!is_resource($this->m_hStream)){
            //清空错误的值
            $this->m_hStream = null;
            throw new InvalidArgumentException(
                sprintf('Stream %s could not be opened: %s', $this->m_sStreamUrl, $this->m_sErrorMessage)
            );
        }
    }


    protected function createDirectory()
    {
        if ($this->m_bDirectoryCreated){
            return;
        }

        $sDir = $this->getDirectoryFromUrl($this->m_sStreamUrl);

        if ($sDir !== null && !is_dir($sDir)){

            $this->m_sErrorMessage = '';
            set_error_handler([$this, 'errorHandler']);
            $bSuccess = mkdir($sDir, 0777, true);
            restore_error_handler();

            if (!$bSuccess){
                throw new UnexpectedValueException(
                    sprintf('Create directory %s failed: %s', $sDir, $this->m_sErrorMessage)
                );
            }
        }
    }


    /**
     * 从如file:///path/to/file.ext,C:\path\to\winfile.ext等形式的文件路径获取目录路径
     * @param string $sStreamUrl
     * @return null|string
     */
    protected function getDirectoryFromUrl($sStreamUrl)
    {
        $nPos = strpos($sStreamUrl, '://');
        if ($nPos === false){
            return dirname($sStreamUrl);
        }

        if ('file://' === substr($sStreamUrl, 0, 7)){
            return dirname(substr($sStreamUrl, 7));
        }

        return null;
    }


    /**
     * 写入流操作
     * @param resource $hStream
     * @param array $arrRecord
     */
    protected function streamWrite($hStream, $arrRecord)
    {
        fwrite($hStream, (string)$arrRecord['formatted']);
    }


    /**
     * 通过临时设置错误处理句柄，自主定义消息格式
     * @param int $nCode
     * @param string $sMessage
     */
    private function errorHandler($nCode, $sMessage)
    {
        $this->m_sErrorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $sMessage);
    }


    public function close()
    {
        if ($this->m_sStreamUrl && is_resource($this->m_hStream)) {
            fclose($this->m_hStream);
        }

        $this->m_hStream = null;
    }

}
