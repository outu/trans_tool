<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:48 CST
 *  Description:     helpers.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:48 CST initialized the file
 ******************************************************************************/

use Capsheaf\Application\Application;

if (!function_exists('app')){


    /**
     * 返回唯一的容器对象，传入参数表示make
     * @param null $sAbstract
     * @param array $arrParameters
     * @return mixed 无参数就返回容器本身，否则获取对应参数别名绑定到容器的对象
     */
    function app($sAbstract = null, $arrParameters = null)
    {
        if (is_null($sAbstract)){
            return Application::getInstance();
        }

        return empty($arrParameters)
            ? Application::getInstance()->make($sAbstract)
            : Application::getInstance()->makeWith($sAbstract, $arrParameters);
    }


}


if (!function_exists('value')){


    /**
     * 若是闭包则通过指定的闭包参数返回值，若为标量则直接返回
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }


}


if (!function_exists('env')){


    /**
     * 获取环境变量值，没有则指定默认值
     * @param $sKey
     * @param null $defaultValue 默认的值，可以为闭包，通过函数来返回值
     * @return array|false|string
     */
    function env($sKey, $defaultValue = null)
    {
        $value = getenv($sKey);

        if ($value === false) {
            return value($defaultValue);
        }

        return $value;
    }


}


if (!function_exists('make')){


    /**
     * Application容器make方法包装
     *
     * @param string $sAbstract
     * @param array $arrParameters
     * @return mixed
     */
    function make($sAbstract, $arrParameters = null)
    {
        return app($sAbstract, $arrParameters);
    }


}


if (!function_exists('windows_os')){


    /**
     * 判断是否是Windows系统
     * @return bool
     */
    function windows_os()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }


}


if (!function_exists('dispatch')){


    /**
     * @param $sEventName
     * @param array|mixed &$arrEventData 数组中的内容会依次传递到指定的回调函数的每个参数，若传入的参数不为数组，则会将其作为回调函数的第一个参数传入，同时注意可以修改它，需要注意的是在php5.4及之后，回调函数的参数也必须为引用，否则修改不了
     * @param bool $bStopPropagationAfterGotReturn
     * @return array
     */
    function dispatch($sEventName, &$arrEventData = [], $bStopPropagationAfterGotReturn = false)
    {
        return app('events')->dispatch($sEventName, $arrEventData, $bStopPropagationAfterGotReturn);
    }


}


if (!function_exists('add_action')){


    /**
     * 挂载钩子
     * @param string $sTag
     * @param mixed $arrArgs
     */
    function add_action($sTag, $arrArgs)
    {

    }


}


if (!function_exists('do_action')){


    /**
     * 执行钩子
     * @param mixed $arrArgs
     */
    function do_action($arrArgs)
    {

    }


}


if (!function_exists('add_filter')){


    /**
     * 挂载过滤钩子
     * @param string $sTag
     * @param Closure $fnFunction
     * @param int $nPriority
     * @param int $nAcceptedArgs
     */
    function add_filter($sTag, $fnFunction, $nPriority = 10, $nAcceptedArgs = 1)
    {

    }


}


if (!function_exists('apply_filters')){


    /**
     * 执行过滤钩子
     * @param mixed $arrArgs
     */
    function apply_filters($arrArgs)
    {

    }


}


