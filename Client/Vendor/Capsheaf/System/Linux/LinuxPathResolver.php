<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:57 CST
 *  Description:     LinuxPathResolver.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:57 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Linux;

use Capsheaf\Application\Application;
use Capsheaf\System\Contracts\PathResolverInterface;

class LinuxPathResolver implements PathResolverInterface
{

    protected $m_app;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    public function getPlatformPath()
    {
        return '/usr/local/capsheaf/client/';
    }


    public function getAppRootPath()
    {

    }

}
