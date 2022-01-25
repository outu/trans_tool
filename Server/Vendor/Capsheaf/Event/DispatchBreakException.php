<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-09-29 11:18:02 CST
 *  Description:     DispatchBreakException.php's function description
 *  Version:         1.0.0.20180929-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-09-29 11:18:02 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Event;

use Exception;

final class DispatchBreakException extends Exception
{

    protected $m_meta;


    public function __construct($meta = null)
    {
        $this->m_meta = $meta;
        parent::__construct('', 0, null);
    }


    /**
     * 在Dispatch树状节点的终端节点（页节点）中进行调用：
     * 通过异常的形式进行快速收敛调用栈到初始的第一个dispatch调用(主要是用在cli程序中防止调用栈过深和芜杂的return语句)
     * @param mixed|null $meta 携带的自定义类型的数据
     * @throws DispatchBreakException
     */
    public static function makeBreakThrow($meta = null)
    {
        throw new static($meta);
    }


    public function setMeta($meta)
    {
        $this->m_meta = $meta;
    }


    public function getMeta()
    {
        return $this->m_meta;
    }


    /**
     * 检查是否Dispatch异常，进行判断是否继续抛出
     * @param Exception $exception
     * @param bool $bStripSelf 是否剔除该异常类
     * @param bool $bHelpThrowOthers 是否帮助抛出其它类型的异常
     * @return bool true表示是该异常类的类型
     * @throws DispatchBreakException|Exception
     */
    public static function filterThrow(Exception $exception, $bStripSelf = true, $bHelpThrowOthers = true)
    {
        if ($exception instanceof static){
            if (!$bStripSelf){
                throw $exception;
            }

            return true;
        }

        if ($bHelpThrowOthers) {
            throw $exception;
        }

        return false;
    }

}
