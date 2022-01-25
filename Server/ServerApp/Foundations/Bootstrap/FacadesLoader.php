<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-20 14:07:26 CST
 *  Description:     FacadesLoader.php's function description
 *  Version:         1.0.0.20180420-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-20 14:07:26 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Foundations\Bootstrap;

require_once ROOT_PATH.'Vendor/Capsheaf/Facades/FacadesLoader.php';

class FacadesLoader extends \Capsheaf\Facades\FacadesLoader
{
    public function __construct()
    {
        $arrFacadesAliases = array(
            'App' => '\Capsheaf\Facades\AutoLoad\App',
            'FileSystem' => '\Capsheaf\Facades\AutoLoad\FileSystem',
            'Events' => '\Capsheaf\Facades\AutoLoad\Events',
            'Log' => '\Capsheaf\Facades\AutoLoad\Log',
            'DB' => '\Capsheaf\Facades\AutoLoad\DB',
            //'Transmitter' => '\Capsheaf\Facades\AutoLoad\Transmitter',
        );

        parent::__construct($arrFacadesAliases);
    }
}