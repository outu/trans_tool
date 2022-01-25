<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-22 18:19:14 CST
 *  Description:     ServerKernel.php's function description
 *  Version:         1.0.0.20180422-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-22 18:19:14 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Kernel;

use Capsheaf\Foundation\Request\Request;
use Capsheaf\Foundation\Response\Response;
use Capsheaf\Foundation\Router\Router;
use Exception;

class ServerKernel
{

    protected $m_router;


    public function __construct(Router $router)
    {
        $this->m_router = $router;
    }


    /**
     * 根据Request实例构建并返回Response（子）实例
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $response = null;
        try{
            $response = $this->toResponse($request);
        } catch (Exception $exception){
            throw $exception;
        }

        return $response;
    }


    /**
     * @param Request $request
     * @return Response
     */
    protected function toResponse(Request $request)
    {
        $response = $this->m_router->dispatch($request);

        return $response;
    }


    /**
     * 提供清理机会
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $this->m_router->terminateMiddleware($request, $response);
    }

}
