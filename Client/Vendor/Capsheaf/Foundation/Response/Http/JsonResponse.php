<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-21 19:46:15 CST
 *  Description:     JsonResponse.php's function description
 *  Version:         1.0.0.20180421-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-21 19:46:15 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Response\Http;

use Capsheaf\Utils\Types\Json;

class JsonResponse extends HttpResponse
{

    protected $m_sJsonData;


    /**
     * JsonResponse constructor.
     * @param null|string|mixed $data
     * @param int $nStatusCode
     * @param array $arrHeaders
     * @param bool $bAlreadyJson 是否已经是Json字符串了（即不需要再次json_encode）
     */
    public function __construct($data = null, $nStatusCode = 200, $arrHeaders = [], $bAlreadyJson = false)
    {
        parent::__construct('', $nStatusCode, $arrHeaders);


        $bAlreadyJson ? $this->setFromJson($data) : $this->setFromData($data);
    }


    public function setFromJson($sJsonData)
    {
        $this->m_sJsonData = $sJsonData;

        return $this->updateContent();
    }


    public function setFromData($data = [])
    {
        $sJsonData = Json::toJson($data, false);

        return $this->setFromJson($sJsonData);
    }


    /**
     * 根据设置的m_sJsonData更新m_sContent
     * @return $this
     */
    public function updateContent()
    {
        $sCharset = $this->m_sCharset ?: 'UTF-8';

        $this->m_header->set('Content-Type', "application/json; charset={$sCharset}");

        return $this->setContent($this->m_sJsonData);
    }


}
