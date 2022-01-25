<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-21 10:30:04 CST
 *  Description:     Config.php's function description
 *  Version:         1.0.0.20180421-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-21 10:30:04 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Config;

use ArrayAccess;
use Capsheaf\Utils\Types\Arr;

class Config implements ArrayAccess
{

    /**
     * @var array 配置的内容
     */
    protected $m_arrConfigs = [];


    /**
     * 可以指定一个已经存在的配置数组，后面将在这个基础上进行操作
     * @param array $arrExists
     */
    public function __construct($arrExists = [])
    {
        $this->merge($arrExists);
    }


    /**
     * 将额外的配置数组添加到对象中
     * @param array $arrConfigs 要Merge的数组
     * @param bool $bNewConfigAsBase 是否不以现存的m_arrConfigs为基础而以将要Merge的数组作为基础
     * @return array 返回Merge后的数组
     */
    public function merge($arrConfigs = [], $bNewConfigAsBase = false)
    {
        if ($bNewConfigAsBase){
            //注意后面的会覆盖前面的，但数字键相同时不会
            return $this->m_arrConfigs = array_merge((array)$arrConfigs, $this->m_arrConfigs);
        }

        return $this->m_arrConfigs = array_merge($this->m_arrConfigs, (array)$arrConfigs);
    }


    /**
     * 判断配置中是否存在该键，或者设置了动态获取的成员函数getSomeKey()
     * @param string $sKey 支持【英文句号分隔的键名串】来访问多维数组深层次的键，如：‘port’，'database.user'
     * @param bool $bIncludeFunction 判断时是否使用函数的结果
     * @return bool 是否存在
     */
    public function has($sKey, $bIncludeFunction = true)
    {
        if (Arr::has($this->m_arrConfigs, $sKey)){
            return true;
        } elseif ($bIncludeFunction && method_exists($this, 'get'.ucfirst($sKey))){
            return true;
        }

        return false;
    }


    /**
     * 设置对应键的值，键名可以为键值对数组的形式同时设置多个，对于存在的则会进行覆盖
     * @param array|string $key 【要设置的键】或者直接是【键值对数组】
     * @param mixed $value 注意为键值对数组的形式时，该字段不使用
     * @return mixed
     */
    public function set($key, $value = null)
    {
        $arrKVs = is_array($key) ? $key : [$key => $value];
        foreach ($arrKVs as $k => $v){
            Arr::set($this->m_arrConfigs, $k, $v);
        }
    }


    /**
     * 添加（存在则替换）新的键值到原数组中，要是原数组中存在对应键则对其进行替换
     * @param array $arrConfigs
     */
    public function add($arrConfigs = [])
    {
        $this->m_arrConfigs = array_replace($this->m_arrConfigs, $arrConfigs);
    }


    /**
     * 删除对应键，不包含回调函数动态计算的键
     * @param $sKey
     */
    public function remove($sKey)
    {
        unset($this->m_arrConfigs[$sKey]);
    }


    /**
     * 获取对应键的值
     * @param string $sKey 键名
     * @param null|mixed $default 默认值
     * @return mixed|null
     */
    public function get($sKey, $default = null)
    {
        if(method_exists($this, 'get'.ucfirst($sKey))) {//如获取'someKey'的方法名称格式应该为getSomeKey(){};
            //传入键名和默认值
            return static::{'get'.ucfirst($sKey)}($sKey, $default);
        }

        return Arr::get($this->m_arrConfigs, $sKey, $default);
    }


    /**
     * 获取全部值
     * @return array
     */
    public function all()
    {
        return $this->m_arrConfigs;
    }


    /**
     * 修改对应键的数组，在获取到的数组的内部前方插入一个新的值并修改现有的配置
     * @param string $sKey
     * @param mixed $value
     * @return void
     */
    public function innerPrepend($sKey, $value)
    {
        $arrModify = $this->get($sKey);

        array_unshift($arrModify, $value);

        $this->set($sKey, $arrModify);
    }


    /**
     * 修改对应键的数组，在获取到的数组的内部后方追加一个新的值并修改现有的配置
     * @param string $sKey
     * @param mixed $value
     * @return mixed
     */
    public function innerPush($sKey, $value)
    {
        $arrModify = $this->get($sKey);

        $arrModify[] = $value;

        $this->set($sKey, $arrModify);
    }


    public function offsetExists($sKey)
    {
        return $this->has($sKey);
    }


    public function offsetGet($sKey)
    {
        return $this->get($sKey);
    }


    public function offsetSet($sKey, $value)
    {
        $this->set($sKey, $value);
    }


    public function offsetUnset($sKey)
    {
        $this->remove($sKey);
    }

}
