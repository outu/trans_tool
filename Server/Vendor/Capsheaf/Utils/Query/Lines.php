<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-14 22:48:20 CST
 *  Description:     Lines.php's function description
 *  Version:         1.0.0.20180514-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-14 22:48:20 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Query;

class Lines
{

    protected $m_arrLines = [];


    public function __construct($arrLines = [])
    {
        $this->m_arrLines = $arrLines;
    }


    public function getLine($nLineNumber = 0)
    {
        if (isset($this->m_arrLines[$nLineNumber])){
            return $this->m_arrLines[$nLineNumber];
        }

        return null;
    }


    public function getLines()
    {
        return $this->m_arrLines;
    }


    public function getLinesCount()
    {
        return count($this->m_arrLines);
    }

}
