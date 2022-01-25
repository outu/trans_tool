<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-10-23 09:42:13 CST
 *  Description:     Rhel6Service.php's function description
 *  Version:         1.0.0.20181023-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-10-23 09:42:13 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Service\LinuxService;

use Capsheaf\FileSystem\FileSystem;
use Capsheaf\Process\Process;
use Capsheaf\System\Service\AbstractService;
use InvalidArgumentException;

class Rhel6Service extends AbstractService
{
    
    public static $m_serviceStub =<<<EOF
#!/bin/sh
#
# __SERVICE_NAME__      __DESCRIPTION__
#
# chkconfig: - 85 15
# processname: __PATH__
# config: 
# pidfile: __PID_FILE__
# description: __DESCRIPTION__
#

# Source function library.
. /etc/rc.d/init.d/functions

Vendor=__VENDOR__
ProcessPath=__PATH__
ProcessParams=__PARAMS__
BaseDir=__BASE_DIR__
ProgramCmd=\${BaseDir}\${Vendor}/\${ProcessPath} \${ProcessParams}
PidFile=\${PIDFILE-/var/run/\${Vendor}/__SERVICE_NAME__.pid}
RETVAL=0

start() {
    if [ -f \${PidFile} ]
    then
        echo "Service already running..."
        return \$RETVAL
    fi

    echo -n \$"Starting \$ProcessPath: "

    daemon --pidfile=\${PidFile} \${ProgramCmd}
    RETVAL=\$?
    echo
    [ \$RETVAL = 0 ]
    return \$RETVAL
}

stop() {
    if [ ! -f \${PidFile} ]
    then
        echo "Service not running at all..."
    fi

    echo -n \$"Stopping \$ProcessPath: "
    killproc -p \${PidFile} \${ProcessPath}
    RETVAL=\$?
    echo
    [ \$RETVAL = 0 ] && rm -f \${PidFile}
}

status() {
    if [ -f \${PidFile} ]
    then
        echo "Service is now running..."
    else
        echo "Service is not running..."
    fi
}

# See how we were called.
case "\$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    status)
        status
        RETVAL=\$?
        ;;
    restart)
        stop
        start
        ;;
    *)
        echo \$"Usage: \$ProcessPath {start|stop|restart|status}"
        RETVAL=2
esac

exit \$RETVAL

EOF;


    /**
     * @param string $sServiceName
     * @param string $sPath
     * @param string $sParams
     * @param array $arrExtraInfo 必须包含的字段vendor|baseDir|description
     * @return bool|int
     */
    public function createService($sServiceName, $sPath, $sParams, $arrExtraInfo = [])
    {
        if (!key_exists('vendor', $arrExtraInfo) || !key_exists('baseDir', $arrExtraInfo) || !key_exists('description', $arrExtraInfo)){
            throw new InvalidArgumentException("Missing necessary key while creating service.");
        }
        $arrReplace = [
            'serviceName' => $sServiceName,
            'path' => $sServiceName,
            'params' => $sServiceName,
        ];

        $arrReplace = array_merge($arrReplace, $arrExtraInfo);
        $arrReplaceSearch = [];
        $arrReplaceTo = [];
        foreach ($arrReplace as $sSearch => $sReplace){
            //注意变量名格式只能为serviceName或者params的格式
            $sSearch = strtoupper(preg_replace('/([A-Z])/', '_$0', $sSearch));
            $arrReplaceSearch[] = "__{$sSearch}__";
            $arrReplaceTo[] = $sReplace;
        }

        $sServiceScript = str_replace($arrReplaceSearch, $arrReplaceTo, self::$m_serviceStub);

        return file_put_contents("/etc/init.d/{$sServiceName}", $sServiceScript);
    }

    
    public function deleteService($sServiceName)
    {
        return FileSystem::delete("/etc/init.d/{$sServiceName}");
    }


    public function queryServiceStatus($sServiceName)
    {
        $sCmd = "service status {$sServiceName}";

        return $this->createProcess($sCmd);
    }


    public function startService($sServiceName)
    {
        $sCmd = "service start {$sServiceName}";

        return $this->createProcess($sCmd);
    }


    public function stopService($sServiceName)
    {
        $sCmd = "service stop {$sServiceName}";

        return $this->createProcess($sCmd);
    }


    protected function createProcess($sCmd)
    {
        $process = new Process($sCmd);
        $process->wait();

        return $process->getOutput();
    }

}
