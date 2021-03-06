<?php
/********************************************************************************************
 *             Copy Right (c) 2022 Capsheaf Co., Ltd.
 *
 *  Author:          Archibald<yangjunjie@capsheaf.com.cn>
 *  Date:            2022-01-19 14:48:40 CST
 *  Description:     TransList.php's function description
 *  Version:         1.0.0.20220119-alpha
 *  History:
 *        Archibald<yangjunjie@capsheaf.com.cn> 2022-01-19 14:48:40 CST initialized the file
 *******************************************************************************************/

namespace ClientApp\Models\Client;

use ClientApp\Models\BaseModel;

class TransList extends BaseModel
{
    protected $m_sTable = 'trans_list';


    public function getTransListCount()
    {
        return $this->M()
            ->whereIn('state', ['WAITING', 'HANDING'])
            ->count();
    }


    public function updateTransList($nTransId, $arrKVPair)
    {
        return $this->M()
            ->where('id', $nTransId)
            ->update($arrKVPair);
    }
}