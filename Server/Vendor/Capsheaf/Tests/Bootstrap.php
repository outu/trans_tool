<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-09-20 09:43:50 CST
 *  Description:     Bootstrap.php's function description
 *  Version:         1.0.0.20180920-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-09-20 09:43:50 CST initialized the file
 ******************************************************************************/

//应用和公共根目录的绝对路径,带最后的的/
define('ROOT_PATH', str_replace("\\", "/", dirname(dirname(dirname(__DIR__))).'/'));

use Capsheaf\Application\Application;
use Capsheaf\Application\AutoLoader;
use Capsheaf\Facades\FacadesLoader;

include __DIR__.'/../Application/AutoLoader.php';
include __DIR__.'/../Facades/FacadesLoader.php';

defined('TIMEZONE') ?: define('TIMEZONE', 'Asia/Shanghai');

date_default_timezone_set(TIMEZONE);

//避免错误信息在STDOUT中显示，注意STDERR中仍会输出
ini_set('display_errors', 'off');

$autoloader = new AutoLoader(ROOT_PATH, '');
$autoloader->register();

$facadesLoader = new FacadesLoader();
$facadesLoader->registerAutoLoader();

$app = new Application('');

return $app;

