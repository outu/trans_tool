<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-02-25 15:54:05 CST
 *  Description:     Request.php's function description
 *  Version:         1.0.0.20180225-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-02-25 15:54:05 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Foundation\Request;

class Request
{

    protected $m_sModule;
    protected $m_sController;
    protected $m_sAction;


    public function __construct($sModule, $sController, $sAction)
    {
        $this->m_sModule        = trim($sModule);
        $this->m_sController    = trim($sController);
        $this->m_sAction        = trim($sAction);
    }


    public function getModule()
    {
        return $this->m_sModule;
    }


    public function getController()
    {
        return $this->m_sController;
    }


    public function getAction()
    {
        return $this->m_sAction;
    }


    public function getParameters()
    {
        return [];
    }

}
