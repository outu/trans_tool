<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:35 CST
 *  Description:     AbstractSubscriber.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:35 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Event;

abstract class AbstractSubscriber
{

    protected $m_dispatcher;


    /**
     * AbstractSubscriber constructor. 使用Make自动注入定义的Dispatcher
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->m_dispatcher = $dispatcher;
    }


    /**
     * 快捷方法，快速添加事件和对应的本类中的定义的方法
     * @param string $sEventName
     * @param string $sMethod
     * @param int $nPriority 优先级
     */
    public function listen($sEventName, $sMethod, $nPriority = 0)
    {
        $this->m_dispatcher->listen($sEventName, [$this, $sMethod], $nPriority);
    }


    /**
     * 快捷方法，快速删除事件
     * @param string $sEventName
     */
    public function forget($sEventName)
    {
        $this->m_dispatcher->forget($sEventName);
    }


    /**
     * 可以在该函数中集中订阅（listen）多个事件，更为方便
     * DispatcherInterface::addSubscriber()时调用它进行注册，函数中可以直接用this->listen()包装函数即可;
     * @return $this
     */
    abstract public function subscribe();

}
