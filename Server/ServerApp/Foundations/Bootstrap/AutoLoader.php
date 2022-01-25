<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-20 14:06:52 CST
 *  Description:     AutoLoader.php's function description
 *  Version:         1.0.0.20180420-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-20 14:06:52 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Foundations\Bootstrap;

require_once ROOT_PATH.'Vendor/Capsheaf/Application/AutoLoader.php';

class AutoLoader extends \Capsheaf\Application\AutoLoader
{
    public function __construct()
    {
        parent::__construct(ROOT_PATH, APP_PATH);
    }
}