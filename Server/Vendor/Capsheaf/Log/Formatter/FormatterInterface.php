<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 14:59:20 CST
 *  Description:     FormatterInterface.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 14:59:20 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log\Formatter;

interface FormatterInterface
{

    /**
     * 格式化一条日志记录
     * @param array $arrRecord
     * @return mixed
     */
    public function format($arrRecord = []);


    /**
     * 批量格式化记录
     * @param array $arrRecordsList
     * @return mixed
     */
    public function formatBatch($arrRecordsList = []);

}
