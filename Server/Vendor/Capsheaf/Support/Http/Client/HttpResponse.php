<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-01-03 09:26:09 CST
 *  Description:     HttpResponse.php's function description
 *  Version:         1.0.0.20180103-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-01-03 09:26:09 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Support\Http\Client;

use Capsheaf\Utils\Types\Arr;
use Capsheaf\Utils\Types\Json;

class HttpResponse implements ResponseInterface
{

    protected $m_arrInfo = [];
    protected $m_arrHeaders = [];
    protected $m_sContent = '';


    /**
     * HttpResponse constructor.
     * @param resource $hCurl
     * @throws HttpClientRequestException
     */
    public function __construct($hCurl)
    {
        //true on success or false on failure. However, if the CURLOPT_RETURNTRANSFER option is set, it will return the result on success, false on failure.
        $sResponse = curl_exec($hCurl);

        if (!curl_errno($hCurl)) {
            $this->m_arrInfo = curl_getinfo($hCurl);
            $nHeaderLength = $this->getInfo('header_size', 0);
            $this->m_arrHeaders = $this->parseHeaders($sResponse, $nHeaderLength);
            $this->m_sContent = $this->parseBody($sResponse, $nHeaderLength);
        } else {
            throw new HttpClientRequestException(curl_error($hCurl));
        }

        curl_close($hCurl);
    }


    public static function make($hCurl)
    {
        return new static($hCurl);
    }


    /**
     * @param string $sKey
     * 可能支持的选项范例：
     * array (
     *   'url' => 'http://www.baidu.com/',
     *   'content_type' => 'text/html',
     *   'http_code' => 200,
     *   'header_size' => 750,
     *   'request_size' => 52,
     *   'filetime' => -1,
     *   'ssl_verify_result' => 0,
     *   'redirect_count' => 0,
     *   'total_time' => 0.092999999999999999,
     *   'namelookup_time' => 0.014999999999999999,
     *   'connect_time' => 0.062,
     *   'pretransfer_time' => 0.062,
     *   'size_upload' => 0.0,
     *   'size_download' => 14613.0,
     *   'speed_download' => 157129.0,
     *   'speed_upload' => 0.0,
     *   'download_content_length' => 14613.0,
     *   'upload_content_length' => -1.0,
     *   'starttransfer_time' => 0.092999999999999999,
     *   'redirect_time' => 0.0,
     *   'redirect_url' => '',
     *   'primary_ip' => '180.97.33.108',
     *   'certinfo' => array (),
     *   'primary_port' => 80,
     *   'local_ip' => '192.168.1.2',
     *   'local_port' => 62263,
     *   'request_header' => 'GET / HTTP/1.1\r\nHost: www.baidu.com\r\nAccept: *\/*\r\n\r\n',
     *   )
     *
     * @param mixed $default 取不到值时的默认值
     * @return mixed
     * @see http://php.net/manual/zh/function.curl-getinfo.php
     */
    public function getInfo($sKey = null, $default = null)
    {
        return Arr::get($this->m_arrInfo, $sKey, $default);
    }


    protected function parseHeaders($sResponse, $nHeaderLength)
    {
        $sHeadersText = substr($sResponse, 0, $nHeaderLength);

        $arrHeaders = [];
        foreach (explode("\r\n", $sHeadersText) as $sHeader){
            if (strpos($sHeader, ':')) {
                list($sHeaderName, $sHeaderValue) = explode(':', $sHeader);
                $arrHeaders[$sHeaderName] = trim($sHeaderValue);
            }
        }

        return $arrHeaders;
    }


    protected function parseBody($sResponse, $nHeaderLength)
    {
        return substr($sResponse, $nHeaderLength);
    }


    /**
     * 获取返回的状态码
     * @return string
     */
    public function statusCode()
    {
        return $this->getInfo('http_code');
    }


    /**
     * 获取返回的CONTENT-TYPE
     * @return string
     */
    public function contentType()
    {
        return $this->getInfo('content_type');
    }


    /**
     * 获取返回的内容
     * @return string
     */
    public function content()
    {
        return $this->m_sContent;
    }


    /**
     * 获取头部数组
     * @return array
     */
    public function headers()
    {
        return $this->m_arrHeaders;
    }


    /**
     * 获取单个Header键对应的值
     * @param string $sHeaderName
     * @param string $sDefault
     * @return string
     */
    public function header($sHeaderName, $sDefault = null)
    {
        return Arr::get($this->m_arrHeaders, $sHeaderName, $sDefault);
    }


    /**
     * 假定内容为json字符串，返回json_decode后的内容
     * @return mixed|array
     */
    public function json()
    {
        return Json::fromJson($this->m_sContent, true);
    }


    public function getCookies()
    {

    }

}
