<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-23 14:39:31 CST
 *  Description:     Pipeline.php's function description
 *  Version:         1.0.0.20180423-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-23 14:39:31 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Pipeline;

use Capsheaf\Application\Application;
use Closure;
use RuntimeException;

class Pipeline
{

    protected $m_app;

    /**
     * 管道经过时调用的每个类的方法名称
     * @var string
     */
    protected $m_sPipeMethod = 'handle';

    /**
     * 交给管道处理的实际对象，经过管道中间件的预处理和后处理
     * @var mixed
     */
    protected $m_inputObject;

    /**
     * 管道的处理过程中，每个中间件节点的集合，可以为中间件类名称，或者一个回调函数（与中间件的处理函数参数一致）
     * @var array
     */
    protected $m_arrPipeStack = [];


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    /**
     * 指定管道经过时调用的每个类的方法名称
     * @param string $sPipeMethod 管道经过时调用的每个类的方法名称
     * @return $this
     */
    public function via($sPipeMethod)
    {
        $this->m_sPipeMethod = $sPipeMethod;

        return $this;
    }


    /**
     * 指定交给管道处理的实际对象，经过管道中间件的预处理和后处理
     * @param mixed $inputObject
     * @return $this
     */
    public function send($inputObject)
    {
        $this->m_inputObject = $inputObject;

        return $this;
    }


    /**
     * 指定管道的处理过程中，每个中间件节点的集合
     * @param string|array $pipeStack 中间件类名列表，可以的形式为单个数组及多个动态个数的字符串参数，字符串表示类名称，回调函数表示独立的函数（不需要弄成类的形式）
     * @return $this
     */
    public function through($pipeStack)
    {
        $this->m_arrPipeStack = is_array($pipeStack) ? $pipeStack : func_get_args();

        return $this;
    }


    /**
     * 根据中间件数组和核心处理回调函数V型处理send()中指定的数据
     * @param Closure $fnInnerCoreProcess 核心处理回调函数，仅有一个参数($inputObject)，如从Request->Response
     * @return mixed
     */
    public function then(Closure $fnInnerCoreProcess)
    {
        //array_reduce()第一个参数是反过来的中间件名称: 如['D','C','B','A']，第三个参数是传给第二个参数（它是一个回调函数，函数参数为上一次的返回值和本次元素取值）的首个参数。
        //第三个参数是核心的处理过程，类似洋葱的最内层，所以呈现出V型调用。如：Response = function dispatchToRouter(Request)
        //
        //
        //class Middleware
        //{
        //    public function handle(Request, Closure $fnNext){
        //          //前置操作
        //          $fnNext(Request);
        //          //后置操作
        //    }
        //}
        //
        //顺序解析：
        //针对每个元素都是返回的是回调函数：
        //首先核心函数是一个回调函数
        //
        //
        //First-> 使用初始化的第三个参数
        //-----------------------------------------------------
        //     返回 Func(Request){};记为【】函数
        //-----------------------------------------------------
        //
        //
        //紧接着到D元素
        //D->  执行Func(【】, D)
        //-----------------------------------------------------
        //     返回Func(Request) use (【】, D){
        //            return D::handle (Request, 【】){
        //                 //前置
        //                 【】(Request);
        //                 //后置
        //            }
        //    }
        //-----------------------------------------------------
        //注意上述步骤并未实际执行，只是生成了一个回调函数
        //
        //
        //。。。
        //直到A
        //A->  执行Func(【】, A)
        //+++++++++++++++++++++++++++++++++++++++++++++++++++++
        //     返回Func(Request) use (【】, A){
        //            return A::handle (Request, 【】){
        //                 //前置
        //                 【】(Request);
        //                 //后置
        //            }
        //    }
        //+++++++++++++++++++++++++++++++++++++++++++++++++++++
        //
        //上面加号之间就是array_reduce返回的最终结果，可以看出仍未回调函数，并未实际调用
        //
        //最后一步便是实际调用：
        //【】(Request);则达到了从A开始的洋葱/管道式调用
        //执行实际调用的具体步骤：
        //5.调用array_reduce的结果【】(Request);
        //4.即A的Func(Request)->实际执行A::handle
        //3.执行A的前置->调用B的结果【】(Request);
        //2.执行B的前置->调用C的结果【】(Request);
        //1.执行C的前置->调用D的结果【】(Request);
        //0.执行D的前置->调用First的结果【】(Request)返回Response;
        //1.执行D的后置->返回由D处理后的Response到C
        //2.执行C的后置->返回由C处理后的Response到B
        //3.执行B的后置->返回由B处理后的Response到A
        //4.执行A的后置->返回由A处理后的Response作为最终结果;
        //
        //
        //
        //注意$this->getCallbackWrapper()是一个函数调用，并不是一个回调函数，它的返回值才是期望的回调函数
        //
        $fnGeneratedStacks = array_reduce(
            array_reverse($this->m_arrPipeStack), $this->getCallbackWrapper(), $this->getInnerCoreCallbackWrapper($fnInnerCoreProcess)
        );

        //上面的array_reduce的结果还是一个回调函数，这个回调函数设置的V形的调用顺序，但是并未触发实际的调用
        return $fnGeneratedStacks($this->m_inputObject);
    }


    /**
     * 包装核心处理函数，这个函数实际没什么必要，包装函数主要原因是可以在子类中覆盖该方法，以增加类似错误处理的内容
     * @param Closure $fnFirstCallback
     * @return Closure
     */
    public function getInnerCoreCallbackWrapper(Closure $fnFirstCallback)
    {
        return function ($inputObject) use ($fnFirstCallback){
            return $fnFirstCallback($inputObject);
        };
    }


    /**
     * 改函数用于返回一个回调函数，这个回调函数作为array_reduce的第二个参数
     * @return Closure
     */
    public function getCallbackWrapper()
    {
        /**
         * 每次array_reduce处理一个元素都返回一个与上次结果关联的回调函数，那么同理最后的结果还是一个回调函数
         * @param \Closure $fnLastReturn 用来表示上一次reduce后生成的回调函数（包括核心回调函数），如First返回的【】，D返回的【】，A返回的【】，具体见上
         * @param string|Closure $pipeNode 这里表示对每个数组元素，它可以为中间件类名称或者直接就是一个回调函数
         * @return \Closure 返回【】作为下一轮array_reduce的第一个参数
         */
        return function ($fnLastReturn, $pipeNode){
            /**
             * 这个函数成为了::handle函数中的$fnNext(Request $request)定义，注意是定义，这里不包括核心处理函数，且参数只有一个如Request
             * @param mixed $inputObject $fnNext的输入如Request
             * @return mixed 返回handle(Request, $fnNext)
             */
            return function ($inputObject) use ($fnLastReturn, $pipeNode){

                if ($pipeNode instanceof Closure){
                    //传入的为一个回调函数，则直接使用这个回调函数来处理
                    //回调函数签名和格式应该为：（其实与中间件类::handle一样）
                    //function(Request, $fnNext){
                    //    前置操作
                    //    Response = $fnNext(Request);
                    //    后置操作
                    //    return Response;
                    //}
                    return $pipeNode($inputObject, $fnLastReturn);
                } elseif (!is_object($pipeNode)){
                    $sClassName = $pipeNode;
                    //传入的不是一个对象时就生成一个
                    $pipeNode = $this->getContainer()->make($sClassName);
                    $this->getContainer()->instance($sClassName, $pipeNode);
                }

                //例如: Response Middleware::handle(Request $request, Closure $fnNext);
                return $pipeNode->{$this->m_sPipeMethod}($inputObject, $fnLastReturn);
            };
        };
    }


    /**
     * 获取绑定的Ioc容器
     * @return Application
     */
    public function getContainer()
    {
        if (!$this->m_app){
            throw new RuntimeException('A Container instance has not been passed to the Pipeline.');
        }

        return $this->m_app;
    }

}
