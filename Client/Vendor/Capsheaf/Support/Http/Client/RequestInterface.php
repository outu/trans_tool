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

namespace Capsheaf\Support\Http\Client;

interface RequestInterface
{

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';

}
