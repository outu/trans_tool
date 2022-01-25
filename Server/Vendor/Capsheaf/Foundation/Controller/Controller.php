<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-22 10:36:56 CST
 *  Description:     Controller.php's function description
 *  Version:         1.0.0.20180422-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-22 10:36:56 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Controller;

use BadMethodCallException;
use Capsheaf\Application\Application;
use Capsheaf\Foundation\Middleware\ControllerMiddleware;
use Capsheaf\Foundation\Middleware\ControllerMiddlewareOptions;
use Capsheaf\Foundation\Request\Request;
use Capsheaf\Foundation\Response\Http\JsonResponse;
use Closure;

class Controller
{

    protected $m_app;
    protected $m_request;

    /**
     * 控制器中间件列表
     * @var ControllerMiddleware[]
     */
    protected $m_arrMiddleware = [];


    public function __construct(Application $app, Request $request)
    {
        $this->m_app = $app;
        $this->m_request = $request;
    }


    /**
     * 为该控制器添加中间件
     * @param array|string|Closure $arrMiddleware 中间件类名（含handle函数），类名数组，也可以直接为闭包，或者已经实例化后的中间件
     * @param array $arrOptions
     * @return ControllerMiddlewareOptions
     */
    public function addMiddleware($arrMiddleware, $arrOptions = [])
    {
        foreach ((array)$arrMiddleware as $middleware){
            $this->m_arrMiddleware[] = [
                'middleware'    => $middleware,
                'options'       => &$arrOptions,
            ];
        }

        //用于支持设置中间件的同时设置适用和排除的方法，eg：$controller->addMiddleware()->only('login');
        return new ControllerMiddlewareOptions($arrOptions);
    }


    /**
     * 获取该控制器设置的中间件列表
     * @return ControllerMiddleware[]
     */
    public function getMiddleware()
    {
        return $this->m_arrMiddleware;
    }


    /**
     * 模板方法：赋值模板变量
     * @param string|array $key
     * @param mixed $value
     * @return Controller
     */
    public function assign($key, $value = null)
    {
        return $this;
    }


    /**
     * 模板方法：渲染模板
     * @param string $sTemplate
     * @return Controller
     */
    public function display($sTemplate)
    {
        return $this;
    }


    /**
     * 返回json数据
     * @param array $arrData
     * @return JsonResponse
     */
    public function json($arrData = [])
    {
        return new JsonResponse($arrData);
    }


    /**
     * 返回表示成功的JSON
     * @param null|array|mixed $arrData
     * @return JsonResponse
     */
    public function success($arrData = null)
    {
        //默认值
        $arrJsonData = $arrData;

        if (!isset($arrData['code'])){
            $arrJsonData = [
                'code'      => 200,
                'message'   => 'Ok',
            ];

            if (!empty($arrData)){
                $arrJsonData['data'] = $arrData;
            }
        }

        return $this->json($arrJsonData);
    }


    /**
     * 返回表示失败的JSON
     * @param int $nCode
     * @param string $sMessage
     * @param null|array|mixed $arrData
     * @return JsonResponse
     */
    public function error($nCode = 400, $sMessage = '', $arrData = null)
    {
        //默认值
        $arrJsonData = $arrData;

        if (!isset($arrData['code'])){
            $arrJsonData = [
                'code'      => $nCode,
                'message'   => $sMessage,
            ];

            if (!empty($arrData)){
                $arrJsonData['data'] = $arrData;
            }
        }

        return $this->json($arrJsonData);
    }


    /**
     * 防止对未定义方法的调用
     * @param string $sMethod
     * @param array $arrArguments
     */
    public function __call($sMethod, $arrArguments)
    {
        throw new BadMethodCallException("Method [{$sMethod}] does not exists in controller:".get_class($this).".");
    }

}
