<?php
/*******************************************************************************
 *             Copy Right (c) 2017 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2017-12-20 17:02:44 CST
 *  Description:     Application.php's function description
 *  Version:         1.0.0.20171220-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2017-12-20 17:02:44 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Application;

use Capsheaf\Exception\ExceptionFormatter;
use Capsheaf\Exception\ExceptionHandler;
use Capsheaf\Facades\AbstractFacades;
use Exception;

class Application extends Container
{

    /**
     * 容器类单实例
     * @var null
     */
    protected static $m_oSelfInstance = null;

    /**
     * @var array 实例和单例模式实例化后的对象的绑定
     */
    protected $m_arrInstances = [];


    /**
     * @var string 当前App的绝对路径
     */
    protected $m_sAppPath = '';


    public function __construct($sAppPath = '')
    {
        //带后缀/
        $this->m_sAppPath = $sAppPath;

        $this->registerBaseBindings();
        $this->registerExceptionHandler();
        $this->registerCoreContainerAliases();

    }


    public function registerExceptionHandler()
    {
        $handler = new ExceptionHandler($this);
        $handler->registerHandlers();
    }


    /**
     * 容器的绑定操作，保证以后对基础容器Container的解析都解析到Application实例上来
     */
    public function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->addAlias($this, __CLASS__);

        AbstractFacades::setContainer($this);

        //$serviceBinder = new CoreServiceProvider($this);
        //$serviceBinder->bindServices();
    }


    /**
     * 容器类是一个单例
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$m_oSelfInstance)) {
            static::$m_oSelfInstance = new static;
        }

        return static::$m_oSelfInstance;
    }


    public function renderException(Exception $exception)
    {
        //echo ">RENDER EXCEPTION\r\n";
        //$this['log']->critical('Exception', [$e]);
        //echo ">Exception:\r\n";
        ExceptionFormatter::renderException($exception);
    }


    protected function registerCoreContainerAliases()
    {
        //每个实际的组件对应的数个别名，用于从容器中直接获取对象的键名称
        $arrAliases = [
            //实际要映射到的=> [别名列表]
            'app'           => ['Capsheaf\Application\Application'],
            'files'         => ['Capsheaf\FileSystem\FileSystem'],
            'hooks'         => ['Capsheaf\Plugin\Hooks'],
        ];

        foreach ($arrAliases as $sRealTargetName => $arrAlias){
            foreach ($arrAlias as $sAlias){
                $this->addAlias($sRealTargetName, $sAlias);
            }
        }
    }


    public function addAlias($sRealTargetName, $sAlias)
    {
        //别名=>实际
        //抽象=>实际
        //实际的会在某处实例化
        //eg: m_arrAliases['\TFramework\FileSystem\FileSystem::class'] => 'files'
        //eg: m_arrAliases['\TFramework\XXX\Dispatcher::class'] => 'event'
        //eg: m_arrAliases['\TFramework\XXX\AbstractDispatcher::class'] => '\TFramework\XXX\Dispatcher::class'
        $this->m_arrAliases[$sAlias] = $sRealTargetName;
    }


    /**
     * @param string $sAbstract
     * @param null $concrete
     * @param array $arrAlias
     * @return $this
     * @throws ContainerException 当指定的一个别名和具体的实现类名一致时抛出，不允许这种情况
     */
    public function singleton($sAbstract, $concrete = null, $arrAlias = [])
    {
        parent::singleton($sAbstract, $concrete);
        if (!empty($arrAlias)){
            foreach ((array)$arrAlias as $sAlias){
                if ($sAlias === $concrete) {
                    throw new ContainerException("Alias name: {$sAlias} is same to the concrete class.");
                }

                $this->addAlias($sAbstract, $sAlias);
            }
        }

        return $this;
    }


    /**
     * @param string $sAbstract
     * @param null $concrete
     * @param array $arrAlias
     * @return $this
     * @throws ContainerException 当指定的一个别名和具体的实现类名一致时抛出，不允许这种情况
     */
    public function factory($sAbstract, $concrete = null, $arrAlias = [])
    {
        parent::factory($sAbstract, $concrete);
        if (!empty($arrAlias)){
            foreach ((array)$arrAlias as $sAlias){
                if ($sAlias === $concrete) {
                    throw new ContainerException("Alias name: {$sAlias} is same to the concrete class.");
                }

                $this->addAlias($sAbstract, $sAlias);
            }
        }

        return $this;
    }


    /**
     * @param string $sAbstract
     * @param object $oInstance
     * @param array|string $arrAlias
     * @return $this
     */
    public function instance($sAbstract, $oInstance, $arrAlias = [])
    {
        parent::instance($sAbstract, $oInstance);
        if (!empty($arrAlias)){
            foreach ((array)$arrAlias as $sAlias){
                $this->addAlias($sAbstract, $sAlias);
            }
        }

        return $this;
    }

}
