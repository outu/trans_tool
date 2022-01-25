<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-29 21:13:44 CST
 *  Description:     NormalizerFormatter.php's function description
 *  Version:         1.0.0.20180329-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-29 21:13:44 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Log\Formatter;

use Capsheaf\Utils\Types\Json;
use Exception;
use InvalidArgumentException;

class NormalizerFormatter implements FormatterInterface
{

    /**
     * 设置
     * @var array
     */
    protected $m_arrSettings = [];


    public function __construct($arrSettings = [])
    {
        if (!function_exists('json_encode')) {
            throw new \RuntimeException('PHP\'s json extension is required to use Logger\'s NormalizerFormatter');
        }

        $arrDefaultSettings = [
            'datetime' => 'Y-m-d H:i:s.u',
        ];

        $this->m_arrSettings = array_merge($arrDefaultSettings, $arrSettings);
    }


    public function format($arrRecord = [])
    {
        return $this->normalize($arrRecord);
    }


    public function formatBatch($arrRecordsList = [])
    {
        foreach ($arrRecordsList as $key => $record){
            $arrRecordsList[$key] = $this->format($record);
        }

        return $arrRecordsList;
    }


    /**
     * 扁平化复杂的数据，通常用于显示和输出
     * @param mixed $data
     * @return string|array
     */
    public function normalize($data)
    {
        if ($data === null || is_scalar($data)){
            if (is_float($data)){
                if (is_infinite($data)){
                    return ($data > 0 ? '' : '-').'INF';
                }

                if (is_nan($data)){
                    return 'NaN';
                }
            }

            return $data;
        }


        if (is_array($data)){
            $arrNormalize = [];
            $nCount = 1;
            foreach ($data as $key => $value){
                if ($nCount++ >= 1000){
                    $arrNormalize['...'] = 'Over 1000 items ('.count($data).' total), aborting normalization';
                }
                $arrNormalize[$key] = self::normalize($value);
            }

            return $arrNormalize;
        }

        if ($data instanceof \DateTime){
            return $data->format($this->m_arrSettings['datetime']);
        }

        if (is_object($data)){
            //注意即使在PHP5中 instanceof \Throwable不会出错PHP_VERSION_ID判断可以去掉
            if ($data instanceof Exception || (PHP_VERSION_ID > 70000 && $data instanceof \Throwable)){
                return self::normalizeException($data);
            }

            if (method_exists($data, '__toString') && !$data instanceof \JsonSerializable){
                $value = $data->__toString();
            } else {
                $value = Json::toJson($data, true);
            }

            return sprintf("[object] (%s: %s)", get_class($data), $value);
        }

        if (is_resource($data)){
            return sprintf('[resource] (%s)', get_resource_type($data));
        }
    }


    public function normalizeException($exception)
    {
        //注意即使在PHP5中 instanceof \Throwable不会出错
        if (!$exception instanceof Exception && !$exception instanceof \Throwable) {
            throw new InvalidArgumentException('Exception/Throwable expected, got '.gettype($exception).' / '.get_class($exception));
        }

        $arrData = [
            'class'     => get_class($exception),
            'message'   => $exception->getMessage(),
            'code'      => $exception->getCode(),
            'file'      => $exception->getFile().':'.$exception->getLine(),
        ];

        $arrTraces = $exception->getTrace();
        foreach ($arrTraces as $arrTrace){
            if (isset($arrTrace['file'])){
                $arrData['trace'][] = $arrTrace['file'].':'.$arrTrace['line'];
            } else if (isset($arrTrace['function']) && $arrTrace['function'] === '{closure}'){
                $arrData['trace'][] = $arrTrace['function'];
            } else {
                $arrData['trace'][] = Json::toJson($this->normalize($arrTrace), true);
            }
        }

        if ($previous = $exception->getPrevious()) {
            $arrData['previous'] = $this->normalizeException($previous);
        }

        return $arrData;
    }

}
