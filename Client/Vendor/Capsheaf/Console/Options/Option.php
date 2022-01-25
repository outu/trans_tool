<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-02 18:06:48 CST
 *  Description:     Option.php's function description
 *  Version:         1.0.0.20180402-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-02 18:06:48 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Console\Options;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

class Option implements IteratorAggregate
{

    /**
     * 必带参数，可多次指定-v=111 -v222，来自于原始的v:
     */
    const REQUIRED = 1;

    /**
     * 仅仅作为标记选项，可多次指定-v=111 -v，来自与原始的v::
     */
    const OPTIONAL = 2;

    /**
     * 标记选项，-v或者-vvv或者-v -v -v或者-once -once -once，来自于原始的v verbose
     */
    const FLAGS = 3;

    protected $m_sOptionSpec;

    /**
     * 短选项名称
     * @var string
     */
    protected $m_sShort;

    /**
     * 长选项名称
     * @var string
     */
    protected $m_sLong;

    /**
     * 选项描述
     * @var string
     */
    protected $m_sDescription;

    /**
     * 选项类型
     * @var int
     */
    protected $m_nOptionType;

    protected $m_arrValue;


    /**
     * Option constructor.
     * @param string $sOptionSpec
     * eg:
     * v|verbose 单个选项（可重复）
     * f|file: 必带参数 REQUIRED
     * with-json? 可选带或者不带参数 OPTIONAL
     * @param string $sDescription
     */
    public function __construct($sOptionSpec, $sDescription = '')
    {
        $this->m_sOptionSpec = trim($sOptionSpec);
        if (!strlen($this->m_sOptionSpec)){
            throw new InvalidArgumentException('Wrong sOptionSpec parameters, zero length string used.');
        }

        $this->m_sDescription = $sDescription;
        $this->parseType();
        $this->parseOption();
        $this->m_arrValue = [];
    }


    private function parseType()
    {
        switch ($this->m_sOptionSpec[strlen($this->m_sOptionSpec) - 1]){
            case '?':
                $this->m_nOptionType = self::OPTIONAL;
                break;
            case ':':
                $this->m_nOptionType = self::REQUIRED;
                break;
            default:
                $this->m_nOptionType = self::FLAGS;
        }
    }


    private function parseOption()
    {
        $sOptionSpec = trim($this->m_sOptionSpec, ':? ');
        $arrShortLong = explode('|', $sOptionSpec);
        list($this->m_sShort, $this->m_sLong) = array_pad($arrShortLong, 2, null);
    }


    public function getType()
    {
        return $this->m_nOptionType;
    }


    public function getShort()
    {
        return $this->m_sShort;
    }


    public function getLong()
    {
        return $this->m_sLong;
    }


    public function getDescription()
    {
        return $this->m_sDescription;
    }


    public function fillValue($arrParsedOptions = [])
    {
        if (isset($arrParsedOptions[$this->getShort()])){
            $this->margeSameNameOption($arrParsedOptions[$this->getShort()]);
        }

        if (isset($arrParsedOptions[$this->getLong()])){
            $this->margeSameNameOption($arrParsedOptions[$this->getLong()]);
        }
    }


    private function margeSameNameOption($optionValues)
    {
        if (is_array($optionValues)){
            array_merge($this->m_arrValue, $optionValues);
        } else {
            $this->m_arrValue[] = $optionValues;
        }
    }


    /**
     * 获取选项全部的值
     * @return array 全部返回数组的形式
     */
    public function get()
    {
        return $this->m_arrValue;
    }


    /**
     * 获取第一个值，主要用于快捷获取第一个有效值，主要针对单次指定的形式
     * @param mixed $default 获取不到时使用的默认值
     * @return mixed|null
     */
    public function getValue($default = null)
    {
        if (isset($this->m_arrValue[0])){
            return $this->m_arrValue[0];
        }

        return $default;
    }


    /**
     * 是否启用选项
     * @return int
     */
    public function enabled()
    {
        return $this->count() > 0;
    }


    /**
     * 获取选项出现的次数
     * @return int
     */
    public function count()
    {
        return count($this->m_arrValue);
    }


    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->m_arrValue);
    }

}
