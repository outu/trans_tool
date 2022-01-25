<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-30 18:10:52 CST
 *  Description:     LoggersCollection.php's function description
 *  Version:         1.0.0.20180330-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-30 18:10:52 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log;

class LoggersCollection
{

    /**
     * @var Logger[]
     */
    protected $m_arrLoggers = [];


    public function __construct($arrLoggers = [])
    {
        $this->m_arrLoggers = $arrLoggers;
    }


    /**
     * @param string  $sLoggerMethodName
     * @param array $arrArguments
     */
    public function __call($sLoggerMethodName, $arrArguments)
    {
        foreach ($this->m_arrLoggers as $logger){
            call_user_func_array([$logger, $sLoggerMethodName], $arrArguments);
        }
    }

}
