<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-30 17:15:21 CST
 *  Description:     MemoryUsageProcessor.php's function description
 *  Version:         1.0.0.20180330-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-30 17:15:21 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log\MessageProcessor;

class MemoryUsageProcessor
{

    /**
     * 每个类类型的Processor都应该实现__invoke方法，参数和返回值均为$arrRecord数组
     * @param array $arrRecord
     * @return array
     */
    public function __invoke($arrRecord)
    {
        $arrRecord['extra']['memory_usage'] = memory_get_usage(true);
        return $arrRecord;
    }

}
