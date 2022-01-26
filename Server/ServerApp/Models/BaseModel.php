<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-26 15:40:41 CST
 *  Description:     BaseModel.php's function description
 *  Version:         1.0.0.20220126-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-26 15:40:41 CST initialized the file
 *******************************************************************************************/

namespace ServerApp\Models;

use Capsheaf\Database\Model;
use Capsheaf\Database\QueryBuilder;
use Capsheaf\Utils\Types\Arr;
use Capsheaf\Utils\Types\Json;

class BaseModel extends Model
{
    /**
     * 展开单条记录中的json字段到原记录数组，可选保留字段属性
     * @param array $arrRecord 要展开的字段存在的数组
     * @param string $sFields 要展开的字段
     * @param false|null|string $sOldColumnTo false：不需要，null使用默认形式'xxx_raw'，string使用指定字段
     * @return bool 存在字段返回true，不存在返回false
     */
    public static function deserializeJsonColumn(&$arrRecord, $sFields = 'meta', $sOldColumnTo = null)
    {
        if (!is_null($sJson = Arr::get($arrRecord, $sFields))){
            if ($sOldColumnTo !== false){
                if (is_null($sOldColumnTo)){
                    $sRawColumnName             = $sFields.'_raw';
                    $arrRecord[$sRawColumnName] = $sJson;
                } elseif ($sOldColumnTo !== '') {
                    $sRawColumnName             = $sFields.$sOldColumnTo;
                    $arrRecord[$sRawColumnName] = $sJson;
                }
            }

            $arrRecord[$sFields] = Json::fromJson($sJson);

            return true;
        }

        return false;
    }


    /**
     * 切换数据库连接信息
     *
     * @param string $sConnectionName
     *
     * @return QueryBuilder
     */
    public static function switchToDatabase($sConnectionName='ha') {
        return new QueryBuilder(\DB::connection($sConnectionName));
    }
}