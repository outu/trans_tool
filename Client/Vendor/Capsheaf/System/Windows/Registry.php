<?php
/******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-22 09:13:00 CST
 *  Description:     Registry.php's function description
 *  Version:         1.0.0.20171222-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-22 09:13:00 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Windows;

use Exception;
use RuntimeException;

/**
 * Registry操作，注意管理员权限
 * @package Capsheaf\System\Windows
 * @see https://msdn.microsoft.com/zh-cn/library/yfdfhz1b
 *
 */
class Registry
{

    /**
     * WshShell 对象
     * @var \COM|null
     */
    protected $m_comShell = null;

    /**
     * 字符串值
     */
    const REG_SZ        = 'REG_SZ';

    /**
     * 二进制值
     */
    const REG_BINARY    = 'REG_BINARY';

    /**
     * DWORD(32位)值
     */
    const REG_DWORD     = 'REG_DWORD';

    /**
     * 可扩充字符串值
     */
    const REG_EXPAND_SZ = 'REG_EXPAND_SZ';

    /**
     * QWORD(64位)值，写入时不支持该类型的注册表键值
     */
    //const REG_QWORD     = 'REG_QWORD';

    /**
     * 多字符串值，写入时不支持该类型的注册表键值
     */
    //const REG_MULTI_SZ  = 'REG_MULTI_SZ';


    public function __construct()
    {
        if (class_exists('\COM')) {
            $this->m_comShell = new \COM('WScript.Shell');
        } else {
            throw new RuntimeException('Failed instantiate COM object. Make sure php_com_dotnet.dll extension is loaded.');
        }
    }


    /**
     * 写入注册表，注意可能需要管理员权限，否则可能失败
     * @param string $sRegistry 如'HKLM\XXX\KEY'或者'HKEY_LOCAL_MACHINE\SOFTWARE\ABChina\CertDllConfig\KEY_NOT_DIR'
     * @param string $sValue 值
     * @param string $sType Registry::REG_系列常量
     * @return void 成功时没有异常，通过是否发生异常来判断注册表是否写入成功
     * @throws Exception
     */
    public function write($sRegistry, $sValue, $sType = self::REG_SZ)
    {
        try {
            //写入成功时RegWrite返回了null，成功时没有异常
            $this->m_comShell->RegWrite($sRegistry, $sValue, $sType);
        } catch (\COM_Exception $exception) {
            throw new Exception("Unable to write registry key:{$sRegistry}. Not a valid key or permission deny.");
        }
    }


    /**
     * 读取注册表键值，一般不需要管理员权限
     * @param string $sRegistry 注册表键名称，注意不能为目录（项），必须指定到key上
     * @return string|int mixed REG_DWORD返回int类型，REG_SZ返回string类型
     * @throws Exception
     */
    public function read($sRegistry)
    {
        try {
            return $this->m_comShell->RegRead($sRegistry);
        } catch (\COM_Exception $exception) {
            throw new Exception("Unable to read registry key:{$sRegistry}. Not a valid key or permission deny.");
        }
    }


    /**
     * 删除【项（目录）/键】，一般需要管理员权限
     * @param string $sRegistry
     * @return bool true表示成功，失败时抛出异常
     * @throws \Exception
     */
    public function delete($sRegistry)
    {
        try {
            return $this->m_comShell->RegDelete($sRegistry);
        } catch (\COM_Exception $exception) {
            throw new Exception("Unable to open registry key:{$sRegistry}. Not a valid key or permission deny.");
        }
    }

}
