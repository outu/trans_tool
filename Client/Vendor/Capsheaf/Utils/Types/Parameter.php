<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:06:15 CST
 *  Description:     Parameter.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:06:15 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Types;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Parameter implements ArrayAccess, Countable, IteratorAggregate
{

    /**
     * 内部参数数组
     * @var array
     */
    protected $m_arrParameters = [];


    /**
     * Parameter constructor.
     * @param array $arrParameters 设置初始化的参数列表
     */
    public function __construct($arrParameters = [])
    {
        $this->m_arrParameters = $arrParameters;
    }


    /**
     * 获取对应键的值
     * @param string $sKey
     * @param null|mixed $default
     * @return mixed|null
     */
    public function get($sKey, $default = null)
    {
        return array_key_exists($sKey, $this->m_arrParameters) ? $this->m_arrParameters[$sKey] : $default;
    }


    /**
     * 返回全部参数的数组
     * @return array
     */
    public function all()
    {
        return $this->m_arrParameters;
    }


    /**
     * 设置对应键的值
     * @param string $sKey
     * @param $value
     */
    public function set($sKey, $value)
    {
        $this->m_arrParameters[$sKey] = $value;
    }


    /**
     * 添加（存在则替换）新的键值到原数组中，要是原数组中存在对应键则对其进行替换
     * @param array $arrParameters
     */
    public function add($arrParameters = [])
    {
        $this->m_arrParameters = array_replace($this->m_arrParameters, $arrParameters);
    }


    /**
     * 删除对应键
     * @param $sKey
     */
    public function remove($sKey)
    {
        unset($this->m_arrParameters[$sKey]);
    }


    /**
     *判断是否存在对应的键
     * @param $sKey
     * @return bool
     */
    public function has($sKey)
    {
        return array_key_exists($sKey, $this->m_arrParameters);
    }


    /**
     * 获取全部的键名
     * @return array
     */
    public function keys()
    {
        return array_keys($this->m_arrParameters);
    }


    /**
     * 获取参数的个数
     * @return int
     */
    public function count()
    {
        return count($this->m_arrParameters);
    }


    /**
     * 支持遍历
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->m_arrParameters);
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
