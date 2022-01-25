<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 15:02:46 CST
 *  Description:     LineFormatter.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 15:02:46 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log\Formatter;

use Capsheaf\Utils\Types\Json;

class LineFormatter extends NormalizerFormatter
{

    /**
     * 限定一条日志记录的输出格式
     * @var string
     */
    protected $m_sFormat = "[%datetime%] %channel%|%level_name%|%message% %context% %extra%\n";

    /**
     * 是否允许一条记录中存在换行符号
     * @var bool
     */
    protected $m_bAllowLineBreaksInRecord;

    /**
     * 是否自动移出空的%context%和%extra%字段占位符
     * @var bool
     */
    protected $m_bRemoveEmptyContextAndExtraFields;


    /**
     * LineFormatter constructor.
     * @param string|null $sFormat 限定一条日志记录的输出格式
     * @param bool $bAllowLineBreaksInRecord 是否允许一条记录中存在换行符号
     * @param bool $bRemoveEmptyContextAndExtraFields 是否自动移出空的%context%和%extra%字段占位符
     */
    public function __construct($sFormat = null, $bAllowLineBreaksInRecord = false, $bRemoveEmptyContextAndExtraFields = true)
    {
        $this->m_sFormat = $sFormat ?: $this->m_sFormat;
        $this->m_bAllowLineBreaksInRecord = $bAllowLineBreaksInRecord;
        $this->m_bRemoveEmptyContextAndExtraFields = $bRemoveEmptyContextAndExtraFields;

        parent::__construct();
    }


    /**
     * 格式化一条日志记录
     * @param array $arrRecord
     * @return string
     */
    public function format($arrRecord = [])
    {
        $arrRecordFormatted = parent::format($arrRecord);

        $sMessage = $this->m_sFormat;

        $sMessage = str_replace("%level_name%", str_pad($arrRecordFormatted['level_name'], 9, ' ', STR_PAD_BOTH), $sMessage);
        unset($arrRecordFormatted['level_name']);

        //用日志记录extra字段中的XXX键值，来替换格式字符串中可能存在的的%extra.XXX%
        foreach ($arrRecordFormatted['extra'] as $sKey => $value){
            if (strpos($sMessage, "%extra.{$sKey}%") === !false){
                $sMessage = str_replace("%extra.{$sKey}%", $this->stringify($value), $sMessage);
                unset($arrRecordFormatted['extra'][$sKey]);
            }
        }

        //用日志记录context字段中的XXX键值，来替换格式字符串中可能存在的的%context.XXX%
        foreach ($arrRecordFormatted['context'] as $sKey => $value){
            if (strpos($sMessage, "%context.{$sKey}%") === !false){
                $sMessage = str_replace("%context.{$sKey}%", $this->stringify($value), $sMessage);
                unset($arrRecordFormatted['context'][$sKey]);
            }
        }

        //若允许移出空的%context%和%extra%字段占位符
        if ($this->m_bRemoveEmptyContextAndExtraFields){
            //分别去除消息中的占位符
            if (empty($arrRecordFormatted['context'])){
                unset($arrRecordFormatted['context']);
                $sMessage = str_replace(' %context%', '', $sMessage);
            }
            if (empty($arrRecordFormatted['extra'])){
                unset($arrRecordFormatted['extra']);
                $sMessage = str_replace(' %extra%', '', $sMessage);
            }
        }

        //根据实际的日志记录中字段一一替换格式串中的占位符
        foreach ($arrRecordFormatted as $sField => $value){
            if (strpos($sMessage, "%{$sField}%") !== false){
                $sMessage = str_replace("%{$sField}%", $this->stringify($value), $sMessage);
            }
        }

        //通过替换去除没有用到的额外的%extra.AAA%%extra.BBB%%context.AAA%这种带后缀的占位符
        if (strpos($sMessage, '%') !== false){
            //(?:表示not capture 在这里没什么意义
            $sMessage = preg_replace('/%(?:extra|context)\..+?%/', '', $sMessage);
        }

        return $sMessage;
    }


    public function formatBatch($arrRecordsList = [])
    {
        $sMessage = '';
        foreach ($arrRecordsList as $arrRecord){
            $sMessage .= $this->format($arrRecord);
        }

        return $sMessage;
    }


    /**
     * 字符串化
     * @param mixed $data 要字符串化的值
     * @return string
     */
    protected function stringify($data)
    {
        $sData = $this->convertToString($data);
        $sData = $this->handleLineBreaksInRecord($sData);
        return $sData;
    }


    /**
     * 转换为字符串
     * @param mixed $data
     * @return bool|mixed|string
     */
    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string)$data;
        }

        return Json::toJson($data, true);
    }


    /**
     * 处理字段中的换行符
     * @param string $sData
     * @return string
     */
    protected function handleLineBreaksInRecord($sData)
    {
        if ($this->m_bAllowLineBreaksInRecord){
            /*
             * 若为JSON对象字符串
             * EG：
             * $a = [
             *     'AA' => 'NN',
             *     'bb' => "MM\r\n",
             *     ];
             *
             * var_dump(json_encode($a));
             * string(25) "{"AA":"NN","bb":"MM\r\n"}"
             * 说明JSON会把真正的换号符号进行转义
             */
            if (strpos($sData, '{')){
                return str_replace(['\r', '\n'], ["\r", "\n"], $sData);
            }

            return $sData;
        }

        return str_replace(["\r\n", "\r", "\n"], ' ', $sData);
    }

}
