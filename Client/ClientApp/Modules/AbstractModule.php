<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 11:47:58 CST
 *  Description:     AbstractModule.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 11:47:58 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Modules;

use Capsheaf\Application\Application;
use Capsheaf\Process\Mutex\FileMutex;
use Capsheaf\Utils\Types\Str;
use ClientApp\Foundations\Repository\BaseConfig;

abstract class AbstractModule
{
    /**
     * 应用程序版本
     */
    const VERSION = '1.0.1.20170913';

    /**
     * @var Application
     */
    protected $m_app;

    /**
     * @var BaseConfig
     */
    protected $m_config;

    protected $m_instanceMutex;

    /**
     * 模块名称，取值为_MODULE几个常量字符串，形式为：XXX_MODULE|XXX_XXX_MODULE等
     * @var string
     */
    protected $m_sModuleName = 'DUMMY_MODULE';


    public function __construct(Application $app)
    {
        $this->m_app                = $app;
        $this->m_bNeedExit          = false;

        $this->initCoreComponents();

        $this->m_app['log']->info("Current module is {$this->m_sModuleName}.");
        $this->m_app['log']->debug('Environment variables:', $_SERVER);

        //重新绑定到该实例
        $this->m_app->instance('module', $this);
        $this->m_app->addAlias('module', 'ClientApp\Modules\AbstractModule');
        $sModule = $this->getModuleName(false, true);
        $this->m_app->addAlias('module', "ClientApp\Modules\{$sModule}\{$sModule}Module");
        $this->loadConfig();

        $this->m_instanceMutex      = new FileMutex($this->m_sModuleName);
    }


    public function loadConfig()
    {
        $sConfigFilePath = APP_PATH.'Etc/config.json';
        $this->m_config = $this->m_app->makeWith('config', ['sConfigFilePath' => $sConfigFilePath]);
        $this->m_app['log']->debug('Loaded final config:', $this->m_app['config']->all());
    }


    /**
     * 获取模块名称，注意取最后一个'_'前的大写文本，可以转换为CamelCase
     * @param bool $bWithPostfix
     * @param bool $bCamelCase
     * @return string
     */
    public function getModuleName($bWithPostfix = true, $bCamelCase = false)
    {
        if ($bWithPostfix){
            $sModule = $this->m_sModuleName;
        } else {
            $sModule = substr($this->m_sModuleName, 0, -(strlen('_MODULE')));
        }

        return $bCamelCase ? $sModule : Str::camelCase($sModule);
    }



    public function initCoreComponents()
    {
        //重置日志Channel名称为当前模块名称
        $defaultLogger = $this->m_app['log']->getLogger('client');
        if ($defaultLogger){
            $defaultLogger->setChannelName(strtolower($this->getModuleName(false)));
        }
    }

    /**
     * 模块初始化操作
     * @return \ClientApp\Modules\AbstractModule
     */
    public function init()
    {
        if (!$this->m_instanceMutex->lock()) {
            echo "ONE INSTANCE ONLY.";
            exit(1);
        }

        return $this;
    }


    /**
     * 模块正式执行, 由子类自行实现，可能执行一次结束，可能循环执行
     */
    abstract public function run();


    public function terminate()
    {
        if (!empty($this->m_instanceMutex)){
            $this->m_instanceMutex->unlock();
            unset($this->m_instanceMutex);
        }
    }


    public function __destruct()
    {
        $this->terminate();
    }
}