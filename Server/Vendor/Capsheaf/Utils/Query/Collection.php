<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-14 21:46:35 CST
 *  Description:     Collection.php's function description
 *  Version:         1.0.0.20180514-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-14 21:46:35 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Query;

class Collection
{

    /**
     * @var Lines
     */
    protected $m_wholeLines;

    protected $m_parentCollection;

    /**
     * @var Collection[]
     */
    protected $m_arrSubCollections = [];

    /**
     * @var Segment[]
     */
    protected $m_arrSegments = [];


    public function __construct($wholeLines, $parentCollection = null)
    {
        $this->m_wholeLines = $wholeLines;
        $this->m_parentCollection = $parentCollection;
    }


    public static function createRootCollection($wholeLines)
    {
        $collection = new self($wholeLines);
        $segment = new Segment($wholeLines, null, $collection, 0, $wholeLines->getLinesCount() - 1);

        return $collection->addSegment(0, $segment);
    }


    /**
     * 根据现存的Segment构建一个Collection，以便于重新进行新的查询
     * @param Segment $segment
     * @return Collection
     */
    public static function createCollectionWithSegment(Segment $segment)
    {
        $wholeLines = new Lines($segment->getLines());
        $collection = new self($wholeLines);

        return $collection->addSegment($segment->m_nBeginLineNumber, $segment);
    }


    public function addSegment($nBeginLineNumber, $segment)
    {
        $this->m_arrSegments[$nBeginLineNumber] = $segment;

        return $this;
    }


    public function removeSegment($nBeginLineNumber)
    {
        unset($this->m_arrSegments[$nBeginLineNumber]);

        return $this;
    }


    /**
     * @param string $sPregPattern
     * @return Collection
     */
    public function query($sPregPattern)
    {
        $subCollection = new self($this->m_wholeLines, $this);

        foreach ($this->m_arrSegments as $segment){
            $arrSegments = $segment->filter($sPregPattern, $subCollection);
            foreach ($arrSegments as $subSegment){
                $subCollection->addSegment($subSegment->m_nBeginLineNumber, $subSegment);
            }
        }

        return $subCollection;
    }


    public function filterNextLinesHas($sPregPattern, $nWithinLines = 1)
    {
        foreach ($this->m_arrSegments as $nBeginLineNumber => $segment){
            $bFound = false;
            for ($i = 1; $i <= $nWithinLines; $i++){
                if (preg_match($sPregPattern, $this->m_wholeLines->getLine($segment->m_nBeginLineNumber + $i))){
                    $bFound = true;
                    break;
                }
            }

            if (!$bFound){
                $this->removeSegment($nBeginLineNumber);
            }
        }

        return $this;
    }


    public function filterPreviousLinesHas($sPregPattern, $nWithinLines = 1)
    {
        foreach ($this->m_arrSegments as $nBeginLineNumber => $segment){
            $bFound = false;
            for ($i = 1; $i <= $nWithinLines; $i++){
                if (preg_match($sPregPattern, $this->m_wholeLines->getLine($segment->m_nBeginLineNumber - $i))){
                    $bFound = true;
                    break;
                }
            }

            if (!$bFound){
                $this->removeSegment($nBeginLineNumber);
            }
        }

        return $this;
    }


    /**
     * 对查询出的每个Segment执行行偏移操作
     * @param int $nBeginLineOffset 起始行偏移行数（正负值表示方向）
     * @param int $nEndLineOffset 结束行偏移行数（正负值表示方向）
     * @return $this
     */
    public function justifyOffset($nBeginLineOffset = 0, $nEndLineOffset = 0)
    {
        foreach ($this->m_arrSegments as $nBeginLineNumber => $segment){
            $segment->setBeginLineOffset($nBeginLineOffset);
            $segment->setEndLineOffset($nEndLineOffset);
        }

        return $this;
    }


    public function getSegments()
    {
        return $this->m_arrSegments;
    }


    public function getFirstSegment()
    {
        return reset($this->m_arrSegments);
    }


    public function passFirstSegment($fnWhileHaveFirst, $default = null)
    {
        if ($segment = $this->getFirstSegment()){
            return $fnWhileHaveFirst($segment);
        }

        return $default;
    }

}
