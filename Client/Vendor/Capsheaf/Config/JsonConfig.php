<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-21 10:32:41 CST
 *  Description:     JsonConfig.php's function description
 *  Version:         1.0.0.20180421-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-21 10:32:41 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Config;

use Capsheaf\FileSystem\FileReadException;
use Capsheaf\FileSystem\FileSystem;
use Capsheaf\Utils\Types\Json;
use Closure;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class JsonConfig extends Config
{

    /**
     * JSON文件路径
     * @var null|string
     */
    protected $m_sConfigFilePath;


    /**
     * JsonConfig constructor.
     * @param null|string $sConfigFilePath JSON文件路径
     */
    public function __construct($sConfigFilePath = null)
    {
        $this->m_sConfigFilePath = $sConfigFilePath;
        $this->merge($this->readFile(), true);
        parent::__construct();
    }


    public function getConfigFilePath()
    {
        return $this->m_sConfigFilePath;
    }


    public function setConfigFilePath($sConfigFilePath)
    {
        $this->m_sConfigFilePath = $sConfigFilePath;

        return $this;
    }


    protected function readFile()
    {
        try {
            $arrConfig = self::readJsonFile($this->m_sConfigFilePath);
        } catch (Exception $exception){
            throw new ConfigException("Config file '{$this->m_sConfigFilePath}' parse failed: {$exception->getMessage()}");
        }

        return $arrConfig;
    }


    /**
     * 解析JSON文件
     * @param string $sJsonFilePath
     * @return array|int|string|float 返回JSON相应的PHP表示
     * @throws Exception
     */
    public static function readJsonFile($sJsonFilePath)
    {
        $arrConfig = null;

        if (FileSystem::isReadable($sJsonFilePath)){
            //注意由于框架会捕捉系统错误，所以很多函数会在notice或者warning的情况下导致抛出异常
            $arrConfig = Json::fromJson(FileSystem::sharedGet($sJsonFilePath), true);

            //null 表示转换失败
            if (is_null($arrConfig)){
                throw new RuntimeException("Json file '{$sJsonFilePath}' parsed failed, maybe a invalid json file.");
            }
        } else {
            throw new FileReadException("Json file '{$sJsonFilePath}' is not readable.");
        }

        return $arrConfig;
    }


    /**
     * 重新初始化加载文件中的配置信息
     * @param array $arrWithExtra 额外的配置
     * @return $this
     */
    public function reload($arrWithExtra = [])
    {
        $arrConfig = $this->readFile();

        $this->merge($arrConfig);
        $this->merge($arrWithExtra);

        return $this;
    }


    /**
     * 修改原始配置文件，并写回到原路径
     * @param Closure $fnModifyCallback 格式为：$arrNew = function($arrRaw)，自动转为JSON
     * @return bool
     */
    public function modify($fnModifyCallback)
    {
        if (!is_callable($fnModifyCallback)){
            throw new InvalidArgumentException('parameter provide is not a callable function while modify config file.');
        }

        $arrRawConfig = $this->readFile();
        $arrNewConfig = $fnModifyCallback($arrRawConfig);

        $sNewConfig = Json::toJson($arrNewConfig, false, JSON_PRETTY_PRINT);

        return file_put_contents($this->m_sConfigFilePath, $sNewConfig, LOCK_EX) !== false;
    }

}
