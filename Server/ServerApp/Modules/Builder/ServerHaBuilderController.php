<?php
/******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 14:05:17 CST
 *  Description:     ServerHaBuilderController.php's function description
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

class ServerHaBuilderController extends BaseController
{

    public function index()
    {
        return $this->success();
    }


    /**
     * @param string $sVersion EG: 2.4.0
     * @param string $sRelease EG: alpha|beta|null
     * @return \Capsheaf\Foundation\Response\Http\JsonResponse
     */
    public function build($sVersion = '', $sRelease = 'alpha')
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
            $sBuildZipFile = "{$sRootDir}/Builds/CapsheafServer-HA-{$sVersion}-{$sDateToday}-{$sGitVersion}-{$sRelease}-x86_64.zip";

            $zip = new Zip();
            $nResult = $zip->open($sBuildZipFile, ZipArchive::CREATE|ZipArchive::OVERWRITE);
            if ($nResult === true) {
                //$zip->addFromString('test.txt', 'file content goes here');
                //$zip->addFile('data.txt', 'entryname.txt');
                $zip->addFolder("{$sRootDir}/Components/CapsheafServer_Ha/", "{$sRootDir}/Components/CapsheafServer_Ha/", 'CapsheafServer-HA/', [
                    "{$sRootDir}/Components/CapsheafServer/version"
                ]);

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