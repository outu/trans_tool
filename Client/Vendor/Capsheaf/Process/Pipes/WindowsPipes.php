<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-09 23:59:08 CST
 *  Description:     WindowsPipes.php's function description
 *  Version:         1.0.0.20180409-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-09 23:59:08 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\Pipes;

use Capsheaf\Process\Process;
use RuntimeException;

class WindowsPipes extends AbstractPipes
{

    /**
     * 用于获取重定向输出时的临时文件路径
     * @var string[]
     */
    protected $m_arrRedirectFiles = [];

    /**
     * 用于获取重定向输出时的临时文件打开的句柄
     * @var resource[]
     */
    protected $m_arrRedirectFileHandles = [];

    /**
     * 用于记录重定向文件读取到的位置
     * @var int[]
     */
    protected $m_arrFilesReadBytesCount = [
        Process::STDOUT => 0,
        Process::STDERR => 0,
    ];


    public function __construct($input, $bWantOutput = true)
    {
        parent::__construct($input, $bWantOutput);

        $arrRedirectedFileAsPipes = [
            Process::STDOUT => Process::OUT_TYPE_STDOUT,
            Process::STDERR => Process::OUT_TYPE_STDERR
        ];

        $sTempDir = sys_get_temp_dir();
        $sLastError = 'Unknown error.';

        //$sTempDir能否正常写文件的标记
        $bTempDirFine = false;

        set_error_handler(
            function ($nErrLevel, $sErrStr) use (&$sLastError){
                $sLastError = $sErrStr;
            }
        );

        //递进找到合适的文件名
        for ($i = 0;; ++$i){
            foreach ($arrRedirectedFileAsPipes as $nPipeStdNumber => $sFileName){
                $sFilePath = sprintf('%s\\tf_proc_%02X.%s', $sTempDir, $i, $sFileName);

                //存在这个文件但是是删除不了的（LOCK），就更新一个$i的值再次尝试
                if (file_exists($sFilePath) && !unlink($sFilePath)){
                    continue 2;
                }

                //互斥模式打开
                $hFile = fopen($sFilePath, 'xb');
                if (!$hFile){
                    $sError = $sLastError;
                    //检查是可以正常创建和删除临时文件的
                    //注意的是tempnam会实际创建一个文件，如：C:\Users\YANTAO\AppData\Local\Temp\tf_A933.tmp
                    //Creates a file with a unique filename, 【with access permission set to 0600】, in the specified directory. 【If the directory does not exist or is not writable, tempnam() may generate a file in the system's temporary directory】, and return the 【full path】 to that file, including its name.
                    if ($bTempDirFine || $bTempDirFine = unlink(tempnam($sTempDir, 'tf_'))){
                        continue;
                    }

                    restore_error_handler();
                    throw new RuntimeException(sprintf('Temporary file could not be opened and write to the process output: %s', $sError));
                }

                //若该Pipe的文件句柄设置失败，或者rb模式打开文件失败，则跳出换个$i再试
                if (!$hFile || (!$this->m_arrRedirectFileHandles[$nPipeStdNumber] = fopen($sFilePath, 'rb'))){
                    continue 2;
                }

                if (isset($this->m_arrRedirectFiles[$nPipeStdNumber])){
                    unlink($this->m_arrRedirectFiles[$nPipeStdNumber]);
                }

                //设置了该Pipe的重定向文件路径
                $this->m_arrRedirectFiles[$nPipeStdNumber] = $sFilePath;

            }//end foreach

            //一旦foreach完成就可以跳出了
            break;
        }//end for

        restore_error_handler();
    }


    public function __destruct()
    {
        $this->close();
        $this->removeFiles();
    }


    public function close()
    {
        parent::close();
        foreach ($this->m_arrRedirectFileHandles as $hRedirectFileHandle){
            fclose($hRedirectFileHandle);
        }
        $this->m_arrRedirectFileHandles = [];
    }


    /**
     * 移除重定向输出用的临时文件
     */
    public function removeFiles()
    {
        foreach ($this->m_arrRedirectFiles as $sFile){
            if (file_exists($sFile)){
                @unlink($sFile);
            }
        }
        $this->m_arrRedirectFiles = [];
    }


    public function getDescriptors()
    {
        if (!$this->m_bWantOutput){
            //Open the file for writing only. If the file does not exist, it is created. If it exists, it is neither truncated (as opposed to 'w'), nor the call to this function fails
            $hNullStream = fopen('NUL', 'c');

            return [
                ['pipe', 'r'],
                $hNullStream,
                $hNullStream
            ];
        }

        //Windows下使用PIPE输出会挂起程序，使用文件会造成文件内容损坏，所以需要在windows下将stdout和stderr重定向到指定的文件中
        return [
            ['pipe', 'r'],//输入没有影响
            ['file', 'NUL', 'w'],
            ['file', 'NUL', 'w']
        ];
    }


    public function getFiles()
    {
        return $this->m_arrRedirectFiles;
    }


    public function readAndWrite($bBlockingMode, $bCloseOnEof = false)
    {
        $this->unBlock();

        $arrStreamsToWrite = $this->write();

        $arrReadGotStrings = [];

        $arrStreamsToRead = [];
        $arrStreamsToExcept = [];

        if ($bBlockingMode){
            if ($arrStreamsToWrite){
                @stream_select($arrStreamsToRead, $arrStreamsToWrite, $arrStreamsToExcept, 0, Process::TIMEOUT_PRECISION * 1E6);
            } elseif ($this->m_arrRedirectFileHandles){
                usleep(Process::TIMEOUT_PRECISION * 1E6);
            }
        }

        foreach ($this->m_arrRedirectFileHandles as $nPipeStdNumber => $hRedirectFileHandle){
            $sData = stream_get_contents($hRedirectFileHandle, -1, $this->m_arrFilesReadBytesCount[$nPipeStdNumber]);

            if (isset($sData[0])){
                //修改文件偏移
                $this->m_arrFilesReadBytesCount[$nPipeStdNumber] += strlen($sData);
                //设置返回的字符串内容
                $arrReadGotStrings[$nPipeStdNumber] = $sData;
            }

            if ($bCloseOnEof){
                fclose($hRedirectFileHandle);
                unset($this->m_arrRedirectFileHandles[$nPipeStdNumber]);
            }
        }

        return $arrReadGotStrings;
    }


    public function areOpen()
    {
        return $this->m_arrPipes && $this->m_arrRedirectFileHandles;
    }

}
