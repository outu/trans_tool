<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-29 11:36:21 CST
 *  Description:     BaseController.php's function description
 *  Version:         1.0.0.20180529-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-29 11:36:21 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Modules;

use Capsheaf\Application\Application;
use Capsheaf\FileSystem\FileSystem;
use Capsheaf\Foundation\Controller\Controller;
use Capsheaf\Foundation\Request\Request;
use RuntimeException;

class BaseController extends Controller
{

    protected $m_bLogStarted = false;
    protected $m_sLogFilePath;

    protected $m_sRootDir;
    protected $m_sPatchRootDir;

    protected $m_sSrcPathToPatch;
    protected $m_sDestPathToPatch;

    public function __construct(Application $app, Request $request)
    {
        parent::__construct($app, $request);
        $this->m_sLogFilePath = RUNTIME_PATH.basename(get_class($this)).".log";
    }


    public function log($sMessage, $bEol = true)
    {
        //在这个后台进程执行构建时，首先情况文件内容
        if ($this->m_bLogStarted == false){
            file_put_contents($this->m_sLogFilePath, '');
            $this->m_bLogStarted = true;
        }

        file_put_contents($this->m_sLogFilePath, $sMessage.($bEol ? PHP_EOL : ''), FILE_APPEND);
    }


    protected function setBaseRoot($sRootDir)
    {
        $sRootDir = FileSystem::unifyDirPath($sRootDir, false);
        $this->m_sRootDir = $sRootDir;
    }


    protected function setPatchBaseRoot($sPatchRootDir)
    {
        $sPatchRootDir = FileSystem::unifyDirPath($sPatchRootDir, false);
        $this->m_sPatchRootDir = $sPatchRootDir;
    }


    public function copy($sSrcPath, $sDistPath)
    {
        $sSrcPath = ltrim($sSrcPath, "/\\");
        $this->log("Coping {$sSrcPath} to {$sDistPath}...");
        FileSystem::copy($this->m_sRootDir.$sSrcPath, $sDistPath, true);

        return $this;
    }


    public function copyWithPatches($sSrcPath, $sDistPath)
    {
        if (empty($this->m_sPatchRootDir) || empty($this->m_sRootDir)){
            throw new RuntimeException("Empty root or patch root directory given.");
        }

        $this->log("Coping {$sSrcPath} to {$sDistPath}...");

        $sSrcPath = ltrim($sSrcPath, '/\\');

        if (FileSystem::exists($this->m_sRootDir.$sSrcPath)){
            FileSystem::copy($this->m_sRootDir.$sSrcPath, $sDistPath, true);
        }

        if (!FileSystem::exists($this->m_sPatchRootDir.$sSrcPath)){
             return $this;
        } else {
            FileSystem::copy($this->m_sPatchRootDir.$sSrcPath, $sDistPath, true);
        }

        return $this;
    }

}