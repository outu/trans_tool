<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-08 11:14:50 CST
 *  Description:     ConnectionFactory.php's function description
 *  Version:         1.0.0.20180508-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-08 11:14:50 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

use Capsheaf\Application\Application;
use Capsheaf\Database\Connection\MysqlConnection;
use Capsheaf\Utils\Types\Arr;
use InvalidArgumentException;

class ConnectionFactory
{
    protected $m_app;


    public function __construct(Application $app)
    {
        $this->m_app = $app;
    }


    /**
     * @param string $sConnectionName
     * @return AbstractConnection
     */
    public function make($sConnectionName)
    {
        $arrConfig = $this->getConfig($sConnectionName);
        return new MysqlConnection($arrConfig);
    }


    public function getConfig($sConnectionName)
    {
        $arrDatabaseConfig = $this->m_app['config']['database'];
        if (is_null($arrConfig = Arr::get($arrDatabaseConfig, $sConnectionName))){
            throw new InvalidArgumentException("Database source name {$sConnectionName} not presented in config.json");
        }

        return $arrConfig;
    }

}
