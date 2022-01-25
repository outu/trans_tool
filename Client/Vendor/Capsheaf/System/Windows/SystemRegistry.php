<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-09 16:26:23 CST
 *  Description:     SystemRegistry.php's function description
 *  Version:         1.0.0.20180409-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-09 16:26:23 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Windows;

use Capsheaf\Process\Process;
use Capsheaf\System\SystemInfo\SystemInfo;
use Exception;
use RuntimeException;

class SystemRegistry
{

    public function __construct()
    {

    }


    /**
     * 查询注册键列表
     * @param string $sNode
     * @param null|string $sKey
     * @return array <br>
     * <pre>
     * 例如查询：HKEY_LOCAL_MACHINE\SOFTWARE\Mozilla\Mozilla Firefox\60.0 (x64 en-US)\
     * [
     * 'values' => [
     *          '(Default)' => [
     *              'type' => "REG_SZ",
     *              'value' => "60.0 (x64 en-US)",
     *          ],
     *      ],
     * 'subKeys' => [
     *          [0] => "HKEY_LOCAL_MACHINE\SOFTWARE\Mozilla\Mozilla Firefox\60.0 (x64 en-US)\Main",
     *          [1] => "HKEY_LOCAL_MACHINE\SOFTWARE\Mozilla\Mozilla Firefox\60.0 (x64 en-US)\Uninstall",
     *      ],
     * ]
     * </pre>
     */
    public function query($sNode, $sKey = null)
    {
        $sNode = trim($sNode, ' \\');
        $sCommand = 'QUERY "'.$sNode.'"'.($sKey !== null ? ' /v "'.$sKey.'"' : '');
        $sOutput = $this->exec($sCommand);
        $arrLines = explode("\r\n", $sOutput);

        $arrValues = [
            'values'    => [],
            'subKeys'   => [],
        ];

        foreach ($arrLines as $nLine => $sLine){
            //去除最右边的空格
            $sLine = rtrim($sLine, ' ');
            if (empty($sLine)){
                continue;
            }

            //前缀相同但是还有后缀
            if (strpos($sLine, $sNode) === 0 && strcmp($sLine, $sNode) > 0){
                $arrValues['subKeys'][] = $sLine;
            } elseif (strpos($sLine, "    ") === 0){//四个空格开头表示值
                $arrKTV = preg_split("/[\x20]{4}/", substr($sLine ,4), 3);
                $arrValues['values'][$arrKTV[0]] = [
                    'type'  => $arrKTV[1],
                    'value' => isset($arrKTV[2]) ? $arrKTV[2] : ''
                ];
            }
        }

        return $arrValues;
    }


    public function add()
    {

    }


    public function delete()
    {

    }


    public function copy()
    {

    }


    public function save()
    {

    }


    public function restore()
    {

    }


    public function load()
    {

    }


    public function unload()
    {

    }


    public function compare()
    {

    }


    public function export()
    {

    }


    public function import()
    {

    }


    public function flags()
    {

    }


    protected function exec($sCommand)
    {
        if (SystemInfo::isX86()){
            $sBin = '%windir%\sysnative\REG';
        } else {
            $sBin = '%windir%\System32\REG';
        }

        $sCommand = $sBin.' '.$sCommand;

        $sOutput = null;
        try {
            $process = new Process($sCommand);
            $nExitCode = $process->run();
            if ($nExitCode){
                $sError = $process->getErrorOutput();
                throw new RuntimeException("Command:{$sCommand} failed. Error (exit code:{$nExitCode}): {$sError}.");
            }

            $sOutput = $process->getOutput();
        } catch (Exception $exception){
            throw new RuntimeException("Execute reg failed {$exception->getMessage()}");
        }

        return $sOutput;
    }

}
