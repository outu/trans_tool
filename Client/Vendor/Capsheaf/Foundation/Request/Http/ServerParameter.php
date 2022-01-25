<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-22 23:33:11 CST
 *  Description:     ServerParameter.php's function description
 *  Version:         1.0.0.20180422-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-22 23:33:11 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Request\Http;

use Capsheaf\Utils\Types\Parameter;

class ServerParameter extends Parameter
{

    public function __construct(array $arrParameters = [])
    {
        parent::__construct($arrParameters);
    }


    /**
     * 获取HTTP请求的头部，注意返回的键均为大写形式，不同于实际情况（EG：Accept-Language: ）
     * @return array
     */
    public function getHeaders()
    {
        $arrHeaders = [];
        //其它形式的不符合HTTP_开头的头部字段
        $arrHeaderKeysExtra = ['CONTENT_LENGTH' => true, 'CONTENT_TYPE' => true, 'CONTENT_MD5' => true];
        foreach ($this->m_arrParameters as $sKey => $sValue){
            if (strpos($sKey, 'HTTP_') === 0) {
                $arrHeaders[substr($sKey, 5)] = $sValue;
            } elseif (isset($arrHeaderKeysExtra[$sKey])) {
                $arrHeaders[$sKey] = $sValue;
            }
        }

        //TODO 验证相关头部未处理
        return $arrHeaders;
    }

}
