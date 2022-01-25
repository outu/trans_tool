<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-23 15:33:36 CST
 *  Description:     MiddlewareInterface.php's function description
 *  Version:         1.0.0.20180423-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-23 15:33:36 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Controller;

use Capsheaf\Foundation\Request\Request;
use Capsheaf\Foundation\Response\Response;
use Closure;

interface MiddlewareInterface
{

    /**
     * 中间件执行流程
     * eg: <br>
     * MyMiddleware::handle(Request $request, Closure $fnNext){ <br>
     *    前置操作放在这里 <br>
     *    Response = $fnNext(Request); <br>
     *    后置操作放置这里 <br>
     *    return Response; <br>
     * } <br>
     * @param Request $request
     * @param Closure $fnNext <br>
     * @return mixed
     */
    public function handle(Request $request, Closure $fnNext);


    /**
     * 结束时要执行的操作
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function terminate(Request $request, Response $response);

}
