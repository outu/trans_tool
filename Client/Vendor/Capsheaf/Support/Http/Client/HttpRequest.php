<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-01-03 09:26:03 CST
 *  Description:     HttpRequest.php's function description
 *  Version:         1.0.0.20180103-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-01-03 09:26:03 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Support\Http\Client;

use Capsheaf\Utils\Types\Json;
use Capsheaf\Utils\Util;
use Exception;

class HttpRequest implements RequestInterface
{

    protected $m_arrDefaultRequestParams = [
        'version' => null,
        'method' => self::METHOD_GET,
        'insecure' => true,
        'url' => null,
        'headers' => [],
        'options' => [],
        'parameters' => [],
        'content' => null,
        'redirect' => 30,//最大重定向次数
        'timeout' => 30,
        'json' => false,//指定Header中的内容类型为JSON
        'digest' => [],//Digest认证
        'basic' => [],//Basic认证
        'retry' => 0,//重试次数
        'period' => 0//重试间隔
    ];

    protected $m_sVersion;
    protected $m_sMethod = self::METHOD_GET;
    protected $m_bInsecure = true;
    protected $m_sUrl = null;
    protected $m_arrHeaders = [];
    protected $m_arrOptions = [];
    protected $m_arrParameters = [];
    protected $m_sContent = null;
    protected $m_nMaxRedirect = 30;
    protected $m_nTimeout = 30;
    protected $m_bJson = false;
    protected $m_arrDigestAuth = [];
    protected $m_arrBasicAuth = [];
    protected $m_nRetryTimes = 0;
    protected $m_nRetryPeriod = 0;


    public function __construct($arrRequestParams = [])
    {
        $arrRequestParams       = array_merge($this->m_arrDefaultRequestParams, $arrRequestParams);
        $this->m_sVersion       = $arrRequestParams['version'];
        $this->m_sMethod        = $arrRequestParams['method'];
        $this->m_bInsecure      = $arrRequestParams['insecure'];//HTTPS时忽略证书
        $this->m_sUrl           = $arrRequestParams['url'];
        $this->m_arrHeaders     = $arrRequestParams['headers'];
        $this->m_arrOptions     = $arrRequestParams['options'];
        $this->m_arrParameters  = (array)$arrRequestParams['parameters'];//键值对数组，POST时会放到POST主体中；GET会将它放到?后
        $this->m_sContent       = $arrRequestParams['content'];//若没有对于POST没有指定parameters，则会直接使用纯文本的content；GET会附加到&
        $this->m_nMaxRedirect   = $arrRequestParams['redirect'];
        $this->m_nTimeout       = $arrRequestParams['timeout'];
        $this->m_bJson          = $arrRequestParams['json'];
        $this->m_arrDigestAuth  = $arrRequestParams['digest'];
        $this->m_arrBasicAuth   = $arrRequestParams['basic'];
        $this->m_nRetryTimes    = $arrRequestParams['retry'];
        $this->m_nRetryPeriod   = $arrRequestParams['period'];

        if ($this->m_bJson){
            $this->m_arrHeaders[] = 'Content-Type: application/json';
        }
    }


    /**
     * 获取方法名称在该类中的表示
     * @param string $sHttpMethodName
     * @return string
     */
    public static function method($sHttpMethodName)
    {
        $sHttpMethodName = 'METHOD_'.strtoupper($sHttpMethodName);
        return constant("self::{$sHttpMethodName}");
    }


    /**
     * @return HttpResponse
     * @throws HttpClientRequestException
     */
    public function send()
    {
        try {
            $hCurl = curl_init();
        } catch (Exception $exception){
            throw new HttpClientRequestException('Curl initialize failed.');
        }

        curl_setopt_array($hCurl, $this->getCurlOptions());

        try {
            return Util::retry(
                function() use ($hCurl)
                {
                    //抛出异常才会重试
                    return HttpResponse::make($hCurl);
                },
                $this->m_nRetryTimes,
                $this->m_nRetryPeriod
            );

        } catch (Exception $exception){
            //不要忘记关闭句柄
            curl_close($hCurl);
            if ($this->m_nRetryTimes > 0){
                $sMessage = "Send http request failed and retried with {$this->m_nRetryTimes} times. Last error message:{$exception->getMessage()}.";
            } else {
                $sMessage = $sMessage = "Send http request failed. Error message:{$exception->getMessage()}.";
            }

            throw new HttpClientRequestException($sMessage);
        }
    }


    protected function getCurlOptions()
    {
        $arrCurlOptions = [
            CURLOPT_HTTP_VERSION => $this->getCurlHttpVersion(),// 	CURL_HTTP_VERSION_NONE (默认值，让 cURL 自己判断使用哪个版本)，CURL_HTTP_VERSION_1_0 (强制使用 HTTP/1.0)或CURL_HTTP_VERSION_1_1 (强制使用 HTTP/1.1)。
            CURLOPT_URL => $this->m_sUrl,//需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候。
            CURLOPT_CUSTOMREQUEST => $this->m_sMethod,// HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。Note:不确定服务器支持这个自定义方法则不要使用它。
            CURLOPT_HTTPHEADER => $this->m_arrHeaders,//设置 HTTP 头字段的数组。格式： array('Content-type: text/plain', 'Content-length: 100')
            CURLOPT_HEADER => true,//启用时会将头文件的信息作为数据流输出。
            CURLINFO_HEADER_OUT => true,//TRUE 时追踪句柄的请求字符串。
            CURLOPT_FOLLOWLOCATION => true,//TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向。（注意：这是递归的，"Location: " 发送几次就重定向几次，除非设置了 CURLOPT_MAXREDIRS，限制最大重定向次数。）。
            CURLOPT_MAXREDIRS => $this->m_nMaxRedirect,
            CURLOPT_TIMEOUT => $this->m_nTimeout,//允许 cURL 函数执行的最长秒数。
            CURLOPT_RETURNTRANSFER => true,//TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
        ];

        if ($this->m_bInsecure){
            $arrCurlOptions[CURLOPT_SSL_VERIFYHOST] = false;//设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。译者注：公用名(Common Name)一般来讲就是填写你将要申请SSL证书的域名 (domain)或子域名(sub domain)。 设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。 0 为不检查名称。 在生产环境中，这个值应该是 2（默认值）。
            $arrCurlOptions[CURLOPT_SSL_VERIFYPEER] = false;//FALSE 禁止 cURL 验证对等证书（peer's certificate）。要验证的交换证书可以在 CURLOPT_CAINFO 选项中设置，或在 CURLOPT_CAPATH中设置证书目录。自cURL 7.10开始默认为 TRUE。从 cURL 7.10开始默认绑定安装。
        }

        $this->buildParameters($arrCurlOptions);

        return $arrCurlOptions;
    }


    protected function buildParameters(&$arrCurlOptions)
    {
        if (in_array(
            $this->m_sMethod,
            [
                static::method('POST'),
                static::method('PUT'),
                static::method('PATCH'),
            ]
        ))
        {
            if (count($this->m_arrParameters)){
                $arrCurlOptions[CURLOPT_POSTFIELDS] = $this->m_bJson ? Json::toJson($this->m_arrParameters, true) : http_build_query($this->m_arrParameters);
            } elseif (!is_null($this->m_sContent)) {
                $arrCurlOptions[CURLOPT_POSTFIELDS] = $this->m_bJson ? Json::toJson($this->m_sContent, true) : $this->m_sContent;
            }

        } else {
            $sUrl = $this->m_sUrl;

            $sUrl = (count($this->m_arrParameters) > 0) ? ($sUrl.'?'.http_build_query($this->m_arrParameters)) : $sUrl;
            $sUrl = is_null($this->m_sContent) ? $sUrl : ($sUrl.'?'.$this->m_sContent);

            $arrCurlOptions[CURLOPT_URL] = $sUrl;
        }
    }


    /**
     * @return int
     */
    public function getCurlHttpVersion()
    {
        switch ($this->m_sVersion) {
            case '1.0':
                return CURL_HTTP_VERSION_1_0;

            case '1.1':
                return CURL_HTTP_VERSION_1_1;

            case '2':
            case '2.0':
                return CURL_HTTP_VERSION_2_0;

            default:
                return CURL_HTTP_VERSION_NONE;
        }
    }

}
