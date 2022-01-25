<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-02-02 09:26:49 CST
 *  Description:     SpeedProgressBar.php's function description
 *  Version:         1.0.0.20180202-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-02-02 09:26:49 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Console\ProcessBar;

class SpeedProgressBar extends ProgressBar
{

    public function __construct($nCurrentBytes = 0, $nTotalSize = 100, $nDisplayWidth = 80, $sDoneChar = '=', $sRemainChar = '-', $sCurrentPositionChar = '>')
    {

        parent::__construct($nCurrentBytes, $nTotalSize, $nDisplayWidth, $sDoneChar, $sRemainChar, $sCurrentPositionChar);
    }

}
