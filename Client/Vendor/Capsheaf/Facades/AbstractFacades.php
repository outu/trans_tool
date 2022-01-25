<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:54 CST
 *  Description:     AbstractFacades.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:54 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Facades;

use Capsheaf\Application\Application;
use RuntimeException;

abstract class AbstractFacades
{

    /**
     * @var Application
     */
    protected static $m_app;

    /**
     * @var array 实际的已经别解析了的Facade子对象数组
     */
    protected static $m_arrResolvedFacadeInstances;


    /**
     * 设置该Facade用来解析别名的容器
     * @param Application $app
     */
    public static function setContainer(Application $app)
    {
        static::$m_app = $app;
    }


    /**
     * 获取该Facade用来解析别名的容器
     * @return Application
     */
    public static function getContainer()
    {
        return static::$m_app;
    }


    public static function __callStatic($sMethod, $arrArguments)
    {
        $facadeInstance = static::resolveFacadeInstance(static::getFacadeAccessor());

        if (!$facadeInstance) {
            throw new RuntimeException('The facade does not has any assigned object.');
        }

        return call_user_func_array([$facadeInstance, $sMethod], $arrArguments);
    }


    /**
     * 子类应该定义这个方法，否则抛出异常
     * @return string|object 返回【容器[$name]】中的别名用来从容器中解析出别名对应的对象，或者直接指定一个现成的对象来使用
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException("The facade does not implement getFacadeAccessor method.");
    }


    /**
     * 通过Facade名称（也可以直接为实例）获取绑定的实例
     * @param mixed $name Facade别名名称或者直接指定的实例
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        //要是子类直接用getFacadeAccessor返回实例，那么就返回这个指定的实例
        if (is_object($name)){
            return $name;
        }

        //若绑定过就直接获取
        if (isset(static::$m_arrResolvedFacadeInstances[$name])){
            return static::$m_arrResolvedFacadeInstances[$name];
        }

        //从App中解析出实际的实例出来，并标记该别名名称已经被绑定
        return static::$m_arrResolvedFacadeInstances[$name] = static::$m_app[$name];
    }

    public static function debug($info) {
//        file_put_contents("/tmp/ws_0524", "[ -=-=-=+++++++++++++++++----==== ] {$info}\n", FILE_APPEND);
    }

}
