<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 15:42:19 CST
 *  Description:     Encoding.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 15:42:19 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

class Encoding
{

    public static function toUTF8(&$data, $sPossibleEncodings = 'UTF-8,CP936,ASCII')
    {
        if (is_string($data)){
            $data = iconv(mb_detect_encoding($data, $sPossibleEncodings, true), "UTF-8//IGNORE", $data);
        } else if (is_array($data)) {
            array_walk_recursive(
                $data, function (&$value) use ($sPossibleEncodings){
                    if (is_string($value)){
                        $value = iconv(mb_detect_encoding($value, $sPossibleEncodings, true), "UTF-8//IGNORE", $value);
                    }
                }
            );
        }
    }


    public static function isWindows1252ToUTF8($sDetectString)
    {
        $sChangeString = mb_convert_encoding($sDetectString, 'WINDOWS-1252', 'UTF-8');

        if (stripos($sChangeString, '?') === false) {

            return $sChangeString;
        } else {

            return $sDetectString;
        }

    }

}
