<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-01-03 09:25:28 CST
 *  Description:     HttpClient.php's function description
 *  Version:         1.0.0.20180103-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-01-03 09:25:28 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Support\Http\Client;

use InvalidArgumentException;

/**
 * Class HttpClient
 * @package Capsheaf\Support\Http\Client
 * @method HttpResponse get(array $arrArguments) GET请求
 * @method HttpResponse post(array $arrArguments) POST请求
 * @method HttpResponse put(array $arrArguments) PUT请求
 * @method HttpResponse delete(array $arrArguments)
 * @method HttpResponse patch(array $arrArguments)
 * @method HttpResponse head(array $arrArguments)
 * @method HttpResponse options(array $arrArguments)
 */
class HttpClient
{
    /**
     * @var HttpRequest
     */
    protected $m_httpRequest = null;


    public function __construct()
    {

    }


    protected function isValidRequest($arrRequestParams = [])
    {
        return array_key_exists('url', $arrRequestParams);
    }


    /**
     * 调用具体方法
     * @param string $sMethodName get|post|put|delete|patch|head|options
     * @param array $arrArguments, 其中必须包含[ 0 => ['url'=>'abc.php']]
     * @return HttpResponse|string
     * @throws HttpClientRequestException
     * @throws InvalidArgumentException
     * @see HttpRequest
     */
    public function __call($sMethodName, $arrArguments)
    {
        $arrRequestParams = array_pop($arrArguments);
        if (!is_array($arrRequestParams)){
            $arrRequestParams = ['url' => $arrRequestParams];
        }

        if ($this->isValidRequest($arrRequestParams)) {
            $arrRequestParams['method'] = HttpRequest::method($sMethodName);
            $this->m_httpRequest = new HttpRequest($arrRequestParams);
            return $this->m_httpRequest->send();
        }

        throw new InvalidArgumentException('Invalid request parameters.');
    }
}
