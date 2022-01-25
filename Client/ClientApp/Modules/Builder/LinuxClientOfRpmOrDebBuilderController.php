<?php
/********************************************************************************************
 *             Copy Right (c) 2021 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2021-11-03 17:38:50 CST
 *  Description:     LinuxClientOfRpmOrDebBuilderController.php's function description
 *  Version:         1.0.0.20211103-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2021-11-03 17:38:50 CST initialized the file
 *******************************************************************************************/

namespace CapsheafBuilder\Modules\Builder;

use CapsheafBuilder\Models\Git\Git;
use CapsheafBuilder\Models\Package\Zip;
use CapsheafBuilder\Models\RemoteControl\RemoteControl;
use CapsheafBuilder\Modules\Builder\LinuxClientBuilderController;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class LinuxClientOfRpmOrDebBuilderController extends LinuxClientBuilderController
{

    public function index()
    {
        return $this->success();
    }


    /**
     * @param string $sVersion
     * @param string $sArch
     * @param false $bEncrypt
     * @param string $sRelease
     * @param string $sType
     * @param string $sIp
     * @param string $sPasswd
     */
    public function build($sVersion = '', $sArch = '', $bEncrypt = false, $sRelease = 'alpha', $sType = '', $sIp = '127.0.0.1', $sPasswd = 'jcb410')
    {
        try {
            $this->m_app['log']->info("version:{$sVersion}, encrypt:{$bEncrypt}, release:{$sRelease}, package type:{$sType}, os packer ip:{$sIp}, os packer password:{$sPasswd}");

            if ($bEncrypt){
                $sEncrypt = 'EC';
            } else {
                $sEncrypt = 'NC';
            }

            $sGitVersion = (new Git())->getCurrentVersion('.');

            //deb包的x86_64应为amd64
            if ($sType == 'deb'){
                $sArch = str_replace('x86_64', 'amd64', $sArch);
            }

            $arrArch = explode('-', $sArch);

            //制作zip包
            $sBuildZipFile = $this->buildZip($sGitVersion, $sVersion, $sArch, $bEncrypt, $sRelease);

            $sZipFileName = basename($sBuildZipFile);
            $this->m_app['log']->debug("build zip file success, {$sBuildZipFile}");

            $remoteControl = new RemoteControl($sType, $sIp, $sPasswd);
            //测试连通性
            $sTestRemotePackerShell = 'ls /root';
            $remoteControl->execRemoteShell($sTestRemotePackerShell);


            //上传打包相关文件到打包机
            $remoteControl->uploadFile($sBuildZipFile, "/root");

            if ($sType == 'deb'){
                $sBuildDebShell = dirname(__FILE__) . "/../../Dev/Package/DebBuild/CapsheafClient_Sm_Deb.sh";
                $remoteControl->uploadFile($sBuildDebShell, "/root");
                $this->m_app['log']->debug("upload {$sBuildDebShell} to {$sIp} success.");
                $sRemoteBuildRpmOrDebShellPath = "/root/CapsheafClient_Sm_Deb.sh";

            } else {
                $sBuildRpmShell = dirname(__FILE__) . "/../../Dev/Package/RpmBuild/CapsheafClient_Sm_Rpm.sh";
                $remoteControl->uploadFile($sBuildRpmShell, "/root");
                $this->m_app['log']->debug("upload {$sBuildRpmShell} to {$sIp} success.");
                $sRemoteBuildRpmOrDebShellPath = "/root/CapsheafClient_Sm_Rpm.sh";

                $sBuildRpmSpec = dirname(__FILE__) . "/../../Dev/Package/RpmBuild/CapsheafClient_Sm_Rpm.spec";
                $remoteControl->uploadFile($sBuildRpmSpec, "/root");
            }

            //执行命令控制打包机进行rpm包制作
            $sRemoteShell = "chmod a+x {$sRemoteBuildRpmOrDebShellPath}";
            $sOutput = $remoteControl->execRemoteShell($sRemoteShell);
            $this->m_app['log']->debug("exec command {$sRemoteShell} result: {$sOutput}.");

            $sRemoteShell = "rm -rf /root/capsheaf_build_status/*";
            $remoteControl->execRemoteShell($sRemoteShell);

            $sRemoteControlBuildRpmCommand = "nohup sudo sh {$sRemoteBuildRpmOrDebShellPath} -e {$sEncrypt} -v {$sVersion} -g {$sGitVersion} -r {$sRelease} -a {$arrArch[1]} -p /root/{$sZipFileName} > /root/capsheaf_build.log 2>&1 &";
            $this->m_app['log']->debug("remote control build rpm command: {$sRemoteControlBuildRpmCommand}.");
            $remoteControl->execRemoteShell($sRemoteControlBuildRpmCommand);

            while(true){
                $sCheckRemoteBuildStatusCommand = "ps aux | grep " . basename($sRemoteBuildRpmOrDebShellPath) . "| grep -v grep | awk '{print $2}'";
                $sOutput = $remoteControl->execRemoteShell($sCheckRemoteBuildStatusCommand);
                if (empty($sOutput)){
                    $sCheckRemoteBuildStatusCommand = "ls /root/capsheaf_build_status/succeed > /dev/null 2>&1;[[ $? -eq 0 ]] && cat /root/capsheaf_build_status/succeed";
                    $sRpmOrDebFilePath = $remoteControl->execRemoteShell($sCheckRemoteBuildStatusCommand);
                    if (!isset($sRpmOrDebFilePath) || empty($sRpmOrDebFilePath)){
                        //失败
                        $sCheckRemoteBuildStatusCommand = "ls /root/capsheaf_build_status/failed > /dev/null 2>&1;[[ $? -eq 0 ]] && cat /root/capsheaf_build_status/failed";
                        $sGetBuildFailedInfo = $remoteControl->execRemoteShell($sCheckRemoteBuildStatusCommand);

                        if (empty($sGetBuildFailedInfo)){
                            return $this->error(400, "build {$sType} file failed, remote " . basename($sRemoteBuildRpmOrDebShellPath) . " process unexpected end, Please check the output log /root/capsheaf_build.log");
                        } else {
                            return $this->error(400, "build {$sType} file failed, {$sGetBuildFailedInfo}");
                        }
                    }
                    break;
                }

                sleep(2);
            }

            $sNewProjectDir = ROOT_PATH;
            $sRootDir = dirname($sNewProjectDir);

            //下载相关文件并删除相关打包信息
            $sLocalRpmOrDebFilePath = "{$sRootDir}/Builds/" . basename($sRpmOrDebFilePath);
            $remoteControl->downloadFile($sLocalRpmOrDebFilePath, $sRpmOrDebFilePath);
            $this->m_app['log']->debug("download rpm file {$sLocalRpmOrDebFilePath} success.");

            $remoteControl->execRemoteShell("rm -rf /root/CapsheafClient*");
            $remoteControl->execRemoteShell("rm -rf /root/capsheaf_build_status/*");

            return $this->success($sLocalRpmOrDebFilePath);
        } catch (Exception $exception) {
            return $this->error(400, "build {$sType} file failed, " . $exception->getMessage());
        }
    }


    /**
     * @param $sArch string EG: KYLIN_DESKTOP_V10_INTEL-x86_64
     * @param $sType
     * @param $sIp
     * @param $sPasswd
     * @return \Capsheaf\Foundation\Response\Http\JsonResponse
     */
    public function check($sArch, $sType, $sIp, $sPasswd)
    {
        try {
            $remoteControl = new RemoteControl($sType, $sIp, $sPasswd);
            //检测系统版本
            $arrArch = explode('-', $sArch);
            $sCpu    = $arrArch[1];

            $arrOsInfo  = explode('_', $arrArch[0]);

            if (count($arrOsInfo) == 4){
                $sOsSystemName    = $arrOsInfo[0];
                $sOsModel   = $arrOsInfo[1];
                $sOsSystemVersion = $arrOsInfo[2];
            } else {
                $sOsSystemName    = $arrOsInfo[0];
                $sOsModel   = '';
                $sOsSystemVersion = $arrOsInfo[1];
            }

            if ($sOsModel == 'DESKTOP' && $sType != 'deb'){
                throw new RuntimeException('The arch option conflicts with the type option.');
            }

            $sPackerOsSystemName    = $remoteControl->execRemoteShell("lsb_release -is", false);
            $sPackerOsSystemVersion = $remoteControl->execRemoteShell("lsb_release -rs | cut -d \".\" -f1", false);

            if (empty($sPackerOsSystemName) || empty($sPackerOsSystemVersion)){
                throw new RuntimeException('not install lsb_release.');
            }
            $bOsNameAndVersionCheck = $this->osNameAndVersionCheck($sPackerOsSystemName, $sPackerOsSystemVersion, $sOsSystemName, $sOsSystemVersion);
            if (!$bOsNameAndVersionCheck){
                throw new RuntimeException('check os system name or os system version failed.');
            }

            $sOsInfo = $remoteControl->execRemoteShell("uname -a && cat /proc/version", false);

            //有的操作系统暂无手段通过shell判断是桌面版还是服务器版
//            $bOsModelCheck = $this->osModelCheck($sOsInfo, $sOsModel);
//            if (!$bOsModelCheck){
//                throw new RuntimeException('check os model failed.');
//            }

            $bCpuCheck = $this->cpuCheck($sOsInfo, $sCpu);
            if (!$bCpuCheck){
                throw new RuntimeException('check cpu failed.');
            }

            return $this->success('env check success');
        } catch (Exception $exception){
            return $this->error(400, 'env check failed: ' . $exception->getMessage());
        }

    }


    private function osNameAndVersionCheck($sPackerOsSystemName, $sPackerOsSystemVersion, $sOsSystemName, $sOsSystemVersion)
    {
        $sMapPackerOsSystemName    = '';
        $sMapPackerOsSystemVersion = '';

        $sPackerOsSystemName = strtoupper($sPackerOsSystemName);
        $sPackerOsSystemVersion = strtoupper($sPackerOsSystemVersion);
        switch ($sPackerOsSystemName){
            case "CENTOS":
            case "RHEL":
            case "REDHATENTERPRISESERVER":
            case "ORACLESERVER":
                $sMapPackerOsSystemName = 'RHEL';
                if (in_array($sPackerOsSystemVersion, ['6', '7'])){
                    $sMapPackerOsSystemVersion = 'V6';
                }
                break;
            case "KYLIN":
                $sMapPackerOsSystemName = 'KYLIN';
                $sMapPackerOsSystemVersion = $sPackerOsSystemVersion;
                break;
        }

        if ($sMapPackerOsSystemName == $sOsSystemName && $sMapPackerOsSystemVersion == $sOsSystemVersion){
            return true;
        } else {
            return false;
        }
    }


    private function osModelCheck($sOsInfo, $sOsModel)
    {
        //RHEL不需要检测
        if (empty($sOsModel)){
            return true;
        }

        if ($sOsModel == 'DESKTOP'){
            $sOsModelMap = 'Ubuntu';
        } else {
            $sOsModelMap = 'Red Hat';
        }

        if (stristr($sOsInfo, $sOsModelMap) === false){
            return false;
        } else {
            return true;
        }
    }

    private function cpuCheck($sOsInfo, $sCpu)
    {
        switch ($sCpu){
            case 'arm64':
                $sMapCpu = 'aarch64';
                break;
            default:
                $sMapCpu = $sCpu;
        }

        if (stristr($sOsInfo, $sMapCpu) === false){
            return false;
        }

        return true;
    }

}