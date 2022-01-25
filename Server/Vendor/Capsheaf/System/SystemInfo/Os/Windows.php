<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-31 13:16:25 CST
 *  Description:     Windows.php's function description
 *  Version:         1.0.0.20180331-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-31 13:16:25 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\System\SystemInfo\Os;

use Exception;

class Windows extends AbstractOs
{

    protected $m_wmiObject;


    public function __construct()
    {
        parent::__construct();

        try{
            $comLocator = new \COM('WbemScripting.SWbemLocator');
            $this->m_wmiObject = $comLocator->ConnectServer('', 'root\CIMv2');
        } catch (Exception $exception){
            throw new Exception('Wmi connect error.');
        }
    }


    protected function queryWmiClass($sWmiClass, $arrNeedFields = false)
    {
        $arrData = [];
        if ($this->m_wmiObject){
            try {
                $webm = $this->m_wmiObject->Get($sWmiClass);
                $arrFields = $webm->Properties_;
                $arrRecords = $webm->Instances_();

                foreach ($arrRecords as $arrRecord){
                    $arrTheRecordResult = [];
                    foreach ($arrFields as $oField){
                        $sFieldName= $oField->Name;
                        $value = $arrRecord->{$sFieldName};

                        if (empty($arrNeedFields)){
                            if (is_string($value)){
                                $arrTheRecordResult[$sFieldName] = trim($value);
                            } else {
                                $arrTheRecordResult[$sFieldName] = $value;
                            }
                        } else {
                            if (in_array($sFieldName, (array)$arrNeedFields)){
                                if (is_string($value)){
                                    $arrTheRecordResult[$sFieldName] = trim($value);
                                } else {
                                    $arrTheRecordResult[$sFieldName] = $value;
                                }
                            }
                        }
                    }

                    $arrData[] = $arrTheRecordResult;
                }

            } catch (Exception $exception) {
                throw new Exception("Wmi query class:{$sWmiClass} failed: {$exception->getMessage()}.");
            }
        }

        return $arrData;
    }


    public function getCpuInfo()
    {

    }


    public function getSystemInfo()
    {
        if ($arrResult = $this->getCache('Win32_OperatingSystem')){
            return $arrResult;
        }

        $arrResult = $this->queryWmiClass('Win32_OperatingSystem');

        return $arrResult;
    }


    public function getBuildNumber()
    {

    }


    public function isSupperUser()
    {
        $sCmd = 'net session >nul 2>&1';

        if (system($sCmd, $bIsNormalUser) === false){
            throw new \RuntimeException('Check supper user failed');
        }

        return !$bIsNormalUser;
    }

}
