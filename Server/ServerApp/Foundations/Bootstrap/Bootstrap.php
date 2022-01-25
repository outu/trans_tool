<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-20 14:07:11 CST
 *  Description:     Bootstrap.php's function description
 *  Version:         1.0.0.20180420-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-20 14:07:11 CST initialized the file
 ******************************************************************************/

use Capsheaf\Application\Application;
use ServerApp\Foundations\Bootstrap\AutoLoader;
use ServerApp\Foundations\Bootstrap\FacadesLoader;

include __DIR__.'/AutoLoader.php';
include __DIR__.'/FacadesLoader.php';

defined('TIMEZONE') ?: define('TIMEZONE', 'Asia/Shanghai');

date_default_timezone_set(TIMEZONE);

//避免错误信息在STDOUT中显示，注意STDERR中仍会输出
ini_set('display_errors', 'off');

$autoloader = new AutoLoader();
$autoloader->register();

$facadesLoader = new FacadesLoader();
$facadesLoader->registerAutoLoader();

$app = new Application(APP_PATH);

return $app;


