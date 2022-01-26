<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-20 14:10:04 CST
 *  Description:     CoreServiceProvider.php's function description
 *  Version:         1.0.0.20180420-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-20 14:10:04 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Foundations\Bootstrap;

use Capsheaf\Application\Application;
use Capsheaf\FileSystem\FileSystem;
use Capsheaf\Foundation\Router\Router;
use Capsheaf\Log\Formatter\LineFormatter;
use Capsheaf\Log\Logger;
use Capsheaf\Log\LoggersFactory;
use Capsheaf\Log\LogHandler\StreamHandler;
use RuntimeException;

class CoreServiceProvider
{

    protected $m_app;

    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    public function bindServices()
    {
        $this->bindLogger();
        $this->bindConfig();
        $this->bindDatabase();

        return $this;
    }


    public function initServices()
    {
        $this->initConfig();
        $this->initKernel();
        $this->initDatabase();
    }


    public function checkPrerequisite()
    {
        if (version_compare(PHP_VERSION_ID, '5.4.0', '<')){
            throw new RuntimeException('Php version must greater than 5.4.0, Current '.PHP_VERSION);
        }

        if (!FileSystem::isWritable(RUNTIME_PATH)){
            throw new RuntimeException('Runtime path '.RUNTIME_PATH.' is not writable.');
        }
    }


    /**
     * @throws \Capsheaf\Application\ContainerException
     */
    public function bindConfig()
    {
        $this->m_app->singleton(
            'config',
            'ServerApp\Foundations\Repository\BaseConfig'
        );
    }


    /**
     * 实例化和注册Logger组件
     */
    protected function bindLogger()
    {
        $loggersFactory = new LoggersFactory();
        $logHandlerStdout = new StreamHandler('php://stdout');
        $logHandlerFile = new StreamHandler(RUNTIME_PATH.'ServerApp.log');
        $logFormatter = new LineFormatter();
        $logHandlerStdout->setFormatter($logFormatter);
        $logHandlerFile->setFormatter($logFormatter);
        $logger = new Logger('server', [$logHandlerStdout, $logHandlerFile]);
        $loggersFactory->addLogger($logger);

        $this->m_app->instance('log', $loggersFactory, ['Capsheaf\Log\LoggersFactory']);
    }


    protected function bindDatabase()
    {
        $this->m_app->singleton(
            'db',
            'Capsheaf\Database\DatabaseManager'
        );
    }


    protected function initConfig()
    {
        $sConfigFilePath = APP_PATH.'Etc/config.json';

        $this->m_app->makeWith('config', ['sConfigFilePath' => $sConfigFilePath]);
        app('log')->debug('Loaded final config:', $this->m_app['config']->all());

        return $this;
    }


    protected function initDatabase()
    {
        $this->m_app->make('db');
        app('log')->debug("Database component initialized.");

        return $this;
    }


    protected function initKernel()
    {
        $sControllerNameSpace = 'ServerApp\Modules\\';
        $this->m_app->instance('router', new Router($this->m_app, $sControllerNameSpace), 'Capsheaf\Foundation\Router\Router');
        $this->m_app->singleton('kernel', 'Capsheaf\Foundation\Kernel\ServerKernel');

        return $this;
    }

}
