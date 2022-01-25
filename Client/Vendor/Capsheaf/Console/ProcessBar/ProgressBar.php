<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-02-02 09:26:49 CST
 *  Description:     ProgressBar.php's function description
 *  Version:         1.0.0.20180202-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-02-02 09:26:49 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Console\ProcessBar;

use InvalidArgumentException;
use OutOfRangeException;

class ProgressBar
{

    protected $m_sFormat = "%current%/%max% [%bar%] %percent%% %remain%";

    protected $m_sExtraMessage = null;

    protected $m_arrParameters = [];

    /**
     * 替换规则列表：[优先级][标签=>回调处理函数($sBuffer, $arrParameters)]
     * @var array
     */
    protected $m_arrReplacementHandlers = [];


    public function __construct($nCurrent = 0, $nMax = 100, $nExtraMessageLength = 0, $nDisplayWidth = 80, $sDoneChar = '=', $sRemainChar = '-', $sCurrentPositionChar = '>')
    {
        $this->m_arrParameters = [
            'nCurrent' => $nCurrent,
            'nMax' => $nMax,
            'nExtraMessageLength' => $nExtraMessageLength,
            'nDisplayWidth' => $nDisplayWidth,
            'sDoneChar' => $sDoneChar,
            'sRemainChar' => $sRemainChar,
            'sCurrentPositionChar' => $sCurrentPositionChar,
            'arrTimestamps' => [
                $nCurrent => time(),
            ],
        ];

        $this->registerDefaultHandlers();
    }


    public function getFormat()
    {
        return $this->m_sFormat;
    }


    public function setFormat($sFormat)
    {
        $this->m_sFormat = $sFormat;
    }


    public function message($sExtraMessage = null)
    {
        $this->m_sExtraMessage = $sExtraMessage;

        return $this;
    }


    protected function registerDefaultHandlers()
    {
        $this->addReplacementHandler(
            '%current%', 10, function ($sBuffer, $arrParameters){
                return $this->m_arrParameters['nCurrent'];
            }
        );

        $this->addReplacementHandler(
            '%max%', 20, function ($sBuffer, $arrParameters){
                return $this->m_arrParameters['nMax'];
            }
        );

        $this->addReplacementHandler(
            '%percent%', 30, function ($sBuffer, $arrParameters){
                return number_format($this->m_arrParameters['nCurrent'] / $this->m_arrParameters['nMax'] * 100, 2);
            }
        );


        $this->addReplacementHandler(
            '%remain%', 40, function (){
                return $this->calculateRemain();
            }
        );


        $this->addReplacementHandler(
            '%bar%', 999999, function ($sBuffer){
                return $this->calculateBar($sBuffer);
            }
        );
    }


    /**
     * 计算剩余时间的回调函数
     * 如进度从【初始化的25】->【现在的80】,假设总进度为【100】
     * 时间点为【2222222】->【3333333】
     * 则时间估计方式为(3333333 - 2222222)/(80 - 25) * (100 - 80)
     */
    protected function calculateRemain()
    {
        //获取第一个时间
        $nFirstTime = reset($this->m_arrParameters['arrTimestamps']);

        //当只有一个时间或者在同一秒之内
        if (count($this->m_arrParameters['arrTimestamps']) == 1) {
            return 'Calculating...';
        }

        $nRemainSeconds = (time() - $nFirstTime)
            / ($this->m_arrParameters['nCurrent'] - key($this->m_arrParameters['arrTimestamps']))
            * ($this->m_arrParameters['nMax'] - $this->m_arrParameters['nCurrent']);

        $nRemainSeconds = max(0, $nRemainSeconds);

        return sprintf(
            "%02d:%02d:%02d",
            intval($nRemainSeconds / 3600),
            intval($nRemainSeconds / 60),
            $nRemainSeconds % 60
        );
    }


    protected function calculateBar($sBuffer)
    {
        $nLengthAvailable = $this->m_arrParameters['nDisplayWidth'] - strlen(str_replace('%bar%', '', $sBuffer));
        $arrBarElements = array_fill(0, $nLengthAvailable, $this->m_arrParameters['sRemainChar']);
        $nCurrentPosition = intval($nLengthAvailable / $this->m_arrParameters['nMax'] * $this->m_arrParameters['nCurrent']);
        for ($i = 0; $i < $nCurrentPosition; $i++){
            $arrBarElements[$i] = $this->m_arrParameters['sDoneChar'];
        }

        $arrBarElements[$nCurrentPosition] = $this->m_arrParameters['sCurrentPositionChar'];

        return implode('', $arrBarElements);
    }


    /**
     * 当前一次输出字符多于本次字符数时，回出现残余情况，需要在本次补白来保证正确输出
     * @param string $sBuffer
     * @return string
     */
    protected function clearRightDirtyChars($sBuffer)
    {
        $nLength = mb_strlen($sBuffer);
        $nTotalLength = $this->m_arrParameters['nDisplayWidth'] + $this->m_arrParameters['nExtraMessageLength'];

        while ($nLength < $nTotalLength - 1){
            $sBuffer .= ' ';
            $nLength = mb_strlen($sBuffer);
        }

        return $sBuffer;
    }


    /**
     * 添加替换处理函数
     * @param string $sSearch
     * @param int $nPriority 值越大越在后面执行
     * @param \Closure $fnReplacementCallback
     */
    public function addReplacementHandler($sSearch, $nPriority, $fnReplacementCallback)
    {
        $this->m_arrReplacementHandlers[$nPriority][$sSearch] = $fnReplacementCallback;
        ksort($this->m_arrReplacementHandlers);
    }


    /**
     * 循环调用display函数
     * @param bool $bLineReturn
     * @return $this
     */
    public function display($bLineReturn = false)
    {
        if ($this->m_arrParameters['nCurrent'] > $this->m_arrParameters['nMax']){
            throw new OutOfRangeException("Current position:{$this->m_arrParameters['nCurrent']} of process bar must below than max:{$this->m_arrParameters['nMax']}.");
        }

        $sDisplayBuffer = $this->m_sFormat;
        foreach ($this->m_arrReplacementHandlers as $nPriority => $arrHandler){
            foreach ($arrHandler as $sSearch => $fnReplacementCallback) {
                $sDisplayBuffer = str_replace($sSearch, $fnReplacementCallback($sDisplayBuffer, $this->m_arrParameters), $sDisplayBuffer);
            }
        }

        if (!empty($this->m_sExtraMessage)) {
            $sDisplayBuffer .= mb_substr($this->m_sExtraMessage, 0, $this->m_arrParameters['nExtraMessageLength']);
        }

        $sDisplayBuffer = $this->clearRightDirtyChars($sDisplayBuffer);
        $sDisplayBuffer .= ($bLineReturn ? "\n" : "\r");

        echo $sDisplayBuffer;

        return $this;
    }


    public function update($nPosition = null)
    {
        if (!is_int($nPosition) || ($nPosition < $this->m_arrParameters['nCurrent'])){
            throw new InvalidArgumentException('Position to set can not lower than current position.');
        }
        $this->m_arrParameters['arrTimestamps'][$nPosition] = time();
        $this->m_arrParameters['nCurrent'] = $nPosition;

        //达到最大值时需要换行
        $bLineReturn = ($nPosition == $this->m_arrParameters['nMax']);

        return $this->display($bLineReturn);
    }


    public function advance($nStep = 1)
    {
        return $this->update($this->m_arrParameters['nCurrent'] += $nStep);
    }

}
