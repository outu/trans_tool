<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-02-09 09:55:29 CST
 *  Description:     TransList.php's function description
 *  Version:         1.0.0.20220209-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-02-09 09:55:29 CST initialized the file
 *******************************************************************************************/

namespace ServerApp\Models\Task;

use ServerApp\Models\BaseModel;

class TransList extends BaseModel
{
    protected $m_sTable     = 'trans_list';


    public function getCompletedTransList($pagesize, $currentPage)
    {
        return $this->M()
            ->where('state', 'HANDED')
            ->orderBy('id')
            //->limit($pagesize)
            ->get();
    }


    public function getCompletedTransListCount()
    {
        return $this->M()
            ->where('state', 'HANDED')
            ->count();
    }


    public function getInCompleteTransList($pagesize, $currentPage)
    {
        return $this->M()
            ->whereIn('state', ['WAITING', 'HANDING', "OFFLINE"])
            ->orderBy('id')
            //->limit($pagesize)
            ->get();
    }


    public function getInCompleteTransListCount()
    {
        return $this->M()
            ->whereIn('state', ['WAITING', 'HANDING', "OFFLINE"])
            ->count();
    }
}