<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-02-25 15:55:09 CST
 *  Description:     Response.php's function description
 *  Version:         1.0.0.20180225-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-02-25 15:55:09 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Response;

use Capsheaf\Foundation\Request\Request;

class Response
{

    /**
     * 提供一个机会，根据Request来对Response进行修改
     * @param Request $request
     * @return Response
     */
    public function prepare($request)
    {
        return $this;
    }


    /**
     * 将要发送的Buffer发送到客户端，子类中可以自定义发送形式（如Header和Content）
     * @return mixed
     */
    public function send()
    {
        return $this;
    }

}
