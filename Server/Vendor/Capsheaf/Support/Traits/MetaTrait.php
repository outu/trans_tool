<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:34 CST
 *  Description:     AbstractMetaTrait.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:34 CST initialized the file
 ******************************************************************************/


namespace Capsheaf\Support\Traits;

use BadMethodCallException;
use Closure;

/**
 * 提供给一个类添加动态方法的功能（实例调用和静态调用）<br/>
 * $hello = new Hello;<br/>
 * Hello::addMethod('sayHi', function(){<br/>
 *      echo "Hello";<br/>
 * });<br/>
 *
 * $hello->sayHi();<br/>
 * Hello::sayHi();<br/>
 */
trait MetaTrait
{

    /**
     * 注册的宏函数
     * @var array
     */
    protected static $m_arrMethods = [];


    /**
     * 注册宏函数
     * @param $sMethodName
     * @param callable $fnMethod
     */
    public static function addMethod($sMethodName, callable $fnMethod)
    {
        static::$m_arrMethods[$sMethodName] = $fnMethod;
    }


    /**
     * 判断宏函数是否注册过
     * @param $sMethodName
     * @return bool
     */
    public static function hasMacro($sMethodName)
    {
        return isset(static::$m_arrMethods[$sMethodName]);
    }


    public function __call($sMethodName, $arrArguments)
    {
        if (!static::hasMacro($sMethodName)) {
            throw new BadMethodCallException("Method ".get_class()."->{$sMethodName}() does not exists.");
        }

        if (static::$m_arrMethods[$sMethodName] instanceof Closure) {

            /**
             * 将这个函数绑定到类/对象上
             * http://php.net/manual/en/closure.bind.php
             * public Closure Closure::bindTo ( object $newthis [, mixed $newscope = "static" ] )
             * 第一个参数是将闭包新绑定到的对象上，NULL表示不绑定
             * 第二个参数是指定绑定到哪个作用于上，static可以通过$this->key访问public成员，访问私有和保护成员需要将这个参数指定为类或者类对象，类对象自动转换为类名
             * 返回一个 new Closure 对象或者 FALSE on failure
             */
            return call_user_func_array(static::$m_arrMethods[$sMethodName]->bindTo($this, get_called_class()), $arrArguments);//注意使用static延迟绑定到实际继承对象名上
        }

        return call_user_func_array(static::$m_arrMethods[$sMethodName], $arrArguments);
    }


    public static function __callStatic($sMethodName, $arrArguments)
    {
        if (!static::hasMacro($sMethodName)) {
            throw new BadMethodCallException("Method {$sMethodName} does not exists.");
        }

        if (static::$m_arrMethods[$sMethodName] instanceof Closure) {

            /**
             * public static Closure bind ( Closure $closure , object $newthis [, mixed $newscope = "static" ] )参考上面bindTo
             */
            return call_user_func_array(Closure::bind(static::$m_arrMethods[$sMethodName], null, get_called_class()), $arrArguments);//注意第二个参数表示闭包中的$this不绑定到任何对象上，第三个参数表示使用类作用域
        }

        return call_user_func_array(static::$m_arrMethods[$sMethodName], $arrArguments);
    }

}
