<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-19 22:16:15 CST
 *  Description:     CapsheafServer.php's function description
 *  Version:         1.0.0.20180419-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-19 22:16:15 CST initialized the file
 ******************************************************************************/


ini_set('display_errors', 'on');
error_reporting(E_ALL & ~E_DEPRECATED);

use Capsheaf\Foundation\Request\Http\HttpRequest;
use ServerApp\Foundations\Bootstrap\CoreServiceProvider;

//应用和公共根目录的绝对路径,带最后的的/
define('APP_PATH', str_replace("\\", "/", dirname(__DIR__).'/'));
define('ROOT_PATH', str_replace("\\", "/", dirname(APP_PATH).'/'));
define('RUNTIME_PATH', ROOT_PATH.'Tmp/');

$app = include APP_PATH.'/Foundations/Bootstrap/Bootstrap.php';

$provider = new CoreServiceProvider($app);
$provider->bindServices();

$app['log']->info('Application started.');

$provider->checkPrerequisite();
$provider->initServices();

//ServerKernel
$serverKernel = $app->make('kernel');
$request = HttpRequest::buildRequest();
$response = $serverKernel->handle($request);
$response->send();

$serverKernel->terminate($request, $response);

