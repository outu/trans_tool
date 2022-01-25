<?php
/******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 14:05:17 CST
 *  Description:     WindowsClientBuilderController.php's function description
 *  Version:         1.0.0.20180314-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-14 14:05:17 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Modules\Builder;

use Capsheaf\Application\Application;
use Capsheaf\FileSystem\FileSystem;
use Capsheaf\Foundation\Request\Request;
use Capsheaf\Process\Process;
use CapsheafBuilder\Models\Git\Git;
use CapsheafBuilder\Modules\BaseController;
use Exception;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class WindowsClientBuilderController extends BaseController
{

    protected $m_sEncryptExePath;


    public function __construct(Application $app, Request $request)
    {
        parent::__construct($app, $request);

        $this->m_sEncryptExePath = ROOT_PATH.'Bin/Interpreter/Src/php-5.4.45/ext/code_obfus/tools/code_obfus_encode_file'.(windows_os() ? '.exe' : '');
    }


    public function index()
    {
        return $this->success();
    }


    /**
     * @param string $sVersion EG: 2.4.0
     * @param string $sBuildToolPath EG: C:\Program Files (x86)\InstallShield\2015\System\IsCmdBld.exe
     * @param bool $bEncrypt �Ƿ����php����
     * @param string $sRelease EG: alpha|beta|null
     * @return \Capsheaf\Foundation\Response\Http\JsonResponse
     */
    public function build($sVersion = '', $sBuildToolPath, $bEncrypt = false, $sRelease = 'alpha')
    {
        try {
            if (empty($sVersion)){
                throw new InvalidArgumentException("Empty version number.");
            }

            if (!windows_os()){
                throw new RuntimeException("Must running on windows os to build the exe file.");
            }

            $sNewProjectDir = ROOT_PATH;
            $sRootDir = dirname($sNewProjectDir);
            $sDateToday = date('Ymd');
            $sGitVersion = (new Git())->getCurrentVersion('.');
            $sBuildExeFile = "{$sRootDir}/Builds/Capsheaf-Agent-PF.FSDB-".($bEncrypt ? 'EC' : 'NC').".PS-{$sVersion}-{$sDateToday}-{$sGitVersion}-{$sRelease}-x86.exe";

            $sBuildTempFile = "{$sRootDir}/Components/CapsheafClientWindows_Platform_BaseFsDb/capsheaf/Media/SINGLE_EXE_IMAGE/Package/Setup.exe";
            $nTempExeMTime = FileSystem::lastModified($sBuildTempFile);//Time OR False

            $sIsmProjectFile = "{$sRootDir}/Components/CapsheafClientWindows_Platform_BaseFsDb/capsheaf.ism";

            //�޸İ汾��
            $sConfigClientFile = "{$sRootDir}/Components/CapsheafClientWindows_Platform_BaseFsDb/capsheaf/capsheaf/client/config/config.client";
            $sConfigClient = file_get_contents($sConfigClientFile);
            $sConfigClient = preg_replace("/ClientVersion=(\S+)/", "ClientVersion={$sVersion}", $sConfigClient);
            file_put_contents($sConfigClientFile, $sConfigClient);

            if ($bEncrypt) {
                $this->prepareEncryptedFiles();
            }

            $sCommand = "\"{$sBuildToolPath}\" -p \"{$sIsmProjectFile}\" -c COMP";
            $process = new Process($sCommand, null, null, null, 3600);
            $nRetCode = $process->run();

            $nTempExeMTimeNew = FileSystem::lastModified($sBuildTempFile);
            $sOutput = $process->getOutput();

            if ($nRetCode != 0 || $nTempExeMTimeNew == $nTempExeMTime){
                $sErrorOutput = $process->getErrorOutput();
                throw new RuntimeException("Build failed:\r\n".$sErrorOutput."\r\n".$sOutput);
            }

            FileSystem::copy($sBuildTempFile, $sBuildExeFile, true);

            if ($bEncrypt) {
                $this->restoreUnEncryptedFiles();
            }

            return $this->success("File wrote to: $sBuildExeFile,\r\n", $sOutput);
        } catch (Exception $exception) {
            return $this->error(404, $exception->getMessage());
        }
    }


    private function prepareEncryptedFiles()
    {
        $sNewProjectDir = ROOT_PATH;
        $sRootDir = dirname($sNewProjectDir);

        $sAbsFolder = "{$sRootDir}/Capsheaf/CapsheafClient";
        $this->backup($sAbsFolder);
        $this->encryptPhpFolderInPlace($sAbsFolder);


        $sAbsFolder = "{$sRootDir}/Capsheaf/Vendor";
        $this->backup($sAbsFolder);
        $this->encryptPhpFolderInPlace($sAbsFolder);


        $sAbsFolder = "{$sRootDir}/Components/CapsheafClientCommon_PhpFsDb";
        $this->backup($sAbsFolder);
        $this->encryptPhpFolderInPlace($sAbsFolder, [
            "{$sRootDir}/Components/CapsheafClientCommon_PhpFsDb/mysql/mysqlcontroller/MYS_GlobalError.php"
        ]);

    }


    private function restoreUnEncryptedFiles()
    {
        $sNewProjectDir = ROOT_PATH;
        $sRootDir = dirname($sNewProjectDir);

        $sAbsFolder = "{$sRootDir}/Capsheaf/CapsheafClient";
        $this->restore($sAbsFolder);


        $sAbsFolder = "{$sRootDir}/Capsheaf/Vendor";
        $this->restore($sAbsFolder);


        $sAbsFolder = "{$sRootDir}/Components/CapsheafClientCommon_PhpFsDb";
        $this->restore($sAbsFolder);

    }


    private function backup($sSrcFolder)
    {
        $sSrcFolder = str_replace('/', "\\", $sSrcFolder);
        $sSrcFolder = rtrim($sSrcFolder, "\\");

        $arrOutput = [];
        $nRetCode = 1;
        exec("xcopy \"{$sSrcFolder}\" \"$sSrcFolder"."_ENC_BACKUP\\\" /E /K /Y", $arrOutput, $nRetCode);
        if ($nRetCode != 0){
            throw new RuntimeException("Backup folder {$sSrcFolder} failed: {$nRetCode}");
        }
    }


    private function restore($sSrcFolder)
    {
        $sSrcFolder = str_replace('/', "\\", $sSrcFolder);

        $sSrcFolder = rtrim($sSrcFolder, "\\");

        $arrOutput = [];
        $nRetCode = 1;
        exec("xcopy \"$sSrcFolder"."_ENC_BACKUP\" \"{$sSrcFolder}\\\"  /E /K /Y", $arrOutput, $nRetCode);

        if ($nRetCode != 0){
            throw new RuntimeException("Restore folder {$sSrcFolder} failed: {$nRetCode}");
        }

        exec("rmdir /S /Q \"$sSrcFolder"."_ENC_BACKUP\"");
    }


    private function encryptPhpFolderInPlace($sFolderAbsPath, $arrIgnored = [])
    {
        $dir_iterator = new RecursiveDirectoryIterator($sFolderAbsPath);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $sLocalPathFileName = $file->getFilename();
            if ($sLocalPathFileName == '.' || $sLocalPathFileName == '..'){
                continue;
            }

            //ע��Ŀ¼Ҳû�к�׺/
            $sLocalPath = str_replace('\\', '/', $file->getPathname());
            foreach ($arrIgnored as $sIgnore){
                if (strpos($sLocalPath, $sIgnore) === 0){
                    continue 2;
                }
            }

            if ($file->isFile()) {
                $this->encryptPhpFileInPlace($sLocalPath);
            }
        }

        return true;
    }


    private function encryptPhpFileInPlace($sFileAbsPath)
    {
        $nRetCode = 1;

        $info = new \SplFileInfo($sFileAbsPath);
        $sExt = $info->getExtension();
        if ($sExt == 'php'){
            $arrOutput = [];
            $sFileAbsPath = str_replace("\\", '/', $sFileAbsPath);
            exec("{$this->m_sEncryptExePath} \"{$sFileAbsPath}\" \"{$sFileAbsPath}\"", $arrOutput, $nRetCode);
            if ($nRetCode != 0) {
                throw new RuntimeException("Encrypt php file {$sFileAbsPath} failed: {$nRetCode}");
            }
        }
    }
}