<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:54 CST
 *  Description:     SnapshotInterface.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:54 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Contracts;

interface SnapshotInterface
{

    /**
     * @param string $sDiskPart 要快照的磁盘分区
     * @return bool|string 快照后的快照名称
     */
    public function doSnapshot($sDiskPart);

}
