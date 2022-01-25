<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 19:54:35 CST
 *  Description:     Normalizer.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 19:54:35 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

class Normalizer
{

    public static $m_arrSettings = [];


    public static function setting($arrSettings = [])
    {
        $arrDefaultSettings = [
            'datetime' => 'Y-m-d H:i:s',
        ];

        self::$m_arrSettings = array_merge($arrDefaultSettings, $arrSettings);
    }

}
