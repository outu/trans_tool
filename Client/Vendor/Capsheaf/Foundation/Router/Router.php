<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-22 10:38:08 CST
 *  Description:     Route.php's function description
 *  Version:         1.0.0.20180422-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-22 10:38:08 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Router;

use Capsheaf\Application\Application;
use Capsheaf\Foundation\Controller\ActionNotFoundException;
use Capsheaf\Foundation\Controller\Controller;
use Capsheaf\Foundation\Controller\ControllerNotFoundException;
use Capsheaf\Foundation\Middleware\ControllerMiddlewareOptions;
use Capsheaf\Foundation\Pipeline\Pipeline;
use Capsheaf\Foundation\Request\Api\ApiRequest;
use Capsheaf\Foundation\Request\Request;
use Capsheaf\Foundation\Response\Api\ApiResponse;
use Capsheaf\Foundation\Response\Http\HttpResponse;
use Capsheaf\Foundation\Response\Http\JsonResponse;
use Capsheaf\Foundation\Response\Response;
use Closure;
use Exception;
use ReflectionMethod;

class Router
{

    protected $m_app;

    /**
     * 当前的请求对象
     * @var Request
     */
    protected $m_currentRequest;

    /**
     * 当前的响应对象
     * @var Response
     */
    protected $m_currentResponse;

    /**
     * 当前处理请求使用的控制器
     * @var Controller
     */
    protected $m_currentController;

    /**
     * 控制器根目录
     * @var string
     */
    protected $m_sControllerNameSpace;


    /**
     * Router constructor.
     * @param Application $app
     * @param string $sControllerNameSpace 全部控制器所在的根目录，用于根据命名空间前缀查找到对应的控制器，如：CapsheafServer\Modules\
     */
    public function __construct(Application $app, $sControllerNameSpace = '')
    {
        $this->m_app = $app;
        $this->m_sControllerNameSpace = $sControllerNameSpace;
    }


    /**
     * @param Request $request
     * @return ApiResponse|HttpResponse|JsonResponse|null
     * @throws Exception
     */
    public function dispatch(Request $request)
    {
        $this->m_currentRequest = $request;
        $this->m_app->instance(
            'request',
            $this->m_currentRequest,
            ['Capsheaf\Foundation\Request\Request', get_class($request)]
        );

        try {
            $callResult = $this->runControllerThroughMiddleware($request);
            $response = $this->parseCallResult($request, $callResult);
            $this->m_currentResponse = $response;

        } catch (Exception $exception) {
            throw $exception;
        }

        return $response;
    }


    /**
     * 通过中间件包裹控制器，安装正确的次序运行控制器
     * @param Request $request
     * @return mixed|null
     */
    protected function runControllerThroughMiddleware(Request $request)
    {
        $sControllerClass = $this->getControllerClass();
        if (!class_exists($sControllerClass)){
            throw new ControllerNotFoundException("Controller class '{$sControllerClass}' not exists.");
        }

        $sAction = $this->getAction();
        $arrParameters = $this->getParameters();

        $controller = $this->m_app->make($sControllerClass);
        $this->m_currentController = $controller;

        $response = (new Pipeline($this->m_app))
            ->send($request)
            ->through($this->getControllerMiddleware($controller, $sAction))
            ->then($this->dispatchControllerCallback($controller, $sAction, $arrParameters));

        return $response;
    }


    /**
     * 组装控制器类（完整的命令空间限定的类）
     * @return string
     */
    protected function getControllerClass()
    {
        return $this->m_sControllerNameSpace.$this->getModule().'\\'.$this->getController().'Controller';
    }


    /**
     * 针对实际的Action获取控制器的中间件列表
     * @param Controller $controller
     * @param string $sAction
     * @return array
     */
    protected function getControllerMiddleware($controller, $sAction)
    {
        if (!method_exists($controller, $sAction)){
            return [];
        }

        $arrAllMiddleware = $controller->getMiddleware();

        $arrMiddlewareAllowed = [];
        foreach ($arrAllMiddleware as $middleware){
            if (ControllerMiddlewareOptions::isAllowed($sAction, $middleware['options'])){
                $arrMiddlewareAllowed[] = $middleware['middleware'];
            }
        }

        return $arrMiddlewareAllowed;
    }


    /**
     * 得到一个便于PIPELINE最终调用的回调函数
     * @param Controller $controller
     * @param string $sAction
     * @param array $arrParameters
     * @return Closure
     */
    protected function dispatchControllerCallback($controller, $sAction, $arrParameters)
    {
        return function (Request $request) use ($controller, $sAction, $arrParameters){
            $arrPassToFunction = [];
            $response = null;

            if (method_exists($controller, $sAction)){
                try {
                    $ref = new ReflectionMethod($controller, $sAction);
                    $arrFuncParameters = $ref->getParameters();
                    foreach ($arrFuncParameters as $parameter){
                        if (isset($arrParameters[$parameter->name])){
                            $arrPassToFunction[] = $arrParameters[$parameter->name];
                        } elseif (($class = $parameter->getClass()) && $this->m_app->isBound($class->name)) {
                            $arrPassToFunction[] = $this->m_app->make($class->name);
                        } elseif ($parameter->isDefaultValueAvailable()) {
                            $arrPassToFunction[] = $parameter->getDefaultValue();
                        } else {
                            $arrPassToFunction[] = null;
                        }
                    }

                    $response = call_user_func_array([$controller, $sAction], $arrPassToFunction);

                } catch (Exception $exception){
                    throw $exception;
                }
            } else {
                throw new ActionNotFoundException("Action {$sAction} of controller ".get_class($controller)." does not exists.");
            }

            return $response;
        };
    }


    /**
     * 从请求和返回的各种类型来推断实际的Response类型
     * @param Request $request
     * @param mixed $callResult
     * @return ApiResponse|HttpResponse|JsonResponse|null
     */
    protected function parseCallResult(Request $request, $callResult)
    {
        $response = null;

        //如果请求为ApiRequest，则全部的都需要转换为ApiResponse
        if ($request instanceof ApiRequest){
            if ($callResult instanceof ApiResponse){
                $response = $callResult;
            } else {
                $response = new ApiResponse($callResult);
            }

        } elseif ($callResult instanceof HttpResponse) {
            $response = $callResult;
        } else {
            $response = new JsonResponse($callResult);
        }

        $response->prepare($request);

        return $response;
    }


    /**
     * 提供结束中间件的机会
     * @param Request $request
     * @param Response $response
     */
    public function terminateMiddleware(Request $request, Response $response)
    {
        $arrMiddlewareAllowed = $this->getControllerMiddleware($this->m_currentController, $this->getAction());
        foreach ($arrMiddlewareAllowed as $middleware){

            //若为回调函数则不需要结束
            if (is_callable($middleware) || $middleware instanceof Closure){
                continue;
            }

            //若为类字符串则构建一个
            if (is_string($middleware)){
                $middleware = $this->m_app->make($middleware);
            }

            if (is_object($middleware) && method_exists($middleware, 'terminate')){
                $middleware->terminate($request, $response);
            }
        }
    }


    /**
     * 取得Module
     * @return string
     */
    protected function getModule()
    {
        return $this->m_currentRequest->getModule();
    }


    /**
     * 取得Controller（不带前面的命名空间）
     * @return string
     */
    protected function getController()
    {
        return $this->m_currentRequest->getController();
    }


    /**
     * 取得Action
     * @return string
     */
    protected function getAction()
    {
        return $this->m_currentRequest->getAction();
    }


    /**
     * 通过Request请求，取得Parameters
     * @return array
     */
    protected function getParameters()
    {
        return $this->m_currentRequest->getParameters();
    }

}
