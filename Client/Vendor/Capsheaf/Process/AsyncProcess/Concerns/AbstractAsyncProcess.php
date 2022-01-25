<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-03 16:27:21 CST
 *  Description:     AsyncProcessInterface.php's function description
 *  Version:         1.0.0.20180503-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-03 16:27:21 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\AsyncProcess\Concerns;

abstract class AbstractAsyncProcess implements AsyncProcessInterface
{

    /**
     * 要执行的命令
     * @var string
     */
    protected $m_sCommand;

    /**
     * 输入重定向文件
     * @var string
     */
    protected $m_sStdInFile;

    /**
     * 输出重定向文件
     * @var string
     */
    protected $m_sStdOutFile;

    /**
     * 错误重定向文件，为false则指向$m_sStdOutFile，为null则弃用
     * @var string
     */
    protected $m_sStdErrFile;

    /**
     * 是否处于运行状态
     * @var bool
     */
    protected $m_bRunning;

    /**
     * 进程PID
     * @var int
     */
    protected $m_nPid;


    /**
     * @param string $sCommand 进程命令
     * @param null|string $sStdOutFile STDOUT文件路径，null表示不输出
     * @param null|string $sStdInFile STDIN文件路径，null表示无输入
     * @param null|false|string $sStdErrFile STDERR文件路径，null表示不关注，false表示使用STDOUT相同的输出
     */
    public function __construct($sCommand, $sStdOutFile = null, $sStdInFile = null, $sStdErrFile = null)
    {
        $this->m_sCommand       = $sCommand;
        $this->m_sStdOutFile    = $sStdOutFile;
        $this->m_sStdInFile     = $sStdInFile;
        $this->m_sStdErrFile    = $sStdErrFile;

        $this->m_nPid           = 0;
        $this->m_bRunning       = false;
    }


    /**
     * 组装重定向字符串
     * @return string
     */
    protected function getRedirectString()
    {
        $sRedirect = '';
        if (is_string($this->m_sStdInFile)){
            $sRedirect .= '<"'.$this->m_sStdInFile.'" ';
        }

        if (is_string($this->m_sStdOutFile)){
            $sRedirect .= '>"'.$this->m_sStdOutFile.'" ';
        } else {
            if (windows_os()){
                $sRedirect .= '>NUL ';
            } else {
                $sRedirect .= '>/dev/null ';
            }
        }

        if (is_string($this->m_sStdErrFile)){
            $sRedirect .= '2>"'.$this->m_sStdErrFile.'" ';
        } elseif ($this->m_sStdErrFile === false){
            $sRedirect .= '2>&1 ';
        }

        return $sRedirect;
    }


    protected function escapeArgument($sArgument)
    {
        return str_replace('"', '\"', $sArgument);
    }

}
