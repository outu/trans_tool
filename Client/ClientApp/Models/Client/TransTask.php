<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 14:46:02 CST
 *  Description:     TransTask.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 14:46:02 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Models\Client;

use ClientApp\Models\BaseModel;

class TransTask extends BaseModel
{
    protected $m_sTable = 'trans_task';

    /**
     * 查询数据库任务表
     * @return array|false
     */
    public function getTransTask()
    {
        return $this->M()
            ->whereIn('state', ['TASK_WAITING', 'TASK_SCANNING'])
            ->get();
    }


    public function updateTaskStatus($nTaskId, $sState)
    {
        return $this->M()
            ->where('id', $nTaskId)
            ->update(['state' => $sState]);
    }


    public function getScanTransTask()
    {
        return $this->M()
            ->where('state', 'TASK_SCANNED')
            ->get();
    }

}