<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-04 10:16:32 CST
 *  Description:     ExceptionFormatter.php's function description
 *  Version:         1.0.0.20180404-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-04 10:16:32 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Exception;

use Exception;

class ExceptionFormatter
{

    public static function renderException(Exception $exception)
    {
        //var_dump($exception);
        $arrData = [
            'class'     => get_class($exception),
            'message'   => $exception->getMessage(),
            'code'      => $exception->getCode(),
            'file'      => $exception->getFile().':'.$exception->getLine(),
        ];

        $arrTraces = $exception->getTrace();
        foreach ($arrTraces as $arrTrace){
            $arrData['trace'][] = $arrTrace;
        }

        if (isset(app()['log'])){
            app('log')->critical("{$arrData['class']}: {$arrData['message']}. thrown in {$arrData['file']}", ['trace' => explode("\n", $exception->getTraceAsString())]);
        }

        echo PHP_EOL.'Exception:'.PHP_EOL;
        echo "{$arrData['class']}: {$arrData['message']}. thrown in {$arrData['file']}".PHP_EOL;
        echo $exception->getTraceAsString().PHP_EOL;
    }

}
