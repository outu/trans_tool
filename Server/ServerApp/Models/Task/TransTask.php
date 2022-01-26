<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-26 15:42:33 CST
 *  Description:     TransTask.php's function description
 *  Version:         1.0.0.20220126-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-26 15:42:33 CST initialized the file
 *******************************************************************************************/

namespace ServerApp\Models\Task;

use Capsheaf\Utils\Types\Json;
use ServerApp\Models\BaseModel;
use DateTime;

class TransTask extends BaseModel
{
    protected $m_sTable     = 'trans_task';

    public function addTask($arrKVPair)
    {
        $arrParameter = [
            'meta'       => Json::toJson($arrKVPair),
            'state'      => 'TASK_WAITING',
            'created_at' => new DateTime()
        ];

        return $this->M()
            ->insert($arrParameter);
    }
}
