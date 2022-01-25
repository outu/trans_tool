<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-23 11:14:58 CST
 *  Description:     ControllerMiddlewareOptions.php's function description
 *  Version:         1.0.0.20180423-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-23 11:14:58 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Middleware;

class ControllerMiddlewareOptions
{

    /**
     * 中间件选项
     * @var array
     */
    protected $m_arrOptions;


    /**
     * ControllerMiddlewareOptions constructor.
     * @param array $arrOptions
     */
    public function __construct(&$arrOptions = [])
    {
        $this->m_arrOptions = &$arrOptions;
    }


    /**
     * 设置前面的控制器中间件仅仅适用于参数指定的方法列表
     * @param array|string $arrMethods 方法列表，支持【'aFun'】，【['aFun','bFun']】，【'aFun','bFunc',...】三种形式
     * @return $this
     */
    public function only($arrMethods)
    {
        $this->m_arrOptions['only'] = is_array($arrMethods) ? $arrMethods : func_get_args();

        return $this;
    }


    /**
     * 设置前面的控制器中间件需要排除参数指定的方法列表
     * @param array|string $arrMethods 方法列表，支持【'aFun'】，【['aFun','bFun']】，【'aFun','bFunc',...】三种形式
     * @return $this
     */
    public function except($arrMethods)
    {
        $this->m_arrOptions['except'] = is_array($arrMethods) ? $arrMethods : func_get_args();

        return $this;
    }


    /**
     * 判断指定的方法是否在中间件中运行
     * @param string $sMethodName
     * @param array $arrOptions 中间件配置数组，如：['only' => [], 'except' => []]
     * @return bool
     */
    public static function isAllowed($sMethodName, $arrOptions = [])
    {
        //首先判断except是否
        if (!empty($arrOptions['except']) && in_array($sMethodName, $arrOptions['except'])){
            return false;
        } elseif (isset($arrOptions['only']) && in_array($sMethodName, $arrOptions['only'])){
            return true;
        }

        //其它情况都是看作允许的
        return true;
    }

}
