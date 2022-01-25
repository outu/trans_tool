<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-01-29 10:47:44 CST
 *  Description:     CapsheafModule.php's function description
 *  Version:         1.0.0.20180129-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-01-29 10:47:44 CST initialized the file
 ******************************************************************************/


//////////////////////////////////////////////////////////////////////////////////////////
/// 声明:
/// 本程序源码为成都世纪顶点科技有限公司所有，请严格遵守公司保密协定，未经允许，严禁对外拷贝和流通!
//////////////////////////////////////////////////////////////////////////////////////////


//应用的绝对路径
use Capsheaf\Console\Options\CommandOptions;
use Capsheaf\Utils\Types\Str;
use ClientApp\Foundations\Bootstrap\CoreServiceProvider;
use ClientApp\Modules\Trans\TransModule;

define('APP_PATH', str_replace("\\", "/", dirname(__DIR__)).'/');
define('ROOT_PATH', str_replace("\\", "/", dirname(dirname(__DIR__)).'/'));
define('RUNTIME_PATH', ROOT_PATH.'Tmp/');


$app = include APP_PATH.'Foundations/Bootstrap/Bootstrap.php';

$provider = new CoreServiceProvider($app);
$provider->bindServices();

$sBanner =<<<BANNER
+----------------------------------------------------------+
+      Capsheaf Disaster Backup and Recovery System        +
+      All Rights Reserved @ 2012-2019 Capsheaf.com.cn     +
+      Version: 1.0.0.20180129-alpha                       +
+      Module:  %-10s                                 +
+----------------------------------------------------------+


BANNER;

$commandOptions = new CommandOptions();
$commandOptions->addOption('module', 'm|module:', 'Setting current running module name. trans');
$commandOptions->addOption('option', 'o|option:', 'Options to module');
$commandOptions->addOption('debug', 'v|verbose', 'Specific debug level. -v|-vv|-vvv');
$commandOptions->addOption('help', 'h|help', 'See list of options.');

$sModuleName = $commandOptions->getOption('module')->getValue();

echo sprintf($sBanner, $sModuleName ?: 'unknown');

if (windows_os()){
    $sServiceName = "CapsheafModule".Str::camelCase($sModuleName);
} else {
    $sServiceName = "capsheaf-".strtolower($sModuleName);
}

if (empty($sModuleName) || $commandOptions->getOption('help')->enabled()){
    echo 'Usage: CapClient OPTIONS'.PHP_EOL;
    $commandOptions->outputRenderOptions();

    die();
}

$app['log']->info('Application started.');

$sModuleOption = $commandOptions->getOption('option')->getValue();
$provider->checkPrerequisite();

switch ($sModuleName){
    case 'trans':
        //绑定单个模块，这里是单线程（进程），只使用单个模块
        $module = new TransModule($app);
        break;
    default:
        throw new LogicException('Invalid module name.');
}


$module->init()->run();
