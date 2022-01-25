<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-02-25 15:49:46 CST
 *  Description:     HttpRequest.php's function description
 *  Version:         1.0.0.20180225-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-02-25 15:49:46 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Request\Http;

use Capsheaf\Foundation\Request\Request;
use Capsheaf\Support\Http\Header;
use Capsheaf\Support\Traits\MetaTrait;
use Capsheaf\Utils\Types\Parameter;

class HttpRequest extends Request
{

    use MetaTrait;

    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PURGE = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    /**
     * HTTP方法
     * @var string
     */
    protected $m_sHttpMethod;

    /**
     * HTTP版本
     * @var string
     */
    protected $m_sHttpVersion;

    /**
     * Headers
     * @var Header
     */
    protected $m_headers;

    /**
     * GET参数
     * @var Parameter
     */
    protected $m_query;

    /**
     * POST参数
     * @var Parameter
     */
    protected $m_request;

    /**
     * 自定义属性
     * @var Parameter
     */
    protected $m_attributes;

    /**
     * COOKIES
     * @var Parameter
     */
    protected $m_cookies;

    /**
     * 文件
     * @var Parameter
     */
    protected $m_files;

    /**
     * $_SERVER变量
     * @var ServerParameter
     */
    protected $m_server;

    /**
     * 请求正文内容
     * @var string
     */
    protected $m_sContents;


    public function __construct(array $arrQuery = [],
                                array $arrRequest = [],
                                array $arrAttributes = [],
                                array $arrCookies = [],
                                array $arrFiles = [],
                                array $arrServer = [],
                                $sContents = null)
    {
        $this->initialize($arrQuery, $arrRequest, $arrAttributes, $arrCookies, $arrFiles, $arrServer, $sContents);
        $sModule     = $this->m_query->get('module',        $this->m_request->get('module', 'Index'));
        $sController = $this->m_query->get('controller',    $this->m_request->get('controller', 'Index'));
        $sAction     = $this->m_query->get('action',        $this->m_request->get('action', 'index'));

        parent::__construct($sModule, $sController, $sAction);
    }


    public function initialize(array $arrQuery = [],
                               array $arrRequest = [],
                               array $arrAttributes = [],
                               array $arrCookies = [],
                               array $arrFiles = [],
                               array $arrServer = [],
                               $sContents = null)
    {
        $this->m_query = new Parameter($arrQuery);
        $this->m_request = new Parameter($arrRequest);
        $this->m_attributes = new Parameter($arrAttributes);
        $this->m_cookies = new Parameter($arrCookies);
        $this->m_files = new Parameter($arrFiles);
        $this->m_server = new ServerParameter($arrServer);
        $this->m_headers = new Header($this->m_server->getHeaders());
        $this->m_sContents = $sContents;
    }


    /**
     * @return Parameter
     */
    public function getQuery()
    {
        return $this->m_query;
    }


    /**
     * @return Parameter
     */
    public function getRequest()
    {
        return $this->m_request;
    }


    /**
     * @param Parameter $parameter
     * @return HttpRequest
     */
    public function setRequest(Parameter $parameter)
    {
        $this->m_request = $parameter;

        return $this;
    }


    /**
     * @return Parameter
     */
    public function getAttributes()
    {
        return $this->m_attributes;
    }


    /**
     * @return Parameter
     */
    public function getCookies()
    {
        return $this->m_cookies;
    }


    /**
     * @return Parameter
     */
    public function getFiles()
    {
        return $this->m_files;
    }


    /**
     * @return ServerParameter
     */
    public function getServer()
    {
        return $this->m_server;
    }


    /**
     * @return Header
     */
    public function getHeaders()
    {
        return $this->m_headers;
    }


    /**
     * @return static
     */
    public static function buildRequest()
    {
        $request = new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

        if ($request->m_headers->get('CONTENT_TYPE') == 'application/x-www-form-urlencoded' &&
            in_array($request->m_server->get('REQUEST_METHOD', 'GET'), ['PUT', 'DELETE', 'PATCH']))
        {
            parse_str($request->getContents(), $arrParameters);
            $request->setRequest(new Parameter($arrParameters));
        }

        return $request;
    }


    /**
     * @param string $sContents
     * @return HttpRequest
     */
    public function setContents($sContents)
    {
        $this->m_sContents = $sContents;

        return $this;
    }


    /**
     * @return string
     */
    public function getContents()
    {
        return $this->m_sContents;
    }


    /**
     * @return array
     */
    public function getParameters()
    {
        $arrParameters = array_merge($this->m_query->all(), $this->m_request->all());

        return $arrParameters;
    }

}
