<?php
/******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 14:05:17 CST
 *  Description:     ServerBuilderController.php's function description
 *  Version:         1.0.0.20180314-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-14 14:05:17 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Modules\Builder;

use CapsheafBuilder\Models\Git\Git;
use CapsheafBuilder\Models\Package\Zip;
use CapsheafBuilder\Modules\BaseController;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class ServerBuilderController extends BaseController
{

    public function index()
    {
        return $this->success();
    }


    /**
     * @param string $sVersion EG: 2.4.0
     * @param bool $bEncrypt 是否加密php代码
     * @param string $sRelease EG: alpha|beta|null
     * @return \Capsheaf\Foundation\Response\Http\JsonResponse
     */
    public function build($sVersion = '', $bEncrypt = false, $sRelease = 'alpha')
    {
        try {
            if (empty($sVersion)){
                throw new InvalidArgumentException("Empty version number.");
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
            $sGitVersion = (new Git())->getCurrentVersion('.');
            $sBuildZipFile = "{$sRootDir}/Builds/CapsheafServer-".($bEncrypt ? 'EC' : 'NC')."-{$sVersion}-{$sDateToday}-{$sGitVersion}-{$sRelease}-x86_64.zip";

            $zip = new Zip($bEncrypt);
            $nResult = $zip->open($sBuildZipFile, ZipArchive::CREATE|ZipArchive::OVERWRITE);
            if ($nResult === true) {

                //修改版本号, 时间
                $sConfigServerFile = "{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/customize/config.json";

                $sConfigServer   = file_get_contents($sConfigServerFile);
                $arrConfigServer = json_decode($sConfigServer, true);
                $arrConfigServer['product_info']['product_version'] = $sVersion;

                $dateTimeNow  = new \DateTime();
                $sCurrentYear = $dateTimeNow->format('Y');
                $sCopyRight   = "2012-{$sCurrentYear} 成都世纪顶点科技有限公司";
                $arrConfigServer['company_info']['copy_right'] = $sCopyRight;

                file_put_contents($sConfigServerFile, json_encode($arrConfigServer, JSON_UNESCAPED_UNICODE));

                //$zip->addFromString('test.txt', 'file content goes here');
                //$zip->addFile('data.txt', 'entryname.txt');
                $zip->disableEncrypt();
                $zip->addFolder("{$sRootDir}/Components/CapsheafServer/", "{$sRootDir}/Components/", '', [
                    "{$sRootDir}/Components/CapsheafServer/version",
                    "{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot"
                ]);
                if ($bEncrypt) { $zip->enableEncrypt(); }
                $zip->addFolder("{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/", "{$sRootDir}/Components/", '', [
                    "{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/conf/FBRSConfig.php",
                    "{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/chinark/conf/FBRSConfig.php",
                    "{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/chinark/lib/FBRSConfig.php",
                    "{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/SQLServerController/mssqlcontroller/SQ_GlobalError.php"
                ]);
                $zip->disableEncrypt();
                $zip->addFile("{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/conf/FBRSConfig.php", "CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/conf/FBRSConfig.php");
                $zip->addFile("{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/chinark/conf/FBRSConfig.php", "CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/chinark/conf/FBRSConfig.php");
                $zip->addFile("{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/chinark/lib/FBRSConfig.php", "CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/chinark/lib/FBRSConfig.php");
                $zip->addFile("{$sRootDir}/Components/CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/SQLServerController/mssqlcontroller/SQ_GlobalError.php", "CapsheafServer/Capsheaf-pf.ui.fsdb/webroot/SQLServerController/mssqlcontroller/SQ_GlobalError.php");

                if ($bEncrypt) { $zip->enableEncrypt(); }
                $zip->addFolder("{$sRootDir}/Capsheaf/CapsheafServer/", "{$sRootDir}/", "CapsheafServer/Capsheaf-pf.ui.fsdb/");
                $zip->addFolder("{$sRootDir}/Capsheaf/Vendor/", "{$sRootDir}/", "CapsheafServer/Capsheaf-pf.ui.fsdb/");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/GetOsInfo.sh", "CapsheafServer/Capsheaf-pf.ui.fsdb/Capsheaf/Bin/GetOsInfo.sh");
                $zip->addFile("{$sRootDir}/Capsheaf/Bin/Interpreter/Capsheaf", "CapsheafServer/Capsheaf-pf.ui.fsdb/Capsheaf/Bin/Interpreter/Capsheaf");
                $zip->addFolder("{$sRootDir}/Capsheaf/Bin/Interpreter/Linux/RHEL6-x86_64/", "{$sRootDir}/", "CapsheafServer/Capsheaf-pf.ui.fsdb/");
                $zip->addEmptyDir("CapsheafServer/Capsheaf-pf.ui.fsdb/Capsheaf/Runtime/");
                $zip->disableEncrypt();

                $zip->addFromString('CapsheafServer/version', "{$sDateToday}-{$sVersionString}-5-3GX");

                $zip->close();

                return $this->success($sBuildZipFile);
            } else {
                throw new RuntimeException("Open zip file:{$sBuildZipFile} failed.");
            }

        } catch (Exception $exception) {
            return $this->error(404, $exception->getMessage());
        }
    }

}