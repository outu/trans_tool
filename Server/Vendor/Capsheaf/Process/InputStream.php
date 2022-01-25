<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-10 22:18:52 CST
 *  Description:     InputStream.php's function description
 *  Version:         1.0.0.20180410-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-10 22:18:52 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process;

use InvalidArgumentException;
use Iterator;
use RuntimeException;

class InputStream implements Iterator
{

    /**
     * 添加的输出会形成队列
     * @var array
     */
    protected $m_arrQueuedInput = [];

    protected $m_nReadOffset = 0;

    /**
     * 每次队列为空时进行的回调函数
     * @var null|callable
     */
    protected $m_fnEmptyCallback = null;

    /**
     * 是否该input队列已经被Process开始读取了
     * @var bool
     */
    protected $m_bOpened = false;


    /**
     * 设置队列为空时触发的回调函数<br>
     * 如：<br>
     * $input = new InputStream();<br>
     * $input->onEmpty(function () use (&$i) { return ++$i; });<br>
     * @param null|callable $fnEmptyCallback
     * @return void
     */
    public function onEmpty($fnEmptyCallback = null)
    {
        $this->m_fnEmptyCallback = $fnEmptyCallback;
    }


    /**
     * 将要写的数据追加到队列中
     * @param mixed $input
     * @return void
     */
    public function write($input)
    {
        if ($input === null){
            return;
        }

        if ($this->isClosed()){
            throw new RuntimeException('Input is closed while trying to write stream.');
        }

        $this->m_arrQueuedInput[] = static::parseInput($input);
    }


    /**
     * 关闭该队列
     */
    public function close()
    {
        $this->m_bOpened = false;
    }


    /**
     * 判断该Input队列是否是关闭状态
     * @return bool
     */
    public function isClosed()
    {
        return !$this->m_bOpened;
    }


    /**
     * 获取不同的输入类型
     * @param mixed $input
     * @return mixed
     */
    public static function parseInput($input)
    {
        if ($input !== null){
            //只允许资源或者字符串（可转换）的类型传入
            if (is_resource($input) || is_string($input)){
                return $input;
            }
            //Scalar variables are those containing an 【integer, float, string or boolean】. Types 【array, object and resource】 are not scalar.
            if (is_scalar($input)){
                //integer, float, string or boolean都可以转换为字符串
                return (string)$input;
            }

            throw new InvalidArgumentException('Only parameter type of resource string and scalar is allowed.');
        }

        return $input;
    }


    /**
     * 获取当前队列中的输入
     * @return mixed
     */
    public function current()
    {
        //在获取元素的时候标记未已打开
        $this->m_bOpened = true;

        return isset($this->m_arrQueuedInput[0]) ? $this->m_arrQueuedInput[0] : null;
    }


    /**
     * 指向下一个input，并清除最早的那个input
     * @return void
     */
    public function next()
    {
        //在获取元素的时候标记未已打开
        $this->m_bOpened = true;
        if (isset($this->m_arrQueuedInput[0])){
            array_shift($this->m_arrQueuedInput);
        }

        if (!isset($this->m_arrQueuedInput[0])){
            if ($this->m_bOpened && is_callable($this->m_fnEmptyCallback)){
                $this->write(call_user_func($this->m_fnEmptyCallback, $this));
            }
        }
    }


    public function key()
    {
        //在获取元素的时候标记未已打开
        $this->m_bOpened = true;

        return isset($this->m_arrQueuedInput[0]) ? 0 : -1;
    }


    /**
     * 判断
     * @return bool
     */
    public function valid()
    {
        //在获取元素的时候标记未已打开
        $this->m_bOpened = true;

        return isset($this->m_arrQueuedInput[0]) ? true : false;
    }


    public function rewind()
    {
        //在获取元素的时候标记未已打开
        $this->m_bOpened = true;

        return;
    }

}
