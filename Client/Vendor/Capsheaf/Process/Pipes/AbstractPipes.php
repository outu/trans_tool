<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-09 23:59:37 CST
 *  Description:     AbstractPipes.php's function description
 *  Version:         1.0.0.20180409-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-09 23:59:37 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process\Pipes;

use Capsheaf\Process\InputStream;
use Capsheaf\Process\Process;
use InvalidArgumentException;
use Iterator;

abstract class AbstractPipes
{

    const CHUNK_SIZE = 16384;

    protected $m_bWantOutput;

    /**
     * @var resource|InputStream|string|int|float
     */
    protected $m_inputSrc = null;
    protected $m_sInputBuffer = null;

    /**
     * 注意这几个Pipes是proc_open根据描述自动建立的，不是直接通过代码赋值，Windows下只会建立0，Linux下建立0，1，2号Pipe
     * @var array
     */
    protected $m_arrPipes = [];

    protected $m_bBlocked;


    /**
     * AbstractPipes constructor.
     * @param mixed $inputSrc
     * @param bool $bWantOutput
     * @see Process::parseInput() 查看input允许的类型
     */
    public function __construct($inputSrc, $bWantOutput = true)
    {
        if (is_resource($inputSrc) || is_array($inputSrc) || $inputSrc instanceof Iterator){
            $this->m_inputSrc = $inputSrc;
        } else if (is_string($inputSrc) || is_scalar($inputSrc)){
            //see https://php.net/manual/en/language.types.string.php#language.types.string.casting
            $this->m_sInputBuffer = (string)$inputSrc;
        } else {//其它类型的应该在Process::setInput时Exception报错，为了安全起见，这里赋值为null
            $this->m_inputSrc = null;
            $this->m_sInputBuffer = null;
        }

        $this->m_bWantOutput = $bWantOutput;
    }


    abstract public function getDescriptors();


    abstract public function getFiles();


    abstract public function areOpen();


    /**
     * 获取维护的pipes引用，由proc_open填充
     * @return array
     */
    public function & getPipes()
    {
        return $this->m_arrPipes;
    }


    public function isWantOutput()
    {
        return $this->m_bWantOutput;
    }


    public function close()
    {
        foreach ($this->m_arrPipes as $hPipe){
            fclose($hPipe);
        }

        $this->m_arrPipes = [];
    }


    /**
     * 将构造函数中传入的input数据写入stdin，有数据要写与否由程序自动判断，注意的是这里的写是写一次，而不是将全部未写的一次性写完
     * @return null|array 若还没有写完则返回当前的写PIPE构成的单个元素的数组，否则不返回
     */
    protected function write()
    {
        if (!isset($this->m_arrPipes[0])){
            return null;
        }

        //若是已经是，或者可以直接转换成string的，在构造函数中已经设置了buffer，优先使用buffer
        //剩下的便是array|Iterator或者资源
        //所以仅仅需要对【array|Iterator或者资源】特殊处理，其它直接读buffer

        //数组会拷贝，对象会引用，注意修改引发的后果，所以新定义一个变量
        $readSrc = null;

        //若源是可以循环获取的集合，则这里需要获取这个集合中当前的元素
        if ($this->isElementTraversable($this->m_inputSrc)){
            if ($this->isElementValid($this->m_inputSrc)){
                $readSrc = $this->getElementCurrent($this->m_inputSrc);

                //单个元素仅可以为【resource|string|scalar】
                if (is_resource($readSrc)){//是资源类型
                    //设置为非阻塞模式
                    stream_set_blocking($readSrc, 0);
                } elseif (is_string($readSrc) || is_scalar($readSrc)){
                    //以前还有没有写完的Buffer，这里不能覆盖
                    if (!isset($this->m_sInputBuffer[0])){//以前没有才能覆盖
                        $this->m_sInputBuffer = (string)$readSrc;
                        $readSrc = null;
                    }
                } else {
                    throw new InvalidArgumentException('The source write to process stdin pipe can only be ');
                }
            } else {
                $readSrc = null;
            }
        } elseif (is_resource($this->m_inputSrc)){
            $readSrc = $this->m_inputSrc;
            //设置为非阻塞模式
            stream_set_blocking($readSrc, 0);
        }

        $arrStreamsToRead = $arrStreamsToExcept = [];
        $arrStreamsToWrite = [$this->m_arrPipes[0]];

        //On success stream_select returns the 【number of stream resources contained in the modified arrays】, which may be 【zero if the timeout expires】 before anything interesting happens. On 【error false】 is returned and a warning raised (this can happen if the system call is interrupted by an incoming signal).
        //时间均为0表示立即返回
        //可写则会返回1并设置传入的$arrStreamsToWrite
        if (($nModified = stream_select($arrStreamsToRead, $arrStreamsToWrite, $arrStreamsToExcept, 0, 0)) === false){
            //不可写/不需要写，则直接返回
            return null;
        }

        //要是select这个pipe可写
        if (!empty($arrStreamsToWrite[0])){
            $hStdinPipe = $arrStreamsToWrite[0];

            //若已经设置了Buffer，首先就是尝试写完这个Buffer
            if (isset($this->m_sInputBuffer[0])){
                $nWritten = fwrite($hStdinPipe, $this->m_sInputBuffer);
                //对于Buffer中没有写完的部分记录，下次从写完的部分开始写
                $this->m_sInputBuffer = substr($this->m_sInputBuffer, $nWritten);

                //若还没有写完，这返回这个$hStdinPipe数组以便于后续操作
                if (isset($this->m_sInputBuffer[0])){
                    return [$hStdinPipe];
                }
            }

            //Buffer不存在或者已经写完该写的buffer，再尝试写剩下资源类型
            if ($readSrc){//$readSrc为资源类型
                while (true){//读取资源并写入进程PIPE，注意可能没有全部写完，需要将它多余的放到buffer如：资源【----+++>....】,-表示已写,+表示本buffer已写,>表示本buffer剩余,.表示还未读
                    //获得源输入数据
                    $sData = fread($readSrc, self::CHUNK_SIZE);
                    if (!isset($sData[0])){//若没有读取到任何字节
                        //则退出循环
                        break;
                    }
                    //数据写入目的地Pipe（或者文件）
                    $nWritten = fwrite($hStdinPipe, $sData);

                    //想写完读取的整个$sData，但是这个进程不需要那么多输入，所以还剩没有写完
                    $sData = substr($sData, $nWritten);
                    if (isset($sData[0])){
                        //记录到维护的buffer
                        $this->m_sInputBuffer = $sData;

                        return [$hStdinPipe];
                    }

                    //要是这个资源类型到EOF了，就关闭这个类型为资源的元素
                    if (feof($readSrc)){
                        //关闭资源
                        fclose($readSrc);
                        break;
                    }

                }//end while
            }//end $readSrc是资源

            //上面没有return的则表示资源或者buffer写入都已经OK
            //要是这个属于循环中的某个元素，那么这里指向下一个元素
            if ($this->isElementTraversable($this->m_inputSrc)){
                $this->setElementToNext($this->m_inputSrc);
                if ($this->isElementValid($this->m_inputSrc)){
                    return [$hStdinPipe];
                }
            }

            //不是循环的源，也直接走到这里来，则设置为空
            $this->m_inputSrc = null;
            fclose($this->m_arrPipes[0]);
            unset($this->m_arrPipes[0]);

        }//end 需要写$hStdinPipe

        return null;
    }


    /**
     * 判断目标是否可以遍历（仅支持array和Iterator或其子类）
     * @param array|Iterator $arrayOrIterator
     * @return bool
     */
    protected function isElementTraversable(&$arrayOrIterator)
    {
        return is_array($arrayOrIterator) || $arrayOrIterator instanceof Iterator;
    }


    /**
     * 获取当前指向的元素是否是有效值
     * @param array|Iterator $arrayOrIterator
     * @return bool
     */
    protected function isElementValid(&$arrayOrIterator)
    {
        if (is_array($arrayOrIterator)){
            return current($arrayOrIterator) !== false;
        }

        return $arrayOrIterator->valid();
    }


    /**
     * 获取当前指向的元素，不负责检验当前指向的元素是否有效
     * 注意这里的每个元素在设置时限制为只允许资源或者字符串（可转换）的类型传入，由于返回后的值没有修改，所以没必要引用的形式返回
     * @param array|Iterator $arrayOrIterator
     * @return mixed
     */
    protected function getElementCurrent(&$arrayOrIterator)
    {
        if (is_array($arrayOrIterator)){
            return current($arrayOrIterator);
        }

        return $arrayOrIterator->current();
    }


    /**
     * 当前指向的元素后移，不负责检验是否有效
     * @param array|Iterator $arrayOrIterator
     * @return void
     */
    protected function setElementToNext(&$arrayOrIterator)
    {
        if (is_array($arrayOrIterator)){
            next($arrayOrIterator);
            return;
        }

        $arrayOrIterator->next();
    }


    abstract public function readAndWrite($bBlockingMode, $bCloseOnEof = false);


    protected function unBlock()
    {
        if (!$this->m_bBlocked){
            return;
        }

        foreach ($this->m_arrPipes as $hPipe){
            stream_set_blocking($hPipe, 0);
        }

        if (is_resource($this->m_inputSrc)){
            stream_set_blocking($this->m_inputSrc, 0);
        }

        $this->m_bBlocked = false;
    }


    protected function isSystemCallBeenInterrupted()
    {
        $sLastError = error_get_last();

        //see http://poincare.matf.bg.ac.rs/~ivana/courses/ps/sistemi_knjige/pomocno/apue/APUE/0201433079/ch10lev1sec5.html
        // stream_select returns false when the `select` system call is interrupted by an incoming signal
        return isset($sLastError['message'])
            && false !== stripos($sLastError['message'], 'interrupted system call');
    }

}
