<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-14 21:45:51 CST
 *  Description:     Query.php's function description
 *  Version:         1.0.0.20180514-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-14 21:45:51 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Utils\Query;

class LineQuery
{

    /**
     * @param string $sAllString
     * @param string $sPregPattern
     * @return Collection
     */
    public static function query($sAllString = '', $sPregPattern = '')
    {
        $arrLines = preg_split('/\R/', $sAllString);
        $collection = Collection::createRootCollection(new Lines($arrLines));

        return $collection->query($sPregPattern);
    }

}
