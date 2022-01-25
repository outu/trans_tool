<?php
/******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 14:05:17 CST
 *  Description:     LinuxClientBuilderController.php's function description
 *  Version:         1.0.0.20180314-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-14 14:05:17 CST initialized the file
 ******************************************************************************/

namespace CapsheafBuilder\Modules\Builder;

use CapsheafBuilder\Models\Git\Git;
use CapsheafBuilder\Models\Package\Zip;
use CapsheafBuilder\Modules\BaseController;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class LinuxClientBuilderController extends BaseController
{

    public function index()
    {
        return $this->success();
    }


    /**
     * @param string $sVersion EG: 2.4.0
     * @param string $sArch
     * @param false $bEncrypt 是否加密php代码
     * @param string $sRelease EG: alpha|beta|null
     * @param string $sType
     * @param string $sIp
     * @param string $sPasswd
     * @return \Capsheaf\Foundation\Response\Http\JsonResponse
     */
    public function build($sVersion = '', $sArch = '', $bEncrypt = false, $sRelease = 'alpha', $sType = '', $sIp = '127.0.0.1', $sPasswd = '')
    {
        try {
            $sGitVersion = (new Git())->getCurrentVersion('.');

            $sBuildZipFile = $this->buildZip($sGitVersion, $sVersion, $sArch, $bEncrypt, $sRelease);

            return $this->success($sBuildZipFile);
        } catch (Exception $exception) {
            return $this->error(400, "zip file failed, " . $exception->getMessage());
        }
    }


    /**
     * @param string $sGitVersion git hash
     * @param string $sVersion 版本号
     * @param string $sArch
     * @param bool $bEncrypt 是否加密php代码
     * @param string $sRelease
     * @return string
     */
    protected function buildZip($sGitVersion, $sVersion = '', $sArch = '', $bEncrypt = false, $sRelease = 'alpha')
    {
        try {
            if (empty($sVersion)){
                throw new InvalidArgumentException("Empty version number.");
            }

            if (empty($sArch)){
                throw new InvalidArgumentException("Empty arch info.");
            }

            $sVersionString = '';
            $arrVersionSegs = explode('.', $sVersion);
            foreach ($arrVersionSegs as $sSeg){
                $sVersionString = $sVersionString.sprintf('%02d', (int)$sSeg);
            }

            switch ($sRelease){
                case 'alpha':
                    $sVersionString .= 'A';
                    break;
                case 'beta':
                    $sVersionString .= 'B';
                    break;
                case 'rc':
                    $sVersionString .= 'C';
                    break;
                case 'release':
                    $sVersionString .= 'R';
                    break;
            }

            $sNewProjectDir = ROOT_PATH;
            $sRootDir = dirname($sNewProjectDir);
            $sDateToday = date('Ymd');

            $sBuildZipFile = "{$sRootDir}/Builds/CapsheafClient-".($bEncrypt ? 'EC' : 'NC')."-{$sVersion}-{$sDateToday}-{$sGitVersion}-{$sRelease}-{$sArch}.zip";

            $zip = new Zip($bEncrypt);
            $nResult = $zip->open($sBuildZipFile, ZipArchive::CREATE|ZipArchive::OVERWRITE);
            if ($nResult === true) {

                //修改版本号
                $sConfigClientFile = "{$sRootDir}/Components/CapsheafClientLinux_Platform/config/config.client";
                $sConfigClient = file_get_contents($sConfigClientFile);
                $sConfigClient = preg_replace("/ClientVersion=(\S+)/", "ClientVersion={$sVersion}", $sConfigClient);
                file_put_contents($sConfigClientFile, $sConfigClient);


                //$zip->addFromString('test.txt', 'file content goes here');
                //$zip->addFile('data.txt', 'entryname.txt');
                $zip->disableEncrypt();

                //bin目录
                $zip->addFolder("{$sRootDir}/Capsheaf/bin/Interpreter/Linux/{$sArch}/", "{$sRootDir}/Capsheaf/bin/Interpreter/Linux/{$sArch}/", 'bin/php/');
                $zip->addFolder("{$sRootDir}/Capsheaf/bin/CapsheafClient/Drcclient/{$sArch}/", "{$sRootDir}/Capsheaf/bin/CapsheafClient/Drcclient/{$sArch}/", 'bin/drc_client/');
                $zip->addFolder("{$sRootDir}/Capsheaf/bin/CapsheafClient/Scripts/", "{$sRootDir}/Capsheaf/bin/CapsheafClient/Scripts/", 'bin/scripts/');
                $zip->addFolder("{$sRootDir}/Capsheaf/bin/CapsheafClient/Snapshot/{$sArch}/", "{$sRootDir}/Capsheaf/bin/CapsheafClient/Snapshot/{$sArch}/", 'bin/tools/');
                $zip->addFolder("{$sRootDir}/Capsheaf/bin/CapsheafClient/VolCdpHa/{$sArch}/", "{$sRootDir}/Capsheaf/bin/CapsheafClient/VolCdpHa/{$sArch}/", 'bin/volcdpha/');
                $zip->addFolder("{$sRootDir}/Capsheaf/bin/CapsheafClient/Viewer/{$sArch}/", "{$sRootDir}/Capsheaf/bin/CapsheafClient/Viewer/{$sArch}/", 'bin/viewer/');

                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/CapClient", "bin/CapClient");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/config.sh", "bin/config.sh");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/drc_client.sh", "bin/drc_client.sh");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/drc_controller.sh", "bin/drc_controller.sh");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/drc_ha_controller.sh", "bin/drc_ha_controller.sh");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/drc_viewer.sh", "bin/drc_viewer.sh");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/lsb_release", "bin/lsb_release");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/CapsheafClient/php.sh", "bin/php.sh");

                //data目录
                $zip->addFolder("{$sRootDir}/Components/CapsheafClientLinux_Platform/config/", "{$sRootDir}/Components/CapsheafClientLinux_Platform/config/", 'data/drc_client/config/');

                $zip->addEmptyFolder("data/drc_client/task/", ['fs', 'mysql', 'oracle']);
                $zip->addEmptyFolder("data/drc_client/control/", ['fs', 'mysql', 'oracle']);
                $zip->addEmptyDir("data/volcdpha/Log/");
                $zip->addEmptyDir("data/volcdpha/task/");
                $zip->addEmptyDir("data/volcdpha/conf/");
                $zip->addEmptyDir("data/viewer/Log/");

                //doc目录
                $zip->addFolder("{$sRootDir}/Capsheaf/Docs/", "{$sRootDir}/Capsheaf/Docs/", 'doc/');

                //etc目录
                $zip->addFolder("{$sRootDir}/Components/CapsheafClientLinux_Platform/conf/", "{$sRootDir}/Components/CapsheafClientLinux_Platform/conf/", 'etc/conf/');
                $zip->addFolder("{$sRootDir}/Capsheaf/CapsheafClient/Etc/config/", "{$sRootDir}/Capsheaf/CapsheafClient/Etc/config/", 'etc/config/');
                $zip->addFolder("{$sRootDir}/Capsheaf/CapsheafClient/Etc/rc.d/", "{$sRootDir}/Capsheaf/CapsheafClient/Etc/rc.d/", 'etc/rc.d/');

                //lib目录
                if ($bEncrypt) { $zip->enableEncrypt(); }
                $zip->addFolder("{$sRootDir}/Components/CapsheafClientCommon_PhpFsDb/", "{$sRootDir}/Components/CapsheafClientCommon_PhpFsDb/", 'lib/fsdb/v1/');
                $zip->addFolder("{$sRootDir}/Capsheaf/CapsheafClient/", "{$sRootDir}/Capsheaf/", "lib/fsdb/v2/");
                $zip->addFolder("{$sRootDir}/Capsheaf/Vendor/", "{$sRootDir}/Capsheaf/", "lib/fsdb/v2/");
                $zip->addFile("{$sRootDir}/Components/CapsheafClientLinux_BaseFsDb/WATCHDOG.php", "lib/fsdb/WATCHDOG.php");
                $zip->disableEncrypt();

                $zip->addFolder("{$sRootDir}/Capsheaf/bin/CapsheafClient/Shared/{$sArch}/", "{$sRootDir}/Capsheaf/bin/CapsheafClient/Shared/{$sArch}/", 'lib/shared/');

                //tmp目录
                $zip->addEmptyDir("tmp/Runtime/");
                $zip->close();

                return $sBuildZipFile;
            } else {
                throw new RuntimeException("Open zip file:{$sBuildZipFile} failed.");
            }

        } catch (Exception $exception) {
            throw new RuntimeException("zip file failed, " . $exception->getMessage());
        }
    }


    public function check($sArch, $sType, $sIp, $sPasswd)
    {
        return $this->success('env check success');
    }

}