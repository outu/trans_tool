<?php
/******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 14:05:17 CST
 *  Description:     Zip.php's function description
 *  Version:         1.0.0.20180314-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-14 14:05:17 CST initialized the file
 ******************************************************************************/

namespace CapsheafBuilder\Models\Package;

use Capsheaf\FileSystem\FileSystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Zip extends ZipArchive
{

    protected $m_bEncryptPhpFile;
    protected $m_sEncryptExePath;

    public function __construct($bEncryptPhpFile = false)
    {
        $this->m_bEncryptPhpFile = $bEncryptPhpFile;
        if ($bEncryptPhpFile) {
            $this->m_sEncryptExePath = ROOT_PATH.'Bin/Interpreter/Src/php-5.4.45/ext/code_obfus/tools/code_obfus_encode_file'.(windows_os() ? '.exe' : '');
        }
    }


    public function enableEncrypt()
    {
        $this->m_bEncryptPhpFile = true;
    }


    public function disableEncrypt()
    {
        $this->m_bEncryptPhpFile = false;
    }


    /**
     * 添加整个文件夹到压缩包中，最终结果是：$sLocatedTo路径+（$sFolderPath中剔除$sStripPrefixString这些前缀）
     * @param string $sFolderPath 要添加的文件夹的绝对路径，注意：带后缀/， 如："{$sRootDir}/Capsheaf/Vendor/"
     * @param string $sStripPrefixString 要从前面绝对路径中剔除的前面固定的绝对路径的部分，以保证压缩包中只留下相对路径，注意：带后缀/，如："{$sRootDir}/"
     * @param string $sLocatedTo 将前面构造的相对路径，重新定位到指定的某个相对目录中，注意：带后缀/，如："CapsheafServer/Capsheaf-pf.ui.fsdb/"
     * @param array $arrIgnored 要忽略的文件路径，注意是要忽略的文件的绝对路径，如："{$sRootDir}/Components/CapsheafServer/version"
     */
    public function addFolder($sFolderPath, $sStripPrefixString = '', $sLocatedTo = '', $arrIgnored = [])
    {
        $nPrefixLength = strlen($sStripPrefixString);
        $dir_iterator = new RecursiveDirectoryIterator($sFolderPath);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $sLocalPathFileName = $file->getFilename();
            if ($sLocalPathFileName == '.' || $sLocalPathFileName == '..'){
                continue;
            }

            //注意目录也没有后缀/
            $sLocalPath = str_replace('\\', '/', $file->getPathname());
            foreach ($arrIgnored as $sIgnore){
                if (strpos($sLocalPath, $sIgnore) === 0){
                    continue 2;
                }
            }

            $sStoredAs = $sLocatedTo.substr($sLocalPath, $nPrefixLength);
            if ($file->isFile()) {
                $this->addFile($sLocalPath, $sStoredAs);
            } else {
                $this->addEmptyDir($sStoredAs);
            }
        }
    }


    public function addFile($sLocalPath, $sStoredAs = null, $nStart = NULL, $nLength = NULL)
    {
        $nRetCode = 1;

        $sLocalPathToAdd = $sLocalPath;
        if ($this->m_bEncryptPhpFile) {
            $info = new \SplFileInfo($sLocalPath);
            $sExt = $info->getExtension();
            if ($sExt == 'php'){
                $arrOutput = [];
                $sEncryptedPath = tempnam(sys_get_temp_dir(), 'ENC');
                if (windows_os()){
                    $sEncryptedPath = str_replace("\\", '/', $sEncryptedPath);
                }
                exec("{$this->m_sEncryptExePath} \"{$sLocalPath}\" \"{$sEncryptedPath}\"", $arrOutput, $nRetCode);
                if ($nRetCode == 0) {
                    $sLocalPathToAdd = $sEncryptedPath;
                }
            }
        }

        parent::addFile($sLocalPathToAdd, $sStoredAs);
    }


    public function patchFolder($sSrcPatchFolderPath, $sFolderPath, $sStripPrefixString = '', $sLocatedTo = '', $arrIgnored = [])
    {
        $nPrefixLength = strlen($sStripPrefixString);
        $dir_iterator = new RecursiveDirectoryIterator($sFolderPath);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $sLocalPathFileName = $file->getFilename();
            if ($sLocalPathFileName == '.' || $sLocalPathFileName == '..'){
                continue;
            }

            $sLocalPath = str_replace('\\', '/', $file->getPathname());
            if (in_array($sLocalPath, $arrIgnored)){
                continue;
            }

            $sStoredAs = $sLocatedTo.substr($sLocalPath, $nPrefixLength);
            if ($file->isFile()) {
                $this->addFile($sLocalPath, $sStoredAs);
            } else {
                $this->addEmptyDir($sStoredAs);
            }
        }
    }


    public function addEmptyFolder($sFolderPath, $arrEmptyDir)
    {
        foreach ($arrEmptyDir as $sEmptyDir){
            $sEmptyFolder = "{$sFolderPath}{$sEmptyDir}";
            $this->addEmptyDir($sEmptyFolder);
        }
    }

}