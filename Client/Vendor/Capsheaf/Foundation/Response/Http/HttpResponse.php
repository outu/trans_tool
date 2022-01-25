<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-02-25 15:50:25 CST
 *  Description:     HttpResponse.php's function description
 *  Version:         1.0.0.20180225-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-02-25 15:50:25 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Response\Http;

use Capsheaf\Foundation\Request\Http\HttpRequest;
use Capsheaf\Foundation\Response\Response;
use Capsheaf\Support\Http\Header;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use UnexpectedValueException;

class HttpResponse extends Response
{

    /**
     * 状态码
     * @var int
     */
    protected $m_nStatusCode;

    /**
     * 状态码后面的状态文本
     * @var string
     */
    protected $m_sStatusText;

    /**
     * @var string
     */
    protected $m_sCharset;

    /**
     * @var Header
     */
    protected $m_header;

    /**
     * HTTP协议版本（1.0，1.1）
     * @var string
     */
    protected $m_sVersion;

    /**
     * 要发送的文本正文
     * @var string
     */
    protected $m_sContent = '';


    public function __construct($sContent = '', $nStatusCode = 200, $arrHeaders = [])
    {
        $this->m_header = new Header($arrHeaders);
        $this->setContent($sContent);
        $this->setStatusCode($nStatusCode);
    }


    /**
     * @param HttpRequest $request
     * @return $this|Response
     */
    public function prepare($request)
    {
        $sCharset = $this->m_sCharset ?: 'UTF-8';
        if (!$this->m_header->has('Content-Type')){
            $this->m_header->set('Content-Type', "text/html; charset={$sCharset}");
        }

        //$_SERVER['SERVER_PROTOCOL']中设置的不是HTTP/1.0那么就使用HTTP/1.1
        if ($request->getServer()->get('SERVER_PROTOCOL') != 'HTTP/1.0'){
            $this->setVersion('1.1');
        }

        $this->m_header->set('Server', 'Capsheaf Server/3.0');

        header_remove('X-Powered-By');

        return $this;
    }


    /**
     * 设置响应的状态码及消息文本
     * @param int $nCode 状态码
     * @param string|null|false $sText 实际的状态消息，缺省为null表示自动获取，false表示没有状态码消息
     * @return $this
     */
    public function setStatusCode($nCode, $sText = null)
    {
        $this->m_nStatusCode = $nCode = (int)$nCode;
        if (!$this->isValidStatusCode()){
            throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $nCode));
        }

        if (null === $sText){
            $this->m_sStatusText = isset(ResponseStatus::$m_arrStatusTexts[$nCode]) ? ResponseStatus::$m_arrStatusTexts[$nCode] : 'unknown status';
        }

        if (false === $sText){
            $this->m_sStatusText = '';
            return $this;
        }

        $this->m_sStatusText = $sText;

        return $this;
    }


    /**
     * 获取当前的状态码
     * @return int
     */
    public function getStatusCode()
    {
        return $this->m_nStatusCode;
    }


    /**
     * @param string $sVersion
     * @return HttpResponse
     */
    public function setVersion($sVersion)
    {
        $this->m_sVersion = $sVersion;

        return $this;
    }


    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->m_sVersion;
    }


    /**
     * 是否是重定向响应
     * @return bool
     */
    public function isRedirect()
    {
        return $this->m_nStatusCode >= 300 && $this->m_nStatusCode < 400;
    }


    /**
     * 判断状态码是否有效
     * @return bool
     */
    public function isValidStatusCode()
    {
        return $this->m_nStatusCode >= 100 && $this->m_nStatusCode < 600;
    }


    public function setCharset($sCharset)
    {
        $this->m_sCharset = $sCharset;

        return $this;
    }


    public function getCharset()
    {
        return $this->m_sCharset;
    }


    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            echo '';
        }

        return $this;
    }


    public function sendHeaders()
    {
        if (headers_sent()) {
            return $this;
        }

        if (!$this->m_header->has('Date')){
            $this->setHeaderDate(DateTime::createFromFormat('U', time()));
        }

        // status
        header(sprintf('HTTP/%s %s %s', $this->m_sVersion, $this->m_nStatusCode, $this->m_sStatusText), true, $this->m_nStatusCode);

        $arrHeaders = $this->m_header->all();
        foreach ($arrHeaders as $sName => $sValue){
            header($sName.': '.$sValue, true, $this->m_nStatusCode);
        }

        return $this;
    }


    /**
     * @param string|mixed $sContent
     * @return $this
     * @throws UnexpectedValueException
     */
    public function setContent($sContent)
    {
        if ($sContent !== null &&
            !is_string($sContent) &&
            !is_scalar($sContent) &&
            !is_callable([$sContent, '__toString'])
        ){
            throw new UnexpectedValueException("The Response content must be a string or object implementing __toString(), ".gettype($sContent)." given.");
        }

        $this->m_sContent = (string)$sContent;

        return $this;
    }


    /**
     * 获取内容文本
     * @return string
     */
    public function getContents()
    {
        return $this->m_sContent;
    }


    /**
     * 发送内容文本
     * @return $this
     */
    public function sendContent()
    {
        echo $this->m_sContent;

        return $this;
    }


    public function __toString()
    {
        return $this->getContents();
    }


    /**
     * 设置返回响应头中的Date字段
     * @param DateTime $dateTime
     * @return $this
     */
    public function setHeaderDate(DateTime $dateTime)
    {
        $dateTime->setTimezone(new DateTimeZone('UTC'));
        //Header中的Date全部都是GMT时间
        $this->m_header->set('Date', $dateTime->format('D, d M Y H:i:s').' GMT');

        return $this;
    }


    /**
     * @return string
     */
    public function getHeaderDate()
    {
        if (!$this->m_header->has('Date')){
            $this->setHeaderDate(DateTime::createFromFormat('U', time()));
        }

        return $this->m_header->get('Date');
    }

}
