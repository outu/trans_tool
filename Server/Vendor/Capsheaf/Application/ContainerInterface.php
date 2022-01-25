<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:03:11 CST
 *  Description:     ContainerInterface.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:03:11 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Application;

interface ContainerInterface extends \ArrayAccess
{

    /**
     * 获取存在的实例或者创建实例
     * @param string $sAbstract
     */
    public function make($sAbstract);


    /**
     * 获取存在的实例或者创建实例,同时可以指定构造函数的其它参数
     * @param string $sAbstract
     * @param array $arrParameters
     */
    public function makeWith($sAbstract, array $arrParameters);


    /**
     * 单例模式, 只返回同一个对象
     * @param string $sAbstract
     * @param callable|string $concrete 指定返回对象的闭包或者实际绑定的类名称，回调函数（格式限定为$oContainer, $arrParameters = []）
     * 不指定时自动使用第一个参数
     * @return mixed
     */
    public function singleton($sAbstract, $concrete = null);


    /**
     * 每次初始化一个，每次返回不同的对象
     * @param string $sAbstract
     * @param callable|string $concrete 指定返回对象的闭包或者实际绑定的类名称，回调函数（格式限定为$oContainer, $arrParameters = []）
     * 不指定时自动使用第一个参数
     * @return mixed
     */
    public function factory($sAbstract, $concrete = null);


    /**
     * 绑定已经实例化的对象到抽象
     * @param string $sAbstract 要绑定的抽象，可以是别名或者类名（注意命名空间不带\前缀），为类名时才可以实现make时自动解析构造函数参数
     * @param object $oInstance 要绑定的实例对象，注意这里不能指定回调函数
     * @return mixed
     */
    public function instance($sAbstract, $oInstance);


    /**
     * 查询抽象类型是否已经绑定
     * @param string $sAbstract
     * @return mixed
     */
    public function isBound($sAbstract);

}
