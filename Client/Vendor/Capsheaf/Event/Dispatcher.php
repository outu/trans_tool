<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:04:37 CST
 *  Description:     Dispatcher.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:04:37 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Event;

use Capsheaf\Application\Application;
use Capsheaf\Support\Traits\MetaTrait;
use Capsheaf\Utils\Types\Arr;
use Capsheaf\Utils\Types\Str;

class Dispatcher
{

    use MetaTrait;

    /**
     * @var Application
     */
    protected $m_app;

    /**
     * 存储普通事件与【处理回调函数，优先级】的关系
     * @var array
     */
    protected $m_arrListeners = [];

    protected $m_nAddedSequenceId = 0;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    /**
     * 开启事件监听，确保以后对应的dispatch时能够调用到对应的回调函数，注意的是事件和处理的回调函数对应关系是 【多对多】
     * @param string|array $eventNames 要绑定的事件名称，单个或者数组的形式指定，使用数组表示将多个不同的事件名称绑定到同一个事件
     * @param callable $fnHandleCallback 回调函数
     * @param int $nPriority 优先级，数字越大，越先执行
     */
    public function listen($eventNames, $fnHandleCallback, $nPriority = 0)
    {
        foreach ((array)$eventNames as $sEventName){
            //添加的同时自增ID加一来保证添加时的正确时序
            $this->m_arrListeners[$sEventName][] = [$fnHandleCallback, $nPriority, $this->m_nAddedSequenceId++];
        }
    }


    /**
     * 忘记绑定的事件处理回调函数
     * @param string $sEventName 事件名称
     */
    public function forget($sEventName)
    {
        if (!empty($this->m_arrListeners[$sEventName])){
            unset($this->m_arrListeners[$sEventName]);
        }
    }


    /**
     * 开始分发事件，并携带参数数组，立即调用绑定到该事件的那些函数（若某个处理函数返回严格false，就不再继续后续函数）
     * @param string $sEventName 事件名称，注意分发事件时不能使用广义匹配事件，广义匹配事件仅仅用在监听时
     * @param array|mixed &$arrEventData  数组中的内容会依次传递到指定的回调函数的每个参数，若传入的参数不为数组，则会将其作为回调函数的第一个参数传入，同时注意可以修改它，需要注意的是在php5.4及之后，回调函数的参数也必须为引用，否则修改不了
     * @param bool $bStopPropagationAfterGotReturn 当指定当遇到处理函数返回值时就停止事件传播的标记 且 遇到事件处理函数返回非空值时，则直接返回该处理函数返回值，不再进行后续操作
     * @return mixed|array 返回最后一个回调函数的返回值（上述停止标记&&非NULL时，不一定为数组）或者由每个事件回调函数处理的结果构成的数组
     */
    public function dispatch($sEventName, &$arrEventData = [], $bStopPropagationAfterGotReturn = false)
    {
        $arrHandledResults = [];

        //需要保留引用的能力，注意自己赋值给自己会存在问题，不要直接将修改的值赋值回原来的变量名
        if (!is_array($arrEventData)){
            $arrFunctionParameters = [&$arrEventData];
        } else {
            $arrFunctionParameters = &$arrEventData;
        }

        foreach ($this->getSortedListeners($sEventName) as $arrListener){
            $ret = call_user_func_array($arrListener[0], $arrFunctionParameters);

            //当指定当遇到处理函数返回值时就停止事件传播的标记 且 事件处理函数返回非空值时，则直接返回该处理函数返回值，不再进行后续操作
            if ($bStopPropagationAfterGotReturn && !is_null($ret)){
                return $ret;
            }

            //若某个处理函数返回严格false，就不再继续后续函数
            if ($ret === false){
                break;
            }

            //收集每个事件回调函数处理的结果
            $arrHandledResults[] = $ret;
        }

        return $arrHandledResults;
    }


    /**
     * 获取事件名称上绑定的回调函数列表（注意会直接更新成员变量，进行排序操作）
     * @param string $sEventName 事件名称（可以为匹配事件名）
     * @return array
     */
    protected function getSortedListeners($sEventName)
    {
        $arrGatheredListeners = [];

        foreach ($this->m_arrListeners as $sEachEventName => $arrListeners) {
            //$sEventNamePattern表示模式
            //$sEachEventName表示用来判断当前是否符合该模式的事件名称
            if ($this->isEventNameMatch($sEachEventName, $sEventName)){
                $arrGatheredListeners = array_merge($arrGatheredListeners, $this->m_arrListeners[$sEachEventName]);
            }
        }

        Arr::sortByFields(
            $arrGatheredListeners,
            [
                ['1', SORT_DESC],//优先级降序
                ['2', SORT_ASC],//序列号升序
            ]
        );

        return $arrGatheredListeners;
    }


    /**
     * 判断是否是匹配事件（根据*号是否存在）
     * @param string $sEventName
     * @return bool
     */
    protected function isWildcardEventName($sEventName)
    {
        return Str::contains($sEventName, '*');
    }


    /**
     * 判断提供的匹配事件是否和给定的事件名称匹配（事件格式为A.B.C.*.E）
     * @param string $sEventNameA
     * @param string $sEventNameB
     * @return bool
     */
    protected function isEventNameMatch($sEventNameA, $sEventNameB)
    {
        $arrAFragments = explode('.', $sEventNameA);
        $arrBFragments = explode('.', $sEventNameB);

        $nMaxLength = max(count($arrAFragments), count($arrBFragments));

        for ($nIndex = 0; $nIndex < $nMaxLength; $nIndex++)
        {
            $sBlockA = isset($arrAFragments[$nIndex]) ? $arrAFragments[$nIndex] : '*';
            $sBlockB = isset($arrBFragments[$nIndex]) ? $arrBFragments[$nIndex] : '*';

            if ($sBlockA == $sBlockB ||
                $sBlockA == '*' ||
                $sBlockB == '*'
            ){
                continue;
            } else {
                return false;
            }
        }

        return true;
    }


    /**
     * 添加一个订阅者，订阅者类在自己的subscribe中集中定义要关注的事件和对应的集合（例如使用Subscriber::listen方法）
     * @param string|AbstractSubscriber $subscriber
     * @return void
     */
    public function addSubscriber($subscriber)
    {
        if (is_string($subscriber)){
            $subscriber = $this->m_app->make($subscriber);
        }

        $subscriber->subscribe();
    }

}
