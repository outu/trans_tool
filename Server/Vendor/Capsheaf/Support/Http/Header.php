<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-22 23:35:11 CST
 *  Description:     Header.php's function description
 *  Version:         1.0.0.20180422-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-22 23:35:11 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Support\Http;

use Capsheaf\Utils\Types\Parameter;

class Header extends Parameter
{

    /**
     * 存储的Cookies
     * @var array
     */
    protected $m_arrCookies;


    public function __construct(array $arrParameters = [])
    {
        parent::__construct();
        $this->add($arrParameters);
    }


    /**
     * 批量添加Header字段
     * @param array $arrHeaders
     * @return $this
     */
    public function add($arrHeaders = [])
    {
        foreach ($arrHeaders as $sKey => $sValue){
            $this->set($sKey, $sValue);
        }

        return $this;
    }


    /**
     * @param string $sKey
     * @param string $sValue
     */
    public function set($sKey, $sValue)
    {
        //要保留的键，参考https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Headers
        $arrReserveKeys = [
            'DNT',//Do Not Track请求
            //以下为响应,
            'ETag',
            'TE',
            'WWW-Authenticate',
            'X-XSS-Protection',
            'X-DNS-Prefetch-Control',
        ];

        //EG：$_SERVER['HTTP_ACCEPT_ENCODING']->ACCEPT_ENCODING->Accept-Encoding
        if (!in_array($sKey, $arrReserveKeys)){

            $sKey = str_replace('_', '-', $sKey);
            $sKey = implode(
                '-', array_map(
                    function($sPart){
                        //先全部小写再首字母大写
                        return ucfirst(strtolower($sPart));
                    }, explode('-', $sKey)
                )
            );

            //$sKey = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $sKey))));有缺陷，不能保证使用-是正确转换大小写
        }

        parent::set($sKey, $sValue);
    }


    public function __toString()
    {
        $sContents = '';
        foreach ($this->m_arrParameters as $sKey => $sValue) {
            $sContents .= $sKey.': '.$sValue."\r\n";
        }

        return $sContents;
    }

}
