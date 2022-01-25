<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-02 17:13:53 CST
 *  Description:     CommandOptionsions.php's function description
 *  Version:         1.0.0.20180402-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-02 17:13:53 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Console\Options;

use InvalidArgumentException;

class CommandOptions
{

    /**
     * @var Option[]
     */
    protected $m_arrOptions = [];

    /**
     * @var array
     */
    protected $m_arrParsedOptions = null;


    public function __construct()
    {

    }


    /**
     * @param string $sOptionName
     * @param string $sOptionSpec
     * @param string $sDescription
     */
    public function addOption($sOptionName, $sOptionSpec, $sDescription = '')
    {
        //重置解析过的选项
        $this->m_arrParsedOptions = null;

        $newOption = new Option($sOptionSpec, $sDescription);

        foreach ($this->m_arrOptions as $option){
            if (array_intersect(
                [
                    $newOption->getShort(),
                    $newOption->getLong()
                ],
                [
                    $option->getShort(),
                    $option->getLong()
                ]
            )){
                throw new InvalidArgumentException('Option can not have same key as previous.');
            }
        }

        $this->m_arrOptions[$sOptionName] = $newOption;
    }


    /**
     * @param string $sOptionName
     * @return Option
     */
    public function getOption($sOptionName)
    {
        if (empty($this->m_arrOptions[$sOptionName])){
            throw new InvalidArgumentException("Option name:'{$sOptionName}' does not exists.");
        }

        if (empty($this->m_arrParsedOptions)){
            $this->m_arrParsedOptions = $this->parseOptions();
        }

        $option = $this->m_arrOptions[$sOptionName];
        $option->fillValue($this->m_arrParsedOptions);

        return $option;
    }


    /**
     * @return array
     */
    protected function parseOptions()
    {
        $arrShort = '';
        $arrLong = [];
        foreach ($this->m_arrOptions as $option){
            switch ($option->getType()){
                case Option::REQUIRED:
                    $sSuffix = ':';
                    break;
                case Option::OPTIONAL:
                    $sSuffix = '::';
                    break;
                default:
                    $sSuffix = '';
            }

            if ($option->getShort() !== null){
                $arrShort .= $option->getShort().$sSuffix;
            }

            if ($option->getLong() !== null){
                $arrLong[] = $option->getLong().$sSuffix;
            }
        }

        $arrParsedOptions = getopt($arrShort, $arrLong);

        return $arrParsedOptions;
    }


    /**
     * 渲染选项帮助
     * @return array
     */
    public function renderOptions()
    {
        $arrLines = [];
        $nLeftMax = 0;
        foreach ($this->m_arrOptions as $sOptionName => $option){
            $sLeft = '';

            if ($option->getShort()){
                $sLeft .= '-'.$option->getShort();
            }

            if ($option->getLong()){
                if ($option->getShort()){
                    $sLeft .= ', ';
                }
                $sLeft .= '--'.$option->getLong();
            }

            switch ($option->getType()){
                case Option::OPTIONAL:
                    $sLeft .= " [<{$sOptionName}>]";
                    break;
                case Option::REQUIRED:
                    $sLeft .= " <{$sOptionName}>";
                    break;

            }

            if ($nLeftMax < ($nLength = strlen($sLeft))){
                $nLeftMax = $nLength;
            }

            $arrLines[] = [
                'left' => $sLeft,
                'right' => $option->getDescription()
            ];
        }

        array_walk(
            $arrLines , function(&$arrLine) use ($nLeftMax){
                $arrLine['left'] = str_pad($arrLine['left'], $nLeftMax, ' ', STR_PAD_LEFT);
                $arrLine = $arrLine['left']."\t".$arrLine['right'];
            }
        );

        return $arrLines;
    }


    /**
     * 直接输出到控制台
     */
    public function outputRenderOptions()
    {
        echo 'Options:'.PHP_EOL;
        echo implode(PHP_EOL, $this->renderOptions()).PHP_EOL;
        echo PHP_EOL;
    }

}
