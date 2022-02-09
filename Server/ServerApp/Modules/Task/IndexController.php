<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-25 17:46:06 CST
 *  Description:     IndexController.php's function description
 *  Version:         1.0.0.20220125-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-25 17:46:06 CST initialized the file
 *******************************************************************************************/

namespace ServerApp\Modules\Task;

use ServerApp\Models\Task\TransTask;
use ServerApp\Models\Task\TransList;
use ServerApp\Modules\BaseController;

class IndexController extends BaseController
{
    public function newTask($sIp, $sPort, $sUser, $sPassword, $sSelectedFile)
    {
        $arrSelectedFile    = explode('|', $sSelectedFile);
        $nSelectedFileCount = count($arrSelectedFile);

        $arrParameters = [
            'arrFtpInfo'      => [
                'sIp'             => $sIp,
                'sPort'           => $sPort,
                'sUser'           => $sUser,
                'sPassword'       => $sPassword,
            ],
            'arrSelectedFile' =>$arrSelectedFile
        ];

        (new TransTask())->addTask($arrParameters);

        return $this->success($nSelectedFileCount);
    }


    public function getCompletedTransList($pagesize, $currentPage)
    {
        $arrFinishedTransList = (new TransList())->getCompletedTransList($pagesize, $currentPage);
        $nFinishedTransList   = (new TransList())->getCompletedTransListCount();

        return $this->success(['list' => $arrFinishedTransList, 'count' => $nFinishedTransList]);
    }
}