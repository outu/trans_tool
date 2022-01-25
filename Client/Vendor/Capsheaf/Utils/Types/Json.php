<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 15:07:28 CST
 *  Description:     Json.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 15:07:28 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

use RuntimeException;

class Json
{
    
    /**
     * 转换到JSON字符串
     * @param mixed $data 要转换位JSON字符串的数据
     * @param bool $bIgnoreErrors 是否忽略错误，若不忽略错误将首先在遇到错误的情况下尝试修复编码，然后继续尝试，若还存在问题将抛出异常。
     * @param int $nOptions json_encode 选项，如：JSON_PRETTY_PRINT，JSON_FORCE_OBJECT，多个选项用【|】连接，注意选项开始支持的PHP版本
     * @return string|bool a JSON encoded string on success or FALSE on failure.
     * @throws RuntimeException 不忽略错误并在TO JSON失败的情况下抛出异常
     */
    public static function toJson($data, $bIgnoreErrors = false, $nOptions = 0)
    {
        if ($bIgnoreErrors){
            return @self::jsonEncode($data, $nOptions);
        }

        $sJsonEncoded = self::jsonEncode($data, $nOptions);

        if ($sJsonEncoded === false) {
            $sJsonEncoded = self::handleJsonEncodeError(json_last_error(), $data, $nOptions);
        }

        return $sJsonEncoded;
    }


    /**
     * 从JSON字符串转换为数组或可能的对象
     * @param string $sJsonString
     * @param bool $bIgnoreErrors
     * @param bool $bToAssocArray
     * @param int $nOptions
     * @return array null表示在忽略错误的情况下转换失败
     */
    public static function fromJson($sJsonString, $bIgnoreErrors = true, $bToAssocArray = true, $nOptions = 0)
    {
        if ($bIgnoreErrors){
            return @self::jsonDecode($sJsonString, $bToAssocArray, $nOptions);
        }

        $arrTo = self::jsonDecode($sJsonString, $bToAssocArray, $nOptions);

        if ($arrTo === null) {
            $arrTo = self::handleJsonDecodeError(json_last_error(), $sJsonString, $bToAssocArray, $nOptions);
        }

        return $arrTo;
    }


    /**
     * JSON ENCODE
     * @param mixed $data
     * @param int $nOptions
     * @return string|bool a JSON encoded string on success or FALSE on failure.
     */
    public static function jsonEncode($data, $nOptions = 0)
    {
        Encoding::toUTF8($data);//首先就尝试转换编码
        if (version_compare(PHP_VERSION, '5.4.0', '>=')){
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $nOptions);
        }

        return json_encode($data, $nOptions);
    }


    /**
     * @param $sJsonString
     * @param bool $bToAssocArray
     * @param int $nOptions
     * @return mixed|null  null表示转换失败
     */
    public static function jsonDecode($sJsonString, $bToAssocArray = true, $nOptions = 0)
    {
        $to = json_decode($sJsonString, $bToAssocArray, 512, $nOptions);

        return $to;
    }


    /**
     * @param int $nErrorCode
     * @param mixed $data
     * @param int $nOptions
     * @return string 返回JSON ENCODE后的数据
     * @throws RuntimeException 通过转换编码后依然不能转换将抛出异常
     */
    private static function handleJsonEncodeError($nErrorCode, $data, $nOptions = 0)
    {
        if ($nErrorCode !== JSON_ERROR_UTF8){
            self::throwJsonError($nErrorCode, $data);
        }

        if (is_string($data)){
            self::detectAndCleanUtf8($data);
        } elseif (is_array($data)){
            array_walk_recursive($data, [__CLASS__, 'detectAndCleanUtf8']);
        } else {
            self::throwJsonError($nErrorCode, $data);
        }

        $sJsonEncoded = self::jsonEncode($data, $nOptions);

        if ($sJsonEncoded === false) {
            self::throwJsonError(json_last_error(), $data);
        }

        return $sJsonEncoded;
    }


    private static function handleJsonDecodeError($nErrorCode, $sJsonString, $bToAssocArray = true, $nOption = 0)
    {
        if ($nErrorCode !== JSON_ERROR_UTF8){
            self::throwJsonError($nErrorCode, $sJsonString);
        }

        //去除不合法的字符
        self::detectAndCleanUtf8($sJsonString);

        $arrTo = self::jsonDecode($sJsonString, $bToAssocArray, $nOption);

        if ($arrTo === null) {
            self::throwJsonError(json_last_error(), $sJsonString);
        }

        return $arrTo;
    }


    /**
     * @param int $nErrorCode JSON错误代码
     * @param mixed $data Encoding/Decoding的数据
     * @throws RuntimeException
     */
    private static function throwJsonError($nErrorCode, $data)
    {
        switch ($nErrorCode) {
            case JSON_ERROR_DEPTH://since PHP 5.3.0
                $sErrorMessage = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH://since PHP 5.3.0
                $sErrorMessage = 'Occurs with underflow or with the modes mismatch.';
                break;
            case JSON_ERROR_CTRL_CHAR://since PHP 5.3.0
                $sErrorMessage = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_UTF8://since PHP 5.3.3
                $sErrorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            default:
                if (version_compare(PHP_VERSION, '5.5.0', '>=')){
                    //Returns the error message on success, "No error" if no error has occurred, or FALSE on failure.
                    $sErrorMessage = json_last_error_msg();
                }
                $sErrorMessage = empty($sErrorMessage) ? 'Unknown error' : $sErrorMessage;
        }

        throw new RuntimeException('Json failed: '.$sErrorMessage);
    }


    /**
     * 检测和清理不合法的UTF8字符，假定是ISO-8859-15
     * @param string &$sData
     */
    public static function detectAndCleanUtf8(&$sData)
    {
        if (is_string($sData) && !preg_match('//u', $sData)) {
            $sData = preg_replace_callback(
                '/[\x80-\xFF]+/',
                function ($m) { return utf8_encode($m[0]); },
                $sData
            );
            $sData = str_replace(
                ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
                ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
                $sData
            );
        }
    }


    /**
     * 判断是否是有效的JSON字符串（不做其它编码处理）
     * @param string $sJsonString
     * @return bool
     */
    public static function isJsonString($sJsonString)
    {
        json_decode($sJsonString, true);

        return (json_last_error() == JSON_ERROR_NONE);
    }

}
