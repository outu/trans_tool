<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 09:35:51 CST
 *  Description:     DB.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 09:35:51 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

use Capsheaf\Application\Application;

class DatabaseManager
{
    protected $m_app;

    protected $m_connectionFactory;


    /**
     * 连接列表
     * @var AbstractConnection[]
     */
    protected $m_arrConnections;


    public function __construct(Application $app, ConnectionFactory $connectionFactory)
    {
        $this->m_app = $app;
        $this->m_connectionFactory = $connectionFactory;

        $this->m_arrConnections = [];
    }


    /**
     * 获取全部连接
     * @return AbstractConnection[]
     */
    public function getConnections()
    {
        return $this->m_arrConnections;
    }


    /**
     * 获取数据库连接
     * @param null|string $sConnectionName
     * @return AbstractConnection|mixed
     */
    public function connection($sConnectionName = null)
    {
        if (!isset($this->m_arrConnections[$sConnectionName = is_null($sConnectionName) ? 'default' : $sConnectionName])){
            $this->m_arrConnections[$sConnectionName] = $this->m_connectionFactory->make($sConnectionName)->enableLog();
        }

        return $this->m_arrConnections[$sConnectionName];
    }


    public function disconnect($sConnectionName = 'default')
    {
        if (isset($this->m_arrConnections[$sConnectionName])){
            $this->m_arrConnections[$sConnectionName]->disconnect();
        }
    }


    public function reconnect($sConnectionName = 'default')
    {
        if (isset($this->m_arrConnections[$sConnectionName])){
            $this->m_arrConnections[$sConnectionName]->reconnect();
        }
    }


    /**
     * 参数传递到直接的对象
     * @param string $sMethodName
     * @param array $arrArguments
     * @return mixed
     */
    public function __call($sMethodName, $arrArguments)
    {
        return call_user_func_array([$this->connection(), $sMethodName], $arrArguments);
    }

}
