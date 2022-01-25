<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-10-18 11:30:49 CST
 *  Description:     WindowsService.php's function description
 *  Version:         1.0.0.20181018-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-10-18 11:30:49 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\Service;

use Exception;
use InvalidArgumentException;
use RuntimeException;

class WindowsService extends  AbstractService
{

    public static $m_arrErrorCodeMessageMap = [];


    public function __construct()
    {
        if (!extension_loaded('win32service')){
            throw new RuntimeException('win32service extension is missing.');
        }

        //避免未加载插件实例化该类时导致语法错误，在这里对常量表示的消息进行赋值
        self::$m_arrErrorCodeMessageMap = [
            WIN32_ERROR_ACCESS_DENIED 	                     =>	"The handle to the SCM database does not have the appropriate access rights.",
            WIN32_ERROR_CIRCULAR_DEPENDENCY 	             =>	"A circular service dependency was specified.",
            WIN32_ERROR_DATABASE_DOES_NOT_EXIST 	         =>	"The specified database does not exist.",
            WIN32_ERROR_DEPENDENT_SERVICES_RUNNING 	         =>	"The service cannot be stopped because other running services are dependent on it.",
            WIN32_ERROR_DUPLICATE_SERVICE_NAME 	             =>	"The display name already exists in the service control manager database either as a service name or as another display name.",
            WIN32_ERROR_FAILED_SERVICE_CONTROLLER_CONNECT 	 =>	"This error is returned if the program is being run as a console application rather than as a service. If the program will be run as a console application for debugging purposes, structure it such that service-specific code is not called.",
            WIN32_ERROR_INSUFFICIENT_BUFFER 	             =>	"The buffer is too small for the service status structure. Nothing was written to the structure.",
            WIN32_ERROR_INVALID_DATA 	                     =>	"The specified service status structure is invalid.",
            WIN32_ERROR_INVALID_HANDLE 	                     =>	"The handle to the specified service control manager database is invalid.",
            WIN32_ERROR_INVALID_LEVEL 	                     =>	"The InfoLevel parameter contains an unsupported value.",
            WIN32_ERROR_INVALID_NAME 	                     =>	"The specified service name is invalid.",
            WIN32_ERROR_INVALID_PARAMETER 	                 =>	"A parameter that was specified is invalid.",
            WIN32_ERROR_INVALID_SERVICE_ACCOUNT 	         =>	"The user account name specified in the user parameter does not exist. See win32_create_service().",
            WIN32_ERROR_INVALID_SERVICE_CONTROL 	         =>	"The requested control code is not valid, or it is unacceptable to the service.",
            WIN32_ERROR_PATH_NOT_FOUND 	                     =>	"The service binary file could not be found.",
            WIN32_ERROR_SERVICE_ALREADY_RUNNING 	         =>	"An instance of the service is already running.",
            WIN32_ERROR_SERVICE_CANNOT_ACCEPT_CTRL 	         =>	"The requested control code cannot be sent to the service because the state of the service is WIN32_SERVICE_STOPPED, WIN32_SERVICE_START_PENDING, or WIN32_SERVICE_STOP_PENDING.",
            WIN32_ERROR_SERVICE_DATABASE_LOCKED 	         =>	"The database is locked.",
            WIN32_ERROR_SERVICE_DEPENDENCY_DELETED 	         =>	"The service depends on a service that does not exist or has been marked for deletion.",
            WIN32_ERROR_SERVICE_DEPENDENCY_FAIL 	 	     => "The service depends on another service that has failed to start.",
            WIN32_ERROR_SERVICE_DISABLED 	                 =>	"The service has been disabled.",
            WIN32_ERROR_SERVICE_DOES_NOT_EXIST 	 	         => "The specified service does not exist as an installed service.",
            WIN32_ERROR_SERVICE_EXISTS 	 	                 => "The specified service already exists in this database.",
            WIN32_ERROR_SERVICE_LOGON_FAILED 	 	         => "The service did not start due to a logon failure. This error occurs if the service is configured to run under an account that does not have the 'Log on as a service' right.",
            //see https://stackoverflow.com/questions/20561990/how-to-solve-the-specified-service-has-been-marked-for-deletion-error
            WIN32_ERROR_SERVICE_MARKED_FOR_DELETE 	 	     => "The specified service has already been marked for deletion. is task manager, mmc or service console window opening?",
            WIN32_ERROR_SERVICE_NO_THREAD 	 	             => "A thread could not be created for the service.",
            WIN32_ERROR_SERVICE_NOT_ACTIVE 	 	             => "The service has not been started.",
            WIN32_ERROR_SERVICE_REQUEST_TIMEOUT 	 	     => "The process for the service was started, but it did not call StartServiceCtrlDispatcher, or the thread that called StartServiceCtrlDispatcher may be blocked in a control handler function.",
            WIN32_ERROR_SHUTDOWN_IN_PROGRESS 	 	         => "The system is shutting down; this function cannot be called.",
            WIN32_NO_ERROR 	 	                             => "No error.",
        ];
    }


    /**
     * 注册系统服务
     * @param string $sServiceName 服务名
     * @param string $sPath 可执行文件路径
     * @param string $sParams 参数
     * @param array $arrExtraInfo 额外的信息，如['display':服务显示名称，'description':服务描述文本信息]
     * @return bool true表示成功，失败时抛出异常
     * @throws Exception 失败时返回错误信息
     * @see http://php.net/manual/zh/win32service.constants.errors.php
     * @see https://docs.microsoft.com/zh-cn/windows/desktop/Debug/system-error-codes--0-499-
     * @see https://docs.microsoft.com/en-us/windows/desktop/api/winsvc/nf-winsvc-createservicea
     */
    public function createService($sServiceName, $sPath, $sParams = '', $arrExtraInfo = [])
    {
        if (empty($sServiceName) || empty($sPath)){
            throw new InvalidArgumentException("Invalid service name or path parameter used to create service.");
        }

        $arrDetails = [
            'service'       => $sServiceName,
            'display'       => isset($arrExtraInfo['display']) ? $arrExtraInfo['display'] : $sServiceName,
            'path'          => $sPath,
            'params'        => $sParams,
            'description'   => isset($arrExtraInfo['description']) ? $arrExtraInfo['description'] : $sServiceName,
        ];

        $nRetCode = win32_create_service(array_merge($arrDetails, $arrExtraInfo));

        return $this->handleOperationResult($nRetCode, false);
    }


    public function deleteService($sServiceName)
    {
        if (empty($sServiceName) || !is_string($sServiceName)){
            throw new InvalidArgumentException("Invalid service name parameter to delete.");
        }

        $nRetCode = win32_delete_service($sServiceName);

        return $this->handleOperationResult($nRetCode, false);
    }


    /**
     * @param string $sServiceName
     * @return array|bool array表示查询到服务相关信息，false表示没查询到
     */
    public function queryServiceStatus($sServiceName)
    {
        if (empty($sServiceName) || !is_string($sServiceName)){
            throw new InvalidArgumentException("Invalid service name parameter to delete.");
        }

        $ret = win32_query_service_status($sServiceName);

        if (is_array($ret)){
            return $ret;
        } elseif ($ret === false) {
            throw new InvalidArgumentException("Invalid service name {$sServiceName} to delete.");
        } else {
            return $this->handleOperationResult($ret, false, true);
        }
    }


    public function startService($sServiceName, $bNoThrow = false)
    {
        if (empty($sServiceName) || !is_string($sServiceName)){
            throw new InvalidArgumentException("Invalid service name parameter to start.");
        }

        $nRetCode = win32_start_service($sServiceName);

        return $this->handleOperationResult($nRetCode, false, $bNoThrow);
    }


    public function stopService($sServiceName, $bNoThrow = false)
    {
        if (empty($sServiceName) || !is_string($sServiceName)){
            throw new InvalidArgumentException("Invalid service name parameter to stop.");
        }

        $nRetCode = win32_stop_service($sServiceName);

        return $this->handleOperationResult($nRetCode, false, $bNoThrow);
    }


    /**
     * @param int|bool|mixed $nRetCode service系列函数，有的true表示成功，有的WIN32_NO_ERROR(0)表示成功
     * @param bool $bRetTrueMeanSuccess
     * @param bool $bNoThrow 不抛出异常，仅返回false表示错误
     * @return bool
     */
    protected function handleOperationResult($nRetCode, $bRetTrueMeanSuccess = true, $bNoThrow = false)
    {
        if ($bRetTrueMeanSuccess ? $nRetCode : ($nRetCode === WIN32_NO_ERROR)){
            return true;
        } elseif ($bNoThrow == true) {
            return false;
        } else {
            if ($nRetCode === false){
                throw new InvalidArgumentException("Invalid parameters while deleting service.");
            }
            $sMessage = "Service operation failed:({$nRetCode})".$this->getErrorCodeMessage($nRetCode);

            throw new RuntimeException($sMessage);
        }
    }


    public function getErrorCodeMessage($nCode)
    {
        if (isset(self::$m_arrErrorCodeMessageMap[$nCode])){
            return self::$m_arrErrorCodeMessageMap[$nCode];
        }

        return 'Unknown error.';
    }


    public function startServiceCtrlDispatcher($sServiceName)
    {
        if (empty($sServiceName) || !is_string($sServiceName)){
            throw new InvalidArgumentException("Invalid service name parameter pass to service control manager.");
        }

        $nRetCode = win32_start_service_ctrl_dispatcher($sServiceName);

        return $this->handleOperationResult($nRetCode, true);
    }


    /**
     * 设置服务状态
     * @param string $sStatus 注意这里为字符串，避免扩展问题导致常量不存在，值有：<br>WIN32_SERVICE_RUNNING<br>WIN32_SERVICE_STOPPED<br>WIN32_SERVICE_STOP_PENDING<br>WIN32_SERVICE_START_PENDING<br>WIN32_SERVICE_CONTINUE_PENDING<br>WIN32_SERVICE_PAUSE_PENDING<br>WIN32_SERVICE_PAUSED
     * @param int $nCheckpointProgress WIN32_SERVICE_XXX_PENDING时用来指示进度
     * @return bool
     */
    public function setServiceStatus($sStatus, $nCheckpointProgress = 0)
    {
        $nStatus = @constant($sStatus);
        if (is_null($nStatus)){
            throw new InvalidArgumentException("Invalid status parameter while setting current service status.");
        }

        $nRetCode = win32_set_service_status($nStatus, $nCheckpointProgress);

        return $this->handleOperationResult($nRetCode, true);
    }


    /**
     * @return int WIN32_SERVICE_CONTROL_CONTINUE, WIN32_SERVICE_CONTROL_INTERROGATE, WIN32_SERVICE_CONTROL_PAUSE, WIN32_SERVICE_CONTROL_PRESHUTDOWN, WIN32_SERVICE_CONTROL_SHUTDOWN, WIN32_SERVICE_CONTROL_STOP
     */
    public function getLastControlMessage()
    {
        return win32_get_last_control_message();
    }

}
