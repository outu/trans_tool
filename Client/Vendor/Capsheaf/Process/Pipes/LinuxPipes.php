<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-09 23:59:18 CST
 *  Description:     LinuxPipes.php's function description
 *  Version:         1.0.0.20180409-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-09 23:59:18 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\Pipes;

use Capsheaf\Process\Process;

class LinuxPipes extends AbstractPipes
{

    public function getDescriptors()
    {
        if (!$this->m_bWantOutput){
            //Open the file for writing only. If the file does not exist, it is created. If it exists, it is neither truncated (as opposed to 'w'), nor the call to this function fails
            $hNullStream = fopen('/dev/null', 'c');

            return [
                ['pipe', 'r'],
                $hNullStream,
                $hNullStream
            ];
        }

        return [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w']
        ];
    }


    public function getFiles()
    {
        return [];
    }


    public function readAndWrite($bBlockingMode, $bCloseOnEof = false)
    {
        $this->unBlock();

        $arrStreamsToWrite = $this->write();

        $arrReadGotStrings = [];
        $arrStreamsToExcept = [];
        $arrStreamsToRead = $this->m_arrPipes;
        //移除stdin，仅留下stdout,stderr
        unset($arrStreamsToRead[0]);

        if (
            ($arrStreamsToWrite || $arrStreamsToRead) &&
            false === @stream_select($arrStreamsToRead, $arrStreamsToWrite, $arrStreamsToExcept, 0, $bBlockingMode ? Process::TIMEOUT_PRECISION * 1E6 : 0)
        ){
            if (!$this->isSystemCallBeenInterrupted()){
                $this->m_arrPipes = [];
            }

            return $arrReadGotStrings;
        }

        foreach ($arrStreamsToRead as $hStdPipe){
            $arrReadGotStrings[$nKey = array_search($hStdPipe, $this->m_arrPipes, true)] = '';

            do {
                $sData = fread($hStdPipe, self::CHUNK_SIZE);
                $arrReadGotStrings[$nKey] .= $sData;
            } while (isset($sData[0]) && ($bCloseOnEof || isset($sData[self::CHUNK_SIZE - 1])));

            //要是该类型的pipe没有读取到数据，就在返回的数组中unset
            if (!isset($arrReadGotStrings[$nKey][0])){
                unset($arrReadGotStrings[$nKey]);
            }

            if ($bCloseOnEof && feof($hStdPipe)){
                fclose($hStdPipe);
                unset($this->m_arrPipes[$nKey]);
            }
        }

        return $arrReadGotStrings;
    }


    public function areOpen()
    {
        return (bool)$this->m_arrPipes;
    }

}
