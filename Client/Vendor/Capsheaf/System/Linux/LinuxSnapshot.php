<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:59 CST
 *  Description:     LinuxSnapshot.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:59 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Linux;

use Capsheaf\Application\Application;
use Capsheaf\System\Contracts\SnapshotInterface;

class LinuxSnapshot implements SnapshotInterface
{

    protected $m_app;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    /**
     * @param string $sDiskPart 要快照的磁盘分区
     * @return bool|string 快照后的快照名称
     */
    public function doSnapshot($sDiskPart)
    {
        // TODO: Implement doSnapshot() method.
    }

}
