<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:05:51 CST
 *  Description:     PathResolverInterface.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:05:51 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Contracts;

interface PathResolverInterface
{

    /**
     * 获取平台路径
     * @return string
     */
    public function getPlatformPath();

}
