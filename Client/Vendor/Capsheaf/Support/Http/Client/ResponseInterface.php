<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-01-03 09:28:11 CST
 *  Description:     ResponseInterface.php's function description
 *  Version:         1.0.0.20180103-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-01-03 09:28:11 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Support\Http\Client;;

interface ResponseInterface
{

    /**
     * 获取返回的状态码
     * @return string
     */
    public function statusCode();


    /**
     * 获取返回的CONTENT-TYPE
     * @return string
     */
    public function contentType();


    /**
     * 获取返回的内容
     * @return string
     */
    public function content();


    /**
     * 假定内容为json字符串，返回json_decode后的内容
     * @return mixed
     */
    public function json();


    /**
     * 获取头部数组
     * @return array
     */
    public function headers();


    /**
     * 获取单个Header键对应的值
     * @param string $sHeaderName
     * @return string
     */
    public function header($sHeaderName);

}
