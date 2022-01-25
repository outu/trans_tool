<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:06:02 CST
 *  Description:     WindowsPathResolver.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:06:02 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Windows;

use Capsheaf\Application\Application;
use Capsheaf\System\Contracts\PathResolverInterface;

class WindowsPathResolver implements PathResolverInterface
{

    protected $m_app;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    public function getPlatformPath()
    {
        return 'C:/capsheaf/client/';
    }

}
