<?php
/**
 * Created by PhpStorm.
 * User: JustNanf
 * Date: 2020/9/15
 * Time: 15:28
 */



namespace CapsheafBuilder\Modules\Builder;

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

class WindowsClientBsBuilderController extends BaseController
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
    public function build($sVersion = '', $sBuildToolPath = "", $bEncrypt = false, $sRelease = 'alpha')
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
            $sBuildExeFile_x86 = "{$sRootDir}/Builds/Capsheaf-Agent-BS-{$sVersion}-{$sDateToday}-{$sGitVersion}-{$sRelease}-x86.exe";
            $sBuildExeFile_x64 = "{$sRootDir}/Builds/Capsheaf-Agent-BS-{$sVersion}-{$sDateToday}-{$sGitVersion}-{$sRelease}-x64.exe";
            $sBuildTempFile_x86 = "{$sRootDir}/Components/CapsheafPkg_Windows_Bs/Media/DRCBS_SETUP_x86/Package/DRCBS_SETUP_X86.exe";
            $sBuildTempFile_x64 = "{$sRootDir}/Components/CapsheafPkg_Windows_Bs/Media/DRCBS_SETUP_x64/Package/DRCBS_SETUP_X64.exe";
            $nTempExeMTime = '';
            if(file_exists($sBuildTempFile_x86)) {
                $nTempExeMTime = FileSystem::lastModified($sBuildTempFile_x86);//Time OR False
            }
            $sIsmProjectFile = "{$sRootDir}/Components/CapsheafPkg_Windows_Bs/Nisec.ism";
            $sCommand_x86 = "\"{$sBuildToolPath}\" -p \"{$sIsmProjectFile}\" -r DRCBS_SETUP_x86 -c COMP";
            $sCommand_x64 = "\"{$sBuildToolPath}\" -p \"{$sIsmProjectFile}\" -r DRCBS_SETUP_x64 -c COMP";
            $sConfigClientFile_x86 = "{$sRootDir}/Components/CapsheafPkg_Windows_Bs/DRC2K3_X86/version";
            $sConfigClientFile_x64 = "{$sRootDir}/Components/CapsheafPkg_Windows_Bs/DRC2K3_X64/version";
            file_put_contents($sConfigClientFile_x86, "{$sVersion} : {$sGitVersion}");
            file_put_contents($sConfigClientFile_x64, "{$sVersion} : {$sGitVersion}");

            $process = new Process($sCommand_x86, null, null, null, 3600);
            $nRetCode = $process->run();

            $nTempExeMTimeNew = FileSystem::lastModified($sBuildTempFile_x86);
            $sOutput = $process->getOutput();

            if ($nRetCode != 0 || $nTempExeMTimeNew == $nTempExeMTime){
                $sErrorOutput = $process->getErrorOutput();
                throw new RuntimeException("Build failed:\r\n".$sErrorOutput."\r\n".$sOutput);
            }

            $process = new Process($sCommand_x64, null, null, null, 3600);
            $nRetCode = $process->run();
            if ($nRetCode != 0 || $nTempExeMTimeNew == $nTempExeMTime){
                $sErrorOutput = $process->getErrorOutput();
                throw new RuntimeException("Build failed:\r\n".$sErrorOutput."\r\n".$sOutput);
            }

            FileSystem::copy($sBuildTempFile_x86, $sBuildExeFile_x86, true);
            FileSystem::copy($sBuildTempFile_x64, $sBuildExeFile_x64, true);

            return $this->success("File wrote to: \r\n $sBuildExeFile_x86 \r\n $sBuildExeFile_x64 \r\n", $sOutput);
        } catch (Exception $exception) {
            return $this->error(404, $exception->getMessage());
        }
    }

}