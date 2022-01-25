<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:03:00 CST
 *  Description:     Container.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:03:00 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Application;

use Closure;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ContainerInterface
{

    /**
     * 容器类单实例
     * @var null
     */
    protected static $m_oSelfInstance = null;

    /**
     * @var array 实例和单例模式实例化后的对象的绑定
     */
    protected $m_arrInstances = [];

    /**
     * @var array 单例，工厂，参数的绑定
     */
    protected $m_arrBindings = [];

    /**
     * @var array 根据类名构建时的链信息，一级一级深入
     */
    protected $m_arrBuildStack = [];

    /**
     * make实例时使用的参数栈
     * @var array
     */
    protected $m_arrMakeParametersStack = [];

    /**
     * @var array 容器里的即时绑定
     */
    protected $m_arrImmediateBindings = [];

    /**
     * 存储的形式为抽象=>实际，可以形成链，实例化会依次查找
     * @var array
     */
    protected $m_arrAliases = [];


    /**
     * 容器类是一个单例
     * @return static
     */
    public static function getInstance()
    {
        //self表示Container类，static可以表示继承Container的类
        if (is_null(static::$m_oSelfInstance)) {
            static::$m_oSelfInstance = new static;
        }

        return static::$m_oSelfInstance;
    }


    /**
     * 将静态的容器实例设置为子对象
     * @param ContainerInterface|null $container
     * @return ContainerInterface
     */
    public static function setInstance(ContainerInterface $container = null)
    {
        return static::$m_oSelfInstance = $container;
    }


    public function singleton($sAbstract, $concrete = null)
    {
        $this->bind($sAbstract, $concrete, true);
    }


    public function factory($sAbstract, $concrete = null)
    {
        $this->bind($sAbstract, $concrete, false);
    }


    public function instance($sAbstract, $oInstance)
    {
        $this->m_arrInstances[$sAbstract] = $oInstance;
    }


    /**
     * 判断是否绑定，可以用于判定该抽象/别名是否可以被容器解析
     * @param string $sAbstract
     * @return bool
     */
    public function isBound($sAbstract)
    {
        return  isset($this->m_arrBindings[$sAbstract]) ||
                isset($this->m_arrInstances[$sAbstract])||
                $this->isAlias($sAbstract);
    }


    public function offsetExists($sAbstract)
    {
        return $this->isBound($sAbstract);
    }


    /**
     * 同make
     * @param mixed $sAbstract
     * @return mixed
     */
    public function offsetGet($sAbstract)
    {
        return $this->make($sAbstract);
    }


    /**
     * 同工厂模式，非单例模式
     * @param mixed $sAbstract
     * @param mixed $concrete 可以绑定具体的值和闭包在该容器上
     */
    public function offsetSet($sAbstract, $concrete)
    {
        $this->factory(
            $sAbstract,
            $concrete instanceof Closure
                ? $concrete
                : function () use ($concrete) {
                    return $concrete;
                }
        );
    }


    /**
     * 取消绑定键
     * @param mixed $sAbstract
     */
    public function offsetUnset($sAbstract)
    {
        $this->unBind($sAbstract);
    }


    /**
     * 取消绑定
     * @param string $sAbstract
     */
    public function unBind($sAbstract)
    {
        //即使下标中的元素不存在也是OK的
        unset($this->m_arrBindings[$sAbstract], $this->m_arrInstances[$sAbstract], $this->m_arrAliases[$sAbstract]);
    }


    /**
     * 绑定实现【回调函数】或【具体类】到【抽象】，此处并不实例化，只供今后的实例化时使用
     * @param string $sAbstract 要绑定的抽象串
     * @param \Closure|string|null $concrete 可以绑定回调函数（格式限定为$oContainer, $arrParameters = []），或者抽象的具体实现类，或者什么都不传就是绑定抽象同名类
     * @param bool $bShared 单例模式绑定吗
     */
    protected function bind($sAbstract, $concrete = null, $bShared = false)
    {
        $this->unBind($sAbstract);

        //为空则绑定自身抽象
        if (is_null($concrete)) {
            $concrete = $sAbstract;
        }

        //若实现参数不是回调函数，则为类名，为了统一根据类名生成一个默认的回调函数，这样全部的绑定可以统一形式
        if (!$concrete instanceof \Closure) {
            $concrete = $this->getClosure($sAbstract, $concrete);
        }

        $this->m_arrBindings[$sAbstract] = [
            'fnConcrete' => $concrete,
            'bShared' => $bShared
        ];
    }


    /**
     * @param string $sAbstract
     * @return mixed
     */
    public function make($sAbstract)
    {
        return $this->resolve($sAbstract);
    }


    /**
     * @param string $sAbstract
     * @param array $arrParameters make时传入这个数组表示在构造函数解析不到依赖或者不存在时进行指定,也可以进行同名覆盖，如构造函数中有个原始的参数是$arrParameters = []，那么需要指定一个数组['arrParameters'=>$arrSomeThings]
     * @return mixed
     */
    public function makeWith($sAbstract, array $arrParameters)
    {
        return $this->resolve($sAbstract, $arrParameters);
    }


    /**
     * 解析实例对象
     * @param $sAbstract
     * @param array $arrParameters
     * @return mixed
     * @throws ContainerException
     */
    protected function resolve($sAbstract, $arrParameters = [])
    {
        $sAbstract = $this->getAlias($sAbstract);

        //首先返回可能存在的单实例，由于一开始已经绑定，单实例不带参数
        if (isset($this->m_arrInstances[$sAbstract])) {
            return $this->m_arrInstances[$sAbstract];
        }

        $this->m_arrMakeParametersStack[] = $arrParameters;

        //要是该抽象绑定过，则返回成员变量中存储的回调函数，没有绑定过则直接使用抽象串
        $concrete = $this->getConcrete($sAbstract);

        if ($this->isBuildable($sAbstract, $concrete)) {
            if (isset($this['log'])){
                $this['log']->debug("Resolving: {$sAbstract}");
            }

            //要是绑定的为可实例化的则构建
            $oObject = $this->build($concrete);
        } else {
            //要是绑定的不能直接实例化则继续递归直到可以实例化为止
            $oObject = $this->make($concrete);
        }

        //单实例绑定则直接将实例化的对象放置到实例成员变量中，make时优先从这个实例中取出
        if ($this->isShared($sAbstract)) {
            $this->m_arrInstances[$sAbstract] = $oObject;
        }

        array_pop($this->m_arrMakeParametersStack);

        return $oObject;
    }


    /**
     * 从【闭包函数】或者【实际的类名】来实例化出对象,注意和make的区别时make传入的是【抽象名】
     * @param \Closure|string $concrete 闭包函数或者实际的类名
     * @return mixed 返回通过闭包函数或者实际的类名来实例化后的对象
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function build($concrete)
    {
        //若为闭包实现，则调用闭包即可
        if ($concrete instanceof \Closure) {
            //传入的闭包函数统一参数格式为(容器，参数),获取makeWith传入的第二个参数
            return $concrete($this, $this->getLastMakeWithParametersArray());
        }

        //不是上述的闭包，那么就是类名（字符串）了
        $sConcreteClass = $concrete;

        //根据类名称的反射来获取类的信息
        $oReflector = new ReflectionClass($sConcreteClass);

        //若该类不能实例化（是一个不存在的类字符串）则抛出异常
        if (!$oReflector->isInstantiable()) {
            return $this->handleNotInstantiable($sConcreteClass);
        }

        //将当前要构建的类名称记录入容器构建栈成员
        $this->m_arrBuildStack[] = $sConcreteClass;

        //获取类的构造函数
        $oFnConstructor = $oReflector->getConstructor();

        //要是该类没有构造函数，则可以立即根据类名称字符串进行new来实例化一个对象并返回
        if (is_null($oFnConstructor)) {

            array_pop($this->m_arrBuildStack);

            //因为构造函数没有参数，直接返回实例化的对象
            return new $sConcreteClass;
        }

        //获取类的参数,注意返回的类型为ReflectionParameter对象类型
        //http://php.net/manual/zh/reflectionfunctionabstract.getparameters.php
        $arrReflectionParameters = $oFnConstructor->getParameters();

        //根据获取到的构造函数的参数继续实例化
        $arrParametersGot = $this->resolveDependencies($arrReflectionParameters);

        //弹出栈
        array_pop($this->m_arrBuildStack);

        //返回实例化的参数
        return $oReflector->newInstanceArgs($arrParametersGot);
    }


    /**
     * 判断makeWith函数传入的参数中是否有需要的手动绑定或者覆盖情况
     * @param ReflectionParameter $parameter
     * @return bool
     */
    protected function existsInLastMakeWithParametersArray(ReflectionParameter $parameter)
    {
        return array_key_exists($parameter->name, $this->getLastMakeWithParametersArray());
    }


    /**
     * 获取当前makeWith函数传入的参数
     * @return array|mixed 返回一个数组，它是makeWith传入的第二个参数，它是一个数组，每个键值对对应一个要指定或者覆盖的构造函数实参
     */
    protected function getLastMakeWithParametersArray()
    {
        return count($this->m_arrMakeParametersStack) ? end($this->m_arrMakeParametersStack) : [];
    }


    /**
     * 从makeWith函数传入的参数数组中获取设定的值
     * @param ReflectionParameter $parameter
     * @return mixed
     */
    protected function getLastMakeWithParameter(ReflectionParameter $parameter)
    {
        $arrParameters = $this->getLastMakeWithParametersArray();
        return $arrParameters[$parameter->name];
    }


    protected function getImmediateBindingConcrete($sAbstract)
    {

    }


    /**
     * 抛出不能实例化的异常
     * @param $concrete
     * @throws ContainerException
     */
    protected function handleNotInstantiable($concrete)
    {
        //最好加上链信息
        $sMessage = "Target [$concrete] is not instantiable.";
        throw new ContainerException($sMessage);
    }


    /**
     * 根据ReflectionParameter参数来实例化出每个参数依赖的实例或者参数
     * @param array $arrReflectionParameters ReflectionParameters数组
     * @return array
     * @throws ContainerException
     */
    protected function resolveDependencies(array $arrReflectionParameters)
    {
        $resolvedParameters = [];
        foreach ($arrReflectionParameters as $oReflectionParameter) {

            /**
             * 判断抽象对应的构造函数依赖的【类】或【原生类型】是否在makeWith的函数中没有就指定的，或者想要覆盖的已经绑定的抽象实现
             * makeWith('AbstractClassName', [
             *      'userDefinedParamInMakeWith' => 'NOTEXISTS',
             *      'AlreadyBoundClassName' => new AlreadyBoundClassName($other),
             * ]);
             *
             */
            if ($this->existsInLastMakeWithParametersArray($oReflectionParameter)){
                $resolvedParameters[] = $this->getLastMakeWithParameter($oReflectionParameter);
                continue;
            }


            //getClass返回type hint的class，不是class则返回null
            $resolvedParameters[] = is_null($sClass = $oReflectionParameter->getClass())
                ? $this->resolvePrimitive($oReflectionParameter)//要是不是类（PHP自带类型的参数等）
                : $this->resolveClass($oReflectionParameter);//要是参数是类的话，通过类来实例化
        }

        return $resolvedParameters;
    }


    /**
     * 解析构造函数中依赖的参数类型为PHP原生类型的值（已经排除MakeWith提前指定的参数），特别注意的是若类型为闭包，则这里需要实际调用一下
     * @param ReflectionParameter $oReflectionParameter
     * @return mixed
     * @throws
     */
    protected function resolvePrimitive(ReflectionParameter $oReflectionParameter)
    {
        //返回默认值
        if ($oReflectionParameter->isDefaultValueAvailable()) {
            return $oReflectionParameter->getDefaultValue();
        }

        //处理异常
        $this->handleUnresolvablePrimitive($oReflectionParameter);
    }


    /**
     * 抛出不能解析PHP原生类型的异常
     * @param ReflectionParameter $oReflectionParameter
     * @throws ContainerException
     */
    protected function handleUnresolvablePrimitive(ReflectionParameter $oReflectionParameter)
    {
        //最好加上链信息
        $sMessage = "Unresolvable dependency while resolving parameter:[$".$oReflectionParameter->getName()."] of class [{$oReflectionParameter->getDeclaringClass()->getName()}].";
        throw new ContainerException($sMessage);
    }


    /**
     * 解析构造函数中依赖的参数类型为类类型的实例
     * @param ReflectionParameter $oReflectionParameter
     * @return mixed
     * @throws Exception
     */
    protected function resolveClass(ReflectionParameter $oReflectionParameter)
    {
        try {
            //尝试Make
            return $this->make($oReflectionParameter->getClass()->name);
        } catch (Exception $exception) {
            //要是是因为可选参数，则获取默认值
            if ($oReflectionParameter->isOptional()) {
                return $oReflectionParameter->getDefaultValue();
            }

            //继续抛出
            throw $exception;
        }
    }


    /**
     * 从绑定成员变量中的获取绑定了的抽象对应的具体的【回调函数】
     * @param string $sAbstract 绑定过的抽象字符串
     * @return string|Closure
     */
    protected function getConcrete($sAbstract)
    {
        if (isset($this->m_arrBindings[$sAbstract])) {
            return $this->m_arrBindings[$sAbstract]['fnConcrete'];
        }

        //没有绑定过，那么直接返回抽象的字符串
        return $sAbstract;
    }


    public function getAlias($sAbstract)
    {
        //要是未找到注册了的别名，则直接返回该抽象名称
        if (!isset($this->m_arrAliases[$sAbstract])){
            return $sAbstract;
        }

        if ($this->m_arrAliases[$sAbstract] === $sAbstract) {
            throw new LogicException("[{$sAbstract}] is aliased to self.");
        }

        //找到别名最后实际的抽象
        return $this->getAlias($this->m_arrAliases[$sAbstract]);
    }


    public function isAlias($sAliasName)
    {
        return isset($this->m_arrAliases[$sAliasName]);
    }


    /**
     * bind()时传入的实现参数不直接是回调函数而是类名称字符串的的话，就按照形式返回一个默认的回调函数
     * @param string $sAbstract 抽象名称
     * @param string $sConcrete 实现为类名称字符串
     * @return \Closure
     */
    protected function getClosure($sAbstract, $sConcrete)
    {
        $app = $this;

        //注意$arrParameters这个参数用户没有机会使用
        return function ($oContainer, $arrParameters = []) use ($sAbstract, $sConcrete, $app) {
            //如何处理带构造参数的情况

            //直接return new $sConcrete有误，这样的话就不能再次向下解析了;
            if ($sAbstract == $sConcrete) {
                return $app->build($sConcrete);
            }

            //$sConcrete是类名称字符串,$oContainer统一实际传入都是容器类实例
            return $oContainer->makeWith($sConcrete, $arrParameters);
        };
    }


    /**
     * 判断绑定的抽象是否是共享的
     * @param $sAbstract
     * @return bool
     */
    public function isShared($sAbstract)
    {
        //若为绑定的实例或者绑定的抽象为共享/Factory
        return isset($this->m_arrInstances[$sAbstract])
            || (isset($this->m_arrBindings[$sAbstract]['bShared']) && $this->m_arrBindings[$sAbstract]['bShared'] === true);
    }


    /**
     * 根据抽象和实现来综合判断是否可以实例化
     * @param string $sAbstract
     * @param string|Closure $concrete
     * @return bool
     */
    public function isBuildable($sAbstract, $concrete)
    {
        return ($concrete === $sAbstract || $concrete instanceof \Closure);
    }


    /**
     * 提供【容器对象->属性=值|闭包】的设置方式，与$container[''] = $var的设置方式作用完全一致
     * @param $sAbstract
     * @param $concrete
     */
    public function __set($sAbstract, $concrete)
    {
        $this[$sAbstract] = $concrete;
    }


    /**
     * 提供【$var = 容器对象->属性】的访问方式，与$var = $container['']的获取方式获取到的内容完全一致
     * @param $sAbstract
     * @return mixed
     */
    public function __get($sAbstract)
    {
        return $this[$sAbstract];
    }


    /**
     * 清理容器已经绑定的抽象
     */
    public function clearContainer()
    {
        $this->m_arrInstances = [];
        $this->m_arrBindings = [];
    }

}
