<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:03 CST
 *  Description:     FileSystem.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:03 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\FileSystem;

use Capsheaf\Support\Traits\MetaTrait;
use Exception;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystem
{

    use MetaTrait;


    /**
     * 更改当前的umask，注意涉及更改文件/文件夹权限的时候，可能需要设置这个,多线程不建议使用
     * @param int $nUMask 要设置的umask，默认全部放开
     * @return int 返回历史umask
     */
    public static function umask($nUMask = 0)
    {
        return umask($nUMask);
    }


    /**
     * 若要获取及时的文件信息，注意可能要清理缓存
     * 受影响的函数包括 stat()， lstat()， file_exists()， is_writable()， is_readable()， is_executable()， is_file()， is_dir()，
     * is_link()， filectime()， fileatime()， filemtime()， fileinode()， filegroup()， fileowner()， filesize()， filetype() 和 fileperms()。
     * @param $sPath
     */
    public static function clearStatCache($sPath)
    {
        return clearstatcache(true, $sPath);
    }


    /**
     * 判断文件或者目录是否存在，注意会被缓存信息，仅仅unlink() 才会自动清除该缓存
     * @param $sPath
     * @return bool
     */
    public static function exists($sPath)
    {
        //必须注意的是，对于不存在的文件，PHP 并不会缓存其信息。所以如果调用 file_exists() 来检查不存在的文件，在该文件没有被创建之前，它都会返回 FALSE。
        //如果该文件被创建了，就算以后被删除，它都会返回 TRUE; 函数 unlink() 会自动清除该缓存.
        return file_exists($sPath);
    }


    /**
     * 获取文件内容
     * @param $sPath
     * @param bool $bLock
     * @return string|false
     * @throws FileNotFoundException
     */
    public static function get($sPath, $bLock = false)
    {
        if (self::isFile($sPath)) {
            return $bLock ? self::sharedGet($sPath) : file_get_contents($sPath);
        }

        throw new FileNotFoundException("File does not exists at path {$sPath}");
    }


    /**
     * 已共享读锁打开并读取文件的内容
     * @param $sPath
     * @return string
     * @throws FileReadException
     */
    public static function sharedGet($sPath)
    {
        $sContents = '';

        $hHandle = fopen($sPath, 'rb');
        if ($hHandle) {
            try {
                if (flock($hHandle, LOCK_SH)) {
                    //调用filesize时获取准确的值需要清除缓存，这里也必须清理缓存
                    clearstatcache(true, $sPath);
                    $sContents = fread($hHandle, self::size($sPath) ?: 1);
                    flock($hHandle, LOCK_UN);
                }
            } catch (Exception $exception){
                fclose($hHandle);
                throw new FileReadException("Get the contents of file:{$sPath} failed:{$exception->getMessage()}");
            }

            fclose($hHandle);
        }

        return $sContents;
    }


    /**
     * 获取文件大小，注意会被缓存信息
     * @param $sPath
     * @return int
     */
    public static function size($sPath)
    {
        return filesize($sPath);
    }


    /**
     * 获取文件的类型。
     * @param string $sPath 文件/文件夹路径
     * @return string|boolean 可能的值有 fifo，char，dir，block，link，file 和unknown,FALSE。
     */
    public static function type($sPath)
    {
        return filetype($sPath);
    }


    /**
     * 获取实际文件的MIME信息，Windows下使用需要注意开启php_fileinfo.dll扩展
     * @param $sPath
     * @return mixed
     */
    public static function mimeType($sPath)
    {
        return finfo_file(finfo_open(FILEINFO_MIME), $sPath);
    }


    /**
     * 获取文件的最后修改时间，注意会被缓存信息
     * @param $sPath
     * @return bool|int 返回时间戳
     */
    public static function lastModified($sPath)
    {
        return filemtime($sPath);
    }


    /**
     * 判断文件/文件夹是否存在并可读，注意会被缓存信息
     * @param $sPath
     * @return bool
     */
    public static function isReadable($sPath)
    {
        return is_readable($sPath);
    }


    /**
     * 判断文件/文件夹是否存在并可写，注意会被缓存信息
     * @param $sPath
     * @return bool
     */
    public static function isWritable($sPath)
    {
        return is_writable($sPath);
    }


    /**
     * 判断是否是文件，注意会被缓存信息
     * @param $sPath
     * @return bool 如果文件存在且为正常的文件则返回TRUE，否则返回FALSE(包含文件夹和没有x权限的情况)。
     */
    public static function isFile($sPath)
    {
        return is_file($sPath);
    }


    /**
     * 判断是否是文件夹，注意会被缓存信息
     * @param string $sPath
     * @return bool 如果文件名存在并且为目录则返回 TRUE
     */
    public static function isDirectory($sPath)
    {
        return is_dir($sPath);
    }


    /**
     * 设置或者获取文件/文件夹的权限
     * @param string $sPath 路径
     * @param null|int $nMode 八进制数字，如: 0755
     * @return bool|string 返回设置成功或失败，若未指定nMode则返回字符串表示的权限，如('0755')
     */
    public static function chmod($sPath, $nMode = null)
    {
        if ($nMode) {
            return chmod($sPath, $nMode);
        }

        return self::getPermission($sPath);
    }


    /**
     * 获取UNIX系统的文件/文件夹权限
     * @param $sPath
     * @return bool|string 返回八进制的字符串如'0755'
     */
    public static function getPermission($sPath)
    {
        return substr(sprintf('%o', fileperms($sPath)), -4);
    }


    /**
     * 获取UNIX系统的文件/文件夹详细权限
     * @param $sPath
     * @return string 返回字符串如'-rw-r--r--'
     */
    public static function getPermissionFull($sPath)
    {
        $nPerms = fileperms($sPath);

        switch ($nPerms & 0xF000) {
            case 0xC000: // socket
                $sInfo = 's';
                break;
            case 0xA000: // symbolic link
                $sInfo = 'l';
                break;
            case 0x8000: // regular
                $sInfo = 'r';
                break;
            case 0x6000: // block special
                $sInfo = 'b';
                break;
            case 0x4000: // directory
                $sInfo = 'd';
                break;
            case 0x2000: // character special
                $sInfo = 'c';
                break;
            case 0x1000: // FIFO pipe
                $sInfo = 'p';
                break;
            default: // unknown
                $sInfo = 'u';
        }

        // Owner
        $sInfo .= (($nPerms & 0x0100) ? 'r' : '-');
        $sInfo .= (($nPerms & 0x0080) ? 'w' : '-');
        $sInfo .= (($nPerms & 0x0040) ?
            (($nPerms & 0x0800) ? 's' : 'x') :
            (($nPerms & 0x0800) ? 'S' : '-'));

        // Group
        $sInfo .= (($nPerms & 0x0020) ? 'r' : '-');
        $sInfo .= (($nPerms & 0x0010) ? 'w' : '-');
        $sInfo .= (($nPerms & 0x0008) ?
            (($nPerms & 0x0400) ? 's' : 'x') :
            (($nPerms & 0x0400) ? 'S' : '-'));

        // World
        $sInfo .= (($nPerms & 0x0004) ? 'r' : '-');
        $sInfo .= (($nPerms & 0x0002) ? 'w' : '-');
        $sInfo .= (($nPerms & 0x0001) ?
            (($nPerms & 0x0200) ? 't' : 'x') :
            (($nPerms & 0x0200) ? 'T' : '-'));

        return $sInfo;
    }


    /**
     * 拷贝文件或者文件夹到指定的目的文件或者文件夹路径，注意某些环境下最终文件路径长度超过255会存在问题
     * @param string $sFrom 源文件（夹）路径，直接拷贝内部文件或者文件夹，不包括这个目录
     * @param string $sTo 目的文件（夹）路径
     * @return bool
     */
    public static function copy($sFrom, $sTo)
    {
        $nToLength = strlen($sTo);
        if ($nToLength == 0){
            throw new InvalidArgumentException("Empty path copy to.");
        }

        $bToIsDir = false;
        if ($sTo[$nToLength - 1] == '/' || $sTo[$nToLength - 1] == '\\'){
            $sTo = self::unifyDirPath($sTo, false);
            $bToIsDir = true;
            if (!is_dir($sTo)){
                self::makeDirectory($sTo, 0777, true, true);
            }
        }

        if (is_dir($sFrom)){
            foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sFrom, RecursiveDirectoryIterator::SKIP_DOTS|RecursiveDirectoryIterator::UNIX_PATHS|RecursiveDirectoryIterator::FOLLOW_SYMLINKS), RecursiveIteratorIterator::SELF_FIRST) as $it)
            {
                if ($it->isDir()){
                    self::makeDirectory($sTo.$iterator->getSubPathName(), 0777, true, true);
                } else {
                    //PHP5，255 LIMIT
                    copy($it, $sTo.$iterator->getSubPathName());
                }
            }

            return true;
        } else {
            if ($bToIsDir){
                self::makeDirectory($sTo, 0777, true, true);
            } else {
                if (!is_dir(dirname($sTo))){
                    self::makeDirectory(dirname($sTo), 0777, true, true);
                }
            }

            return copy($sFrom, $sTo);
        }
    }


    /**
     * 创建文件或者文件夹的软链接，即操作一边对另一边有影响
     * @param $sFromSource
     * @param $sToLink
     * @return bool
     */
    public static function link($sFromSource, $sToLink)
    {
        if (!windows_os()){
            //WindowsVista和Server 2008后才支持该函数
            return symlink($sFromSource, $sToLink);
        }

        $sMode = self::isDirectory($sFromSource) ? 'J' : 'H';
        exec("mklink /{$sMode} \"{$sToLink}\" \"{$sFromSource}\"");
    }


    public static function filename($sPath)
    {
        return pathinfo($sPath, PATHINFO_FILENAME);
    }


    public static function extension($sPath)
    {
        return pathinfo($sPath, PATHINFO_EXTENSION);
    }


    public static function dirname($sPath)
    {
        return pathinfo($sPath, PATHINFO_DIRNAME);
    }


    /**
     * 删除单个或者多个文件，注意不包括文件夹
     * @param string|array $paths 可以传入多个字符串形式的参数表示单个或者多个文件，或者直接指定为数组,eg：delete('a.txt', 'b.xls'); delete(['a.txt', 'b.xls']);
     * @return bool
     */
    public static function delete($paths)
    {
        $arrPaths = is_array($paths) ? $paths : func_get_args();

        $bSuccess = true;
        foreach ($arrPaths as $sPath) {
            try {
                //和 Unix C 的 unlink() 函数相似。 发生错误时会产生一个 E_WARNING 级别的错误。 这里将错误转换为了ErrorException
                if (!@unlink($sPath)) {
                    $bSuccess = false;
                }
            } catch (Exception $exception) {
                $bSuccess = false;
            }
        }

        return $bSuccess;
    }


    /**
     * 根据指定的Glob模式删除文件夹下的文件
     * @param string $sPattern BLOB模式，如："some/dir/*.txt"
     * @return array
     */
    public static function deleteByPattern($sPattern)
    {
        //array_map — 为数组的每个元素应用回调函数，并返回对每个元素执行操作后的结果的新数组，第一个参数可以指定为回调函数
        return array_map(
            function ($sFile) {
                try {
                    if (!@unlink($sFile)) {
                        return false;
                    }
                } catch (Exception $exception) {
                    return false;
                }

                return true;
            }, glob($sPattern)
        );
    }


    /**
     * 创建一个临时文件
     * @param string $sDir 如果 PHP 不能在指定的 dir 参数中创建文件，则退回到系统默认值。 在 NTFS 文件系统中，同样的情况也发生在 dir 中文件数超过 65534 个的时候。
     * @param string $sPrefix
     * @return bool|string
     */
    public static function tempFile($sDir = '', $sPrefix = 'tmp')
    {
        return tempnam($sDir, $sPrefix);
    }


    /**
     * 清空一个文件内容
     * @param $sPath
     * @return bool
     */
    public static function emptyFile($sPath)
    {
        $hFile = fopen($sPath, "w");
        if ($hFile){
            return fclose($hFile);
        }
        return false;
    }


    /**
     * 根据Glob模式，寻找与模式匹配的文件/文件夹路径集合，注意默认不会获取到隐藏.文件
     * @param string $sPattern <br/>
     * 参考:<br/>
     *  'my/dir/*.[cC][sS][vV]' 忽略大小写<br/>
     *  '{,.}*'模式，和GLOB_BRACE Flag 同时获取隐藏.文件<br/>
     *  'my/*_/dir/_*.php'模式可以使用多个星号
     *  '{includes/*.php,core/*.php}'模式，和GLOB_BRACE Flag 同时获取多个文件夹下的文件<br/>
     * @param int $nFlags 参看 http://php.net/manual/zh/function.glob.php<br/>
     * 如:<br/>
     * GLOB_NOSORT - 按照文件在目录中出现的原始顺序返回（不排序）<br/>
     * GLOB_ONLYDIR - 仅返回与模式匹配的目录项<br/>
     * GLOB_BRACE - 允许模式中使用类似正则表达式的中括号<br/>
     * @return array|boolean 返回一个包含有匹配文件／目录的数组，注意返回的每一项元素为绝对路径。如果出错返回FALSE。
     */
    public static function glob($sPattern, $nFlags = 0)
    {
        return glob($sPattern, $nFlags);
    }


    /**
     * 重命名文件,若目的地文件存在则覆盖，注意文件夹操作尽量不要使用这个函数
     * @param string $sFrom
     * @param string $sTo
     * @return bool
     */
    public static function move($sFrom, $sTo)
    {
        return @rename($sFrom, $sTo);
    }


    /**
     * 获取当前文件夹下的所有文件，要求文件都是正常文件类型
     * @param string $sDirectoryPath 文件夹，不带目录分隔符
     * @return array 注意返回的目录文件只有一层，不包含文件夹
     */
    public static function getFiles($sDirectoryPath)
    {
        $arrGlobs = glob($sDirectoryPath.DIRECTORY_SEPARATOR.'*');

        if ($arrGlobs === false) {
            return [];
        }

        return array_filter(
            $arrGlobs, function ($sFilePath) {
                return filetype($sFilePath) == 'file';
            }
        );
    }


    /**
     * 递归获取文件夹下的全部文件（包含子文件夹）
     * @param string $sDirectoryPath
     * @param string $sExt 筛选后缀，对于如php，.php，应该使用.php才能正确获取对应后缀的文件名称
     * @return array 注意返回多维数组的形式
     */
    public static function getAllFiles($sDirectoryPath, $sExt = null)
    {
        $arrResult = [];

        $arrSubItems = array_diff(scandir($sDirectoryPath), ['.', '..']);
        foreach ($arrSubItems as $sSubItem) {
            if (!in_array($sSubItem, ['.', '..'])) {
                if (is_dir($sDirectoryPath.DIRECTORY_SEPARATOR.$sSubItem)) {
                    $arrResult[$sSubItem] = self::getAllFiles($sDirectoryPath.DIRECTORY_SEPARATOR.$sSubItem, $sExt);
                } else {
                    //要是指定了后缀，并且后缀不是指定的后缀则忽略该记录
                    if (!is_null($sExt) && substr($sSubItem, -(strlen($sExt))) !== $sExt){
                        continue;
                    }
                    $arrResult[] = $sSubItem;
                }
            }
        }

        return $arrResult;
    }


    /**
     * 创建一个目录
     * @param string $sPath
     * @param int $nMode 八进制模式,注意这个权限需要设置Umask
     * @param bool $bRecursive 是否递归创建，默认为true
     * @param bool $bForce 若为true则遇到权限问题或者目录已经存在，则不关注引发的异常；为false则在遇到上述问题时会抛出异常
     * @return bool
     */
    public static function makeDirectory($sPath, $nMode = 0777, $bRecursive = true, $bForce = true)
    {
        if ($bForce) {
            return @mkdir($sPath, $nMode, $bRecursive);
        }

        return mkdir($sPath, $nMode, $bRecursive);
    }


    /**
     * 递归清空一个文件夹，注意不能指定多个
     * @param string $sPath
     * @param bool $bKeepSelf 清空完成后，最终是否保留这个文件夹
     * @return bool
     */
    public static function deleteDirectory($sPath, $bKeepSelf = false)
    {
        if (!self::isDirectory($sPath)) {
            return false;
        }

        //也可以使用FilesystemIterator
        $arrSubItems = array_diff(scandir($sPath), ['.', '..']);
        foreach ($arrSubItems as $sSubItem) {
            is_dir($sPath.DIRECTORY_SEPARATOR.$sSubItem)
                ? self::deleteDirectory($sPath.DIRECTORY_SEPARATOR.$sSubItem)
                : unlink($sPath.DIRECTORY_SEPARATOR.$sSubItem);
        }

        if (!$bKeepSelf) {
            @rmdir($sPath);
        }

        return true;
    }


    /**
     * 系统命令的方式删除强制文件夹
     * @param $sPath
     * @return bool
     */
    public static function deleteDirectoryCmd($sPath)
    {
        if (windows_os()) {
            $sCmd = sprintf("rd /s /q %s", escapeshellarg($sPath));
        } else {
            $sCmd = sprintf("rm -rf %s", escapeshellarg($sPath));
        }

        //注意windows的cmd进程
        $sLastLine = exec($sCmd);
        return empty($sLastLine);
    }


    /**
     * 重命名文件夹（不包括文件）
     * @param string $sFrom
     * @param string $sTo
     * @param bool $bForce 是否强制覆盖已经存在的若不为空的文件夹（先删除目的文件夹）
     * @return bool
     */
    public static function moveDirectory($sFrom, $sTo, $bForce = false)
    {
        //处理目的文件夹不为空报Directory not empty的情况
        if ($bForce && self::isDirectory($sTo)) {
            if (!self::deleteDirectory($sTo)) {
                return false;
            }
        }

        return @rename($sFrom, $sTo) == true;
    }


    /**
     * 字节自动转换为其它合适的或者手动指定的单位和精度
     * @param int $nBytes 字节
     * @param string $sUnit 强制使用的单位，B,KB,MB,GB,TB,PB,EB,ZB,YB
     * @param int $nDecimals 指定精度
     * @return string 返回转换后的字符串如:'1.23 GB'，注意单位之前有一个英文空格
     */
    public static function byteFormat($nBytes, $sUnit = '', $nDecimals = 2)
    {
        $arrUnits = ['B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
        $nValue = 0;

        if ($nBytes > 0) {
            //自动计算合适的单位
            if (!array_key_exists($sUnit, $arrUnits)) {
                $nPow = floor(log($nBytes, 1024));
                $sUnit = array_search($nPow, $arrUnits);
            }

            //根据单位计算数字前缀
            $nValue = ($nBytes / pow(1024, $arrUnits[$sUnit]));
        } else {
            return '0 B';
        }

        //格式化输出字符串
        return sprintf('%.'.$nDecimals.'f '.$sUnit, $nValue);
    }


    /**
     * 判断是否是绝对路径，支持Win，Linux，Url的匹配
     * @param string $sPath
     * @return bool
     */
    public static function isAbsolutePath($sPath)
    {
        //如'C:/'，'/root'，'ftp://'，A修饰符表示仅仅以匹配开头的才算匹配，i表示不区分大小写
        return (bool)preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $sPath);
    }


    /**
     * 获取文件所属于的用户的用户名
     * @param string $sPath 文件路径
     * @return string|bool 返回文件所属于的用户名，false表示失败
     */
    public static function getFileOwner($sPath)
    {
        if (!windows_os()) {
            if (file_exists($sPath) && (($nOwnerId = fileowner($sPath)) !== false)) {
                $arrInfo = posix_getpwuid($nOwnerId);

                return $arrInfo ? $arrInfo['name'] : false;
            }
        }

        return false;
    }


    public static function unifyDirPath($sPath, $bToWindows = false)
    {
        $sPath = rtrim($sPath, '/\\');
        if ($bToWindows) {
            $sPath = str_replace('/', '\\', $sPath).'\\';
        } else {
            $sPath = str_replace('\\', '/', $sPath).'/';
        }

        return $sPath;
    }

}
