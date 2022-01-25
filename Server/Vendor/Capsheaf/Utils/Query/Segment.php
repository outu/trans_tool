<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-15 00:21:48 CST
 *  Description:     Segment.php's function description
 *  Version:         1.0.0.20180515-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-15 00:21:48 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Query;

class Segment
{

    /**
     * @var Lines
     */
    protected $m_wholeLines;
    protected $m_parentSegment;
    protected $m_arrMatches = [];

    /**
     * @var null
     */
    public $m_parentCollection;
    public $m_nBeginLineNumber;
    public $m_nEndLineNumber;


    public function __construct($wholeLines, $parentSegment = null, $parentCollection = null, $nBeginLineNumber = 0, $nEndLineNumber = 0)
    {
        $this->m_wholeLines       = $wholeLines;
        $this->m_parentSegment    = $parentSegment;
        $this->m_parentCollection = $parentCollection;

        $this->m_nBeginLineNumber = $nBeginLineNumber;
        $this->m_nEndLineNumber   = $nEndLineNumber;
    }


    /**
     * @return null
     */
    public function getParentCollection()
    {
        return $this->m_parentCollection;
    }


    public function getLines()
    {
        return array_slice($this->m_wholeLines->getLines(), $this->m_nBeginLineNumber, $this->m_nEndLineNumber - $this->m_nBeginLineNumber + 1, true);
    }


    /**
     * @param int $nOffsetToBeginLineNumber
     * @return string|null
     */
    public function getLine($nOffsetToBeginLineNumber = 0)
    {
        return $this->m_wholeLines->getLine($this->m_nBeginLineNumber + $nOffsetToBeginLineNumber);
    }


    /**
     * 对该Segment管理的行的起始行增大或者减小指定的行数，例如偏移指定为-1则表示其实行从14到13行
     * @param int $nOffset
     * @return Segment
     */
    public function setBeginLineOffset($nOffset)
    {
        $this->m_nBeginLineNumber += (int)$nOffset;
        //最小是0，最大等于最后一行所在行
        $this->m_nBeginLineNumber = min(max(0, $this->m_nBeginLineNumber), $this->m_nEndLineNumber);

        return $this;
    }


    /**
     * 对该Segment管理的行的结束行增大或者减小指定的行数，例如偏移指定为-1则表示其实行从19到18行
     * @param int $nOffset
     * @return Segment
     */
    public function setEndLineOffset($nOffset)
    {
        $this->m_nEndLineNumber += (int)$nOffset;
        //最大值为总行数-1，最小为起始行行数
        $this->m_nEndLineNumber = max(min($this->m_wholeLines->getLinesCount() - 1, $this->m_nEndLineNumber), $this->m_nBeginLineNumber);

        return $this;
    }


    public function isPlain()
    {
        return $this->m_nBeginLineNumber == $this->m_nEndLineNumber;
    }


    public function setMatches($arrMatches)
    {
        $this->m_arrMatches = $arrMatches;

        return $this;
    }


    public function getMatches($nIndex = null)
    {
        if (is_null($nIndex)){
            return $this->m_arrMatches;
        } elseif (isset($this->m_arrMatches[$nIndex])){
            return $this->m_arrMatches[$nIndex];
        }

        return null;
    }


    /**
     * @param string $sPregPattern
     * @param Collection $parentCollection
     * @return self[]
     */
    public function filter($sPregPattern, $parentCollection)
    {
        $arrSegments = [];
        $arrFindLinesNumber = [];
        $arrMatches = [];

        //在该Segment管理的行内进行检索
        for ($i = $this->m_nBeginLineNumber; $i <= $this->m_nEndLineNumber; $i++){
            if (preg_match($sPregPattern, $this->m_wholeLines->getLine($i), $arrOneLineMatches)){
                $arrFindLinesNumber[] = $i;
                $arrMatches[$i] = $arrOneLineMatches;
            }
        }

        if (!empty($arrFindLinesNumber)){
            $nLastLineNumber = $this->m_nEndLineNumber;

            //eg:[1,5,8,12] => [12,8,5,1]
            $arrFindLinesNumber = array_reverse($arrFindLinesNumber);
            foreach ($arrFindLinesNumber as $nNewCollectBeginLineNumber){
                $segment = new self($this->m_wholeLines, $this, $parentCollection, $nNewCollectBeginLineNumber, $nLastLineNumber);
                $segment->setMatches($arrMatches[$nNewCollectBeginLineNumber]);
                $arrSegments[] = $segment;

                $nLastLineNumber = $nNewCollectBeginLineNumber - 1;
            }

            $arrSegments = array_reverse($arrSegments);
        }

        return $arrSegments;
    }


    /**
     * 在该Segment内的行进行正则匹配，查找到就返回
     * @param $sPregPattern
     * @param $nLineFromBegin
     * @param $nCountLine
     * @return array|null
     */
    public function find($sPregPattern, $nLineFromBegin = 0, $nCountLine = null)
    {
        $arrMatches = $this->findAll($sPregPattern, $nLineFromBegin, $nCountLine, true);
        if (count($arrMatches)){
            return reset($arrMatches);
        }

        return null;
    }


    /**
     * 在该Segment内的行进行正则匹配，查找全部匹配的行
     * @param string $sPregPattern
     * @param int $nLineFromBegin
     * @param null|int $nCountLine
     * @param bool $bOnce
     * @return array 注意下标为行（可能不连续）
     */
    public function findAll($sPregPattern, $nLineFromBegin = 0, $nCountLine = null, $bOnce = false)
    {
        $nFindBeginLineNumber = $this->m_nBeginLineNumber + $nLineFromBegin;
        if (is_null($nCountLine)){
            $nFindEndLineNumber = $this->m_nEndLineNumber;
        } elseif ($nCountLine < 0){
            $nFindEndLineNumber = $this->m_nEndLineNumber + $nCountLine;//减
        } else {
            $nFindEndLineNumber = $this->m_nBeginLineNumber + $nCountLine;
        }

        $arrMatches = [];

        //在该Segment管理的行内进行检索
        for ($i = $nFindBeginLineNumber; $i <= $nFindEndLineNumber; $i++){
            if (preg_match($sPregPattern, $this->m_wholeLines->getLine($i), $arrOneLineMatches)){
                $arrMatches[$i] = $arrOneLineMatches;

                if ($bOnce){
                    break;
                }
            }
        }

        return $arrMatches;
    }

}
