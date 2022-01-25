<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-04-09 22:20:55 CST
 *  Description:     Process.php's function description
 *  Version:         1.0.0.20180409-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-04-09 22:20:55 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Process;

use Capsheaf\Process\Exception\ProcessSignaledException;
use Capsheaf\Process\Exception\ProcessTimeoutException;
use Capsheaf\Process\Pipes\AbstractPipes;
use Capsheaf\Process\Pipes\LinuxPipes;
use Capsheaf\Process\Pipes\WindowsPipes;
use InvalidArgumentException;
use Iterator;
use LogicException;
use RuntimeException;

class Process
{

    const STATUS_READY      = 'ready';
    const STATUS_STARED     = 'started';
    const STATUS_TERMINATED = 'terminated';

    const STDIN     = 0;
    const STDOUT    = 1;
    const STDERR    = 2;

    const OUT_TYPE_STDOUT = 'stdout';
    const OUT_TYPE_STDERR = 'stderr';

    /**
     * 超时时间精度（秒）
     */
    const TIMEOUT_PRECISION = 0.2;

    protected $m_commandLine;
    protected $m_sCwd;
    protected $m_arrEnvironment;
    protected $m_input;

    protected $m_nTimeout;
    protected $m_nIdleTimeout;

    protected $m_hStdOut;
    protected $m_hStdErr;
    protected $m_bOutputDisabled;

    protected $m_nIncrementalOutputOffset = 0;
    protected $m_nIncrementalErrorOutputOffset = 0;

    protected $m_hProcess;
    protected $m_arrProcessInformation;
    protected $m_nStartTime;
    protected $m_nLastOutputTime;
    protected $m_nStatus = self::STATUS_READY;
    protected $m_nExitCode;
    protected $m_nLastSignal;

    /**
     * @var AbstractPipes
     */
    protected $m_processPipes;

    protected $m_fnOutputCallback;

    public static $m_arrExitCode = [
        0 => 'OK',
        1 => 'General error',
        2 => 'Misuse of shell builtins',

        126 => 'Invoked command cannot execute',
        127 => 'Command not found',
        128 => 'Invalid exit argument',

        // signals
        129 => 'Hangup',
        130 => 'Interrupt',
        131 => 'Quit and dump core',
        132 => 'Illegal instruction',
        133 => 'Trace/breakpoint trap',
        134 => 'Process aborted',
        135 => 'Bus error: "access to undefined portion of memory object"',
        136 => 'Floating point exception: "erroneous arithmetic operation"',
        137 => 'Kill (terminate immediately)',
        138 => 'User-defined 1',
        139 => 'Segmentation violation',
        140 => 'User-defined 2',
        141 => 'Write to pipe with no one reading',
        142 => 'Signal raised by alarm',
        143 => 'Termination (request to terminate)',
        // 144 - not defined
        145 => 'Child process terminated, stopped (or continued*)',
        146 => 'Continue if stopped',
        147 => 'Stop executing temporarily',
        148 => 'Terminal stop signal',
        149 => 'Background process attempting to read from tty ("in")',
        150 => 'Background process attempting to write to tty ("out")',
        151 => 'Urgent data available on socket',
        152 => 'CPU time limit exceeded',
        153 => 'File size limit exceeded',
        154 => 'Signal raised by timer counting virtual time: "virtual timer expired"',
        155 => 'Profiling timer expired',
        // 156 - not defined
        157 => 'Pollable event',
        // 158 - not defined
        159 => 'Bad syscall',
    ];


    public function __construct($commandLine, $sCwd = null, $arrEnvironment = null, $input = null, $nTimeout = 60, $arrOptions = null)
    {
        if (!function_exists('proc_open')){
            throw new RuntimeException('The Process class relies on proc_open, which is not available on your PHP installation.');
        }

        $this->m_commandLine = $commandLine;
        $this->m_sCwd = $sCwd;

        //windows下或者linux下编译php使用了--enable-maintainer-zts，proc_open时cwd会设置为php二进制的路径
        // @see : https://bugs.php.net/bug.php?id=50524
        if ($this->m_sCwd === null && (defined('ZEND_THREAD_SAFE') || $this->isWindows())){
            $this->m_sCwd = getcwd();
        }

        if ($arrEnvironment !== null){
            $this->setEnvironment($arrEnvironment);
        }

        $this->setInput($input);
        $this->setTimeout($nTimeout);
    }


    /**
     * @param string|array $commandLine
     * @return $this
     */
    public function setCommandLine($commandLine)
    {
        $this->m_commandLine = $commandLine;

        return $this;
    }


    public function getCommandLine()
    {
        return is_array($this->m_commandLine) ? implode(' ', array_map([$this, 'escapeArgument'], $this->m_commandLine)) : $this->m_commandLine;
    }


    /**
     * 设置环境变量，如：['PATH'=>'WILL_OVERWRITE_SYSTEM_PATH']
     * @param array $arrEnvironment
     * @return $this
     */
    public function setEnvironment($arrEnvironment = [])
    {
        //仅仅接受一维数组
        $arrEnvironment = array_filter(
            $arrEnvironment, function($value){
                return !is_array($value);
            }
        );

        $this->m_arrEnvironment = $arrEnvironment;

        return $this;
    }


    /**
     * 获取设置的环境变量
     * @return array
     */
    public function getEnvironment()
    {
        return $this->m_arrEnvironment;
    }


    public function setTimeout($nTimeout)
    {
        $this->m_nTimeout = $this->parseTimeout($nTimeout);

        return $this;
    }


    public function getTimeout()
    {
        return $this->m_nTimeout;
    }


    public function setIdleTimeout($nTimeout)
    {
        if ($nTimeout !== null && $this->m_bOutputDisabled){
            throw new LogicException('Idle timeout can not be set while the output is disabled.');
        }

        $this->m_nIdleTimeout = $this->parseTimeout($nTimeout);

        return $this;
    }


    public function getIdleTimeout()
    {
        return $this->m_nIdleTimeout;
    }


    /**
     * 检查时间设置是否正确
     * @param int|float $nTimeNumber
     * @return float|null
     */
    public function parseTimeout($nTimeNumber)
    {
        $nTimeNumber = (float)$nTimeNumber;
        if ($nTimeNumber === 0.0){
            $nTimeNumber = null;
        } elseif ($nTimeNumber < 0){
            throw new InvalidArgumentException('The timeout value must be a valid positive integer or float number.');
        }

        return $nTimeNumber;
    }


    public function parseInput($input)
    {
        if ($input !== null){
            //允许的input类型
            if (is_string($input) || is_resource($input) || is_array($input) || $input instanceof Iterator){
                return $input;
            }

            if (is_scalar($input)){
                return (string)$input;
            }

            throw new InvalidArgumentException('Only parameter type of string,resource,array,Iterator and scalar allowed.');
        }

        return $input;
    }


    /**
     * 设置当前工作目录
     * @param string $sCwd
     * @return $this
     */
    public function setWorkingDirectory($sCwd)
    {
        $this->m_sCwd = $sCwd;

        return $this;
    }


    /**
     * 获取当前工作目录
     * @return null|string
     */
    public function getWorkingDirectory()
    {
        if ($this->m_sCwd === null){
            //在某些 Unix 的变种下，如果任何父目录没有设定可读或搜索模式，即使当前目录设定了，getcwd() 还是会返回 FALSE。有关模式与权限的更多信息见 chmod()。
            return getcwd() ?: null;
        }

        return $this->m_sCwd;
    }


    public function setInput($input)
    {
        if ($this->isRunning()){
            throw new RuntimeException('Input can not be set while the process is already in running state.');
        }

        $this->m_input = $this->parseInput($input);
    }


    /**
     * 获取设置的Input源
     * @return string|resource|\IteratorAggregate|null
     */
    public function getInput()
    {
        return $this->m_input;
    }


    /**
     * @param callable $fnOutputCallback function ($nType, $sData){} $nType=Process::OUT_TYPE_STDOUT|OUT_TYPE_STDERR, $sData为每次执行该回调时的增量输出字符串
     * @param array $arrEnvironment
     */
    public function start($fnOutputCallback = null, $arrEnvironment = [])
    {
        if ($this->isRunning()){
            throw new RuntimeException('Process is already in running state.');
        }

        //重置最近启动的进程运行数据
        $this->resetProcessData();
        //记录启动时间
        $this->m_nStartTime = $this->m_nLastOutputTime = microtime(true);
        //用指定的回调函数，构建新的回调函数
        $this->m_fnOutputCallback = $this->buildOutputCallback($fnOutputCallback);

        $arrDescriptors = $this->getDescriptors();

        //注意数组的形式传入的才进行转义
        if (is_array($sCommandLine = $this->m_commandLine)){
            $sCommandLine = implode(' ', array_map([$this, 'escapeArgument'], $this->m_commandLine));
        }

        //如果你想完全保留原有数组并只想新的数组附加到后面，用 + 运算符： 第一个数组的键名将会被保留。在两个数组中存在相同的键名时，第一个数组中的【同键名的元素将会被保留】，【第二个数组中的元素将会被忽略】
        if ($this->m_arrEnvironment){
            //构造函数中的环境变量优先级低
            $arrEnvironment += $this->m_arrEnvironment;
        }

        //主进程中的环境变量优先级低
        $arrEnvironment += $this->getDefaultEnvironment();


        //suppress_errors (windows only?): suppresses errors generated by this function when it's set to TRUE
        //bypass_shell (windows only): bypass cmd.exe shell when set to TRUE
        $arrOptions = ['suppress_errors' => true];
        if ($this->isWindows()){
            $arrOptions['bypass_shell'] = true;
            $sCommandLine = $this->prepareWindowsCommandLine($sCommandLine, $arrEnvironment);
        } else {
            $sCommandLine = $this->prepareLinuxCommandLine($sCommandLine, $arrEnvironment);
        }

        //提供proc_open的环境变量
        $arrEnvironmentPairs = [];
        foreach ($arrEnvironment as $sKey => $sValue){
            if ($sValue !== false){
                $arrEnvironmentPairs[] = $sKey.'='.$sValue;
            }
        }


        if (!is_dir($this->m_sCwd)){
            throw new RuntimeException("Current working directory {$this->m_sCwd} does not exist.");
        }

        $this->m_hProcess = proc_open($sCommandLine, $arrDescriptors, $this->m_processPipes->getPipes(), $this->m_sCwd, $arrEnvironmentPairs, $arrOptions);

        if (!is_resource($this->m_hProcess)){
            throw new RuntimeException('Unable to launch the process.');
        }

        //标记进程已经启动
        $this->m_nStatus = self::STATUS_STARED;

        $this->updateStatus(false);
        $this->checkTimeout();
    }


    /**
     * 启动+等待进程运行结束
     * @param callable $fnOutputCallback
     * @param array $arrEnvironment
     * @return int 返回进程的Exit Code
     * @see Process::start()  参考start的参数
     */
    public function run($fnOutputCallback = null, $arrEnvironment = [])
    {
        $this->start($fnOutputCallback, $arrEnvironment);

        return $this->wait();
    }


    public function mustRun()
    {

    }


    /**
     * @param callable $fnOutputCallback
     * @return int 返回进程的Exit Code
     */
    public function wait($fnOutputCallback = null)
    {
        $this->requireProcessIsStarted(__FUNCTION__);
        $this->updateStatus(false);

        if ($fnOutputCallback !== null){
            if (!$this->m_processPipes->isWantOutput()){
                $this->stop(0);
                throw new LogicException('Pass a callback to the Process::start method or enableOutput to use a callback with Process::wait');
            }

            $this->m_fnOutputCallback = $fnOutputCallback;
        }

        do{
            $this->checkTimeout();
            $bRunning = $this->isWindows() ? $this->isRunning() : $this->m_processPipes->areOpen();
            $this->readPipes($bRunning, !$this->isWindows() || !$bRunning);
        } while ($bRunning);

        //做完最后的事？
        while ($this->isRunning()){
            usleep(1000);
        }

        //要是程序被终止信号终止，但是终止信号并不是由本程序终止的
        if ($this->m_arrProcessInformation['signaled'] && $this->m_arrProcessInformation['termsig'] !== $this->m_nLastSignal){
            throw new ProcessSignaledException($this);
        }

        return $this->m_nExitCode;
    }


    /**
     * 准备windows命令
     * @param $sCommandLine
     * @param array &$arrEnvironment 注意命令行通过!var!的形式解释可以避免一些问题
     * @return string
     */
    protected function prepareWindowsCommandLine($sCommandLine, &$arrEnvironment = [])
    {
        //每次生成的都不一样
        //5acdc97b38a2f6.16879290
        $sUid = uniqid('', true);
        $arrVarCount = 0;
        $arrVarCache = [];

        //x (PCRE_EXTENDED)
        $sCommandLine = preg_replace_callback(
            '/"(?:(
                [^"%!^]*+
                (?:
                    (?: !LF! | "(?:\^[%!^])?+" )
                    [^"%!^]*+
                )++
            ) | [^"]*+ )"/x',
            function ($arrMatches) use (&$arrEnvironment, &$arrVarCache, &$arrVarCount, $sUid){
                if (!isset($arrMatches[1])){
                    return $arrMatches[0];
                }
                if (isset($arrVarCache[$arrMatches[0]])){
                    return $arrVarCache[$arrMatches[0]];
                }

                if (false !== strpos($sMatch = $arrMatches[1], "\0")) {
                    $sMatch = str_replace("\0", '?', $sMatch);
                }
                if (false === strpbrk($sMatch, "\"%!\n")) {
                    return '"'.$sMatch.'"';
                }
                $sMatch = str_replace(['!LF!', '"^!"', '"^%"', '"^^"', '""'], ["\n", '!', '%', '^', '"'], $sMatch);
                $sMatch = '"'.preg_replace('/(\\\\*)"/', '$1$1\\"', $sMatch).'"';

                //组装键值
                $sKey = $sUid.++$arrVarCount;
                //设置引用的数组
                $arrEnvironment[$sKey] = $sMatch;
                //设置缓存并返回
                return $arrVarCache[$arrMatches[0]] = '!'.$sKey.'!';
            },
            $sCommandLine
        );

        //$sChangeCodePage = '@chcp 65001 >nul 2>&1 & ';//可以在构造Process时前面加上这句，效果一致
        //$sCommandLine = $sChangeCodePage.$sCommandLine;

        //cmd的作用：启动 Windows 命令解释器的一个新实例
        ///V:ON   使用 ! 作为分隔符启用延迟的环境变量
        //        扩展。例如，/V:ON 会允许 !var! 在执行时
        //        扩展变量 var。var 语法会在输入时
        //        扩展变量，这与在一个 FOR
        //        循环内不同。
        ///E:ON   启用命令扩展(见下)
        ///D      禁止从注册表执行 AutoRun 命令(见下)
        ///C      执行字符串指定的命令然后终止
        $sCommandLine = 'cmd /V:ON /E:ON /D /C ('.str_replace("\n", ' ', $sCommandLine).')';

        //构建stdout和stderr重定向的命令行
        foreach ($this->m_processPipes->getFiles() as $nPipeStdNumber => $sFilePath){
            $sCommandLine .= ' '.$nPipeStdNumber.'>"'.$sFilePath.'"';
        }

        //$arrEnvironment['5acdc5a89c0260.901110641']->"ping www.baidu.com ^ & 6 %AA!BB\"CC\DD| 1233 * +>\\\nping www.ifeng.com ^ & 6 %AA!BB\"CC\DD| 1233 * +>\\\\";注意自带双引号
        //cmd /V:ON /E:ON /D /C (!5acdc5a89c0260.901110641!) 1>"D:\Progrem\111.txt" 2>"D:\Progrem\222.txt"
        return $sCommandLine;
    }


    /**
     * 准备linux命令
     * @param string $sCommandLine
     * @param array $arrEnvironment
     * @return string
     */
    protected function prepareLinuxCommandLine($sCommandLine, &$arrEnvironment = [])
    {
        //see http://php.net/manual/en/function.proc-get-status.php#93382
        //Linux下不使用exec 时获取到的是调用进程的pid
        return 'exec '.$sCommandLine;
    }


    /**
     * 获取各自操作系统的管道描述数组
     * @return array
     */
    protected function getDescriptors()
    {
        if ($this->m_input instanceof Iterator){
            $this->m_input->rewind();
        }

        if ($this->isWindows()){
            $this->m_processPipes = new WindowsPipes($this->m_input, (!$this->m_bOutputDisabled) || ($this->m_fnOutputCallback !== null));
        } else {
            $this->m_processPipes = new LinuxPipes($this->m_input, (!$this->m_bOutputDisabled) || ($this->m_fnOutputCallback !== null));
        }

        return $this->m_processPipes->getDescriptors();
    }


    /**
     * 检测运行超时情况，运行超时或者IDLE超时时抛出异常
     * @return void 注意不返回任何信息
     * @throws ProcessTimeoutException
     */
    public function checkTimeout()
    {
        //不在运行状态则不判断
        if ($this->m_nStatus !== self::STATUS_STARED){
            return;
        }

        if ($this->m_nTimeout !== null && $this->m_nTimeout < (microtime(true) - $this->m_nStartTime)){
            throw new ProcessTimeoutException($this, ProcessTimeoutException::TYPE_GENERAL);
        }

        if ($this->m_nIdleTimeout !== null && $this->m_nIdleTimeout < (microtime(true) - $this->m_nLastOutputTime)){
            throw new ProcessTimeoutException($this, ProcessTimeoutException::TYPE_IDLE);
        }
    }


    /**
     * 获取该进程全部的输出
     */
    public function getOutput()
    {
        if (($sOutput = stream_get_contents($this->m_hStdOut, -1, 0)) === false){
            return '';
        }

        return $sOutput;
    }


    /**
     * 获取输出的新增的部分
     */
    public function getIncrementalOutput()
    {
        $sLatest = stream_get_contents($this->m_hStdOut, -1, $this->m_nIncrementalOutputOffset);
        $this->m_nIncrementalOutputOffset = ftell($this->m_hStdOut);

        if ($sLatest === false){
            return '';
        }

        return $sLatest;
    }


    /**
     * 清空输出
     */
    public function clearOutput()
    {
        ftruncate($this->m_hStdOut, 0);
        fseek($this->m_hStdOut, 0);
        $this->m_nIncrementalOutputOffset = 0;

        return $this;
    }


    public function getErrorOutput()
    {
        if (($sOutput = stream_get_contents($this->m_hStdErr, -1, 0)) === false){
            return '';
        }

        return $sOutput;
    }


    public function getIncrementErrorOutput()
    {
        $sLatest = stream_get_contents($this->m_hStdErr, -1, $this->m_nIncrementalErrorOutputOffset);
        $this->m_nIncrementalErrorOutputOffset = ftell($this->m_hStdErr);

        if ($sLatest === false){
            return '';
        }

        return $sLatest;
    }


    public function clearErrorOutput()
    {
        ftruncate($this->m_hStdErr, 0);
        fseek($this->m_hStdErr, 0);
        $this->m_nIncrementalErrorOutputOffset = 0;

        return $this;
    }


    protected function readPipesForOutput($sWhoRequire, $bBlocking = false)
    {
        if ($this->m_bOutputDisabled){
            throw new LogicException('Output has been disabled.');
        }

        $this->requireProcessIsStarted($sWhoRequire);

        $this->updateStatus($bBlocking);
    }


    public function isRunning()
    {
        //不在正在运行中则直接返回false
        if ($this->m_nStatus !== self::STATUS_STARED) {
            return false;
        }

        //更新状态
        $this->updateStatus(false);

        //返回更新后的状态情况
        return $this->m_arrProcessInformation['running'];
    }


    /**
     * 判断进程是否已经启动
     * @return bool
     */
    public function isStarted()
    {
        //started和terminated都算作已经启动
        return $this->m_nStatus != self::STATUS_READY;
    }


    /**
     * 判断进程是否已经终止
     * @return bool
     */
    public function isTerminated()
    {
        $this->updateStatus(false);

        return $this->m_nStatus == self::STATUS_TERMINATED;
    }


    /**
     * 判断要求进程已经启动是否符合条件，需要的状态不正确则抛出异常
     * @param string $sWhoRequire
     * @throws LogicException
     */
    protected function requireProcessIsStarted($sWhoRequire)
    {
        if (!$this->isStarted()){
            throw new LogicException(sprintf('Process must be started before calling %s.', $sWhoRequire));
        }
    }


    /**
     * 判断要求进程已经终止是否符合条件，需要的状态不正确则抛出异常
     * @param string $sWhoRequire
     * @throws LogicException
     */
    protected function requireProcessIsTerminated($sWhoRequire)
    {
        if (!$this->isTerminated()){
            throw new LogicException(sprintf('Process must be terminated before calling %s.', $sWhoRequire));
        }
    }


    public function stop($nTimeout = 10, $nSignal = null)
    {
        //要超时的时间点
        $nTimeoutPoint = microtime(true) + $nTimeout;

        if ($this->isRunning()){
            $this->doSignal(15, false);
            do {
                //每隔1s检测一次
                usleep(1000);
            } while ($this->isRunning() && microtime(true) < $nTimeoutPoint);//还在运行并且未超时

            //若超时了还在运行就使用信号常量9
            if ($this->isRunning()){
                $this->doSignal($nSignal ?: 9, false);
            }

            $this->close();
        }

        return $this->m_nExitCode;
    }


    /**
     * 关闭进程资源
     * @return int
     */
    public function close()
    {
        $this->m_processPipes->close();
        if (is_resource($this->m_hProcess)){
            proc_close($this->m_hProcess);
        }

        $this->m_nExitCode = $this->m_arrProcessInformation['exitcode'];
        $this->m_nStatus = self::STATUS_TERMINATED;

        if ($this->m_nExitCode === -1){
            if ($this->m_arrProcessInformation['signaled'] && $this->m_arrProcessInformation['termsig'] > 0){
                $this->m_nExitCode = 128 + $this->m_arrProcessInformation['termsig'];
            }
        }

        $this->m_fnOutputCallback = null;

        return $this->m_nExitCode;
    }


    public function enableOutput()
    {
        if ($this->isRunning()){
            throw new RuntimeException('Enabling output while the process is running is not possible.');
        }

        $this->m_bOutputDisabled = false;

        return $this;
    }


    public function disableOutput()
    {
        if ($this->isRunning()){
            throw new RuntimeException('Disabling output while the process is running is not possible.');
        }

        if ($this->m_nIdleTimeout !== null){
            throw new LogicException('Output can not be disabled while an idle timeout is set.');
        }

        $this->m_bOutputDisabled = true;

        return $this;
    }


    protected function isWindows()
    {
        return windows_os();
    }


    /**
     * 获取主进程默认的已经存在的环境变量
     * @return array
     */
    protected function getDefaultEnvironment()
    {
        $arrEnvironment = [];

        foreach($_SERVER as $sKey => $value){
            if (is_string($value) && (getenv($sKey) !== false)){
                $arrEnvironment[$sKey] = $value;
            }
        }

        foreach($_ENV as $sKey => $value){
            if (is_string($value)){
                $arrEnvironment[$sKey] = $value;
            }
        }

        return $arrEnvironment;
    }


    /**
     * 转义windows参数，如：
     * 'ping www.baidu.com ^ & 6 %AA!BB"CC\\DD| 12'."\n".'33 * +>\\\\'
     * "ping www.baidu.com "^^" & 6 "^%"AA"^!"BB""CC\DD| 12!LF!33 * +>\\\\"
     * @param string $sArgument
     * @return mixed|null|string|string[]
     */
    private function escapeArgument($sArgument)
    {
        //adds single quotes around a string and quotes/escapes any existing single quotes allowing you to pass a string directly to a shell function and having it be treated as a single safe argument.
        //On Windows, escapeshellarg() instead replaces percent signs, exclamation marks (delayed variable substitution) and double quotes with spaces and adds double quotes around the string.
        //return escapeshellarg($sArgument);具有局限性

        if (!$this->isWindows()){
            //Linux下仅仅需要外部使用单引号【'】，内部的单引号替换成【'\''】
            return "'".str_replace("'", "'\\''", $sArgument)."'";
        }

        //以下都是对windows的参数进行处理

        //空的参数
        if (($sArgument = (string)$sArgument) === ''){
            return '""';
        }

        //\0替换成问号
        if (strpos($sArgument, "\0") !== false){
            $sArgument = str_replace("\0", '?', $sArgument);
        }

        //未匹配到【/()%!^"<>&|空白】中的任意一个则直接返回
        if (!preg_match('/[\/()%!^"<>&|\s]/', $sArgument)) {
            return $sArgument;
        }

        //行末最后的每个\都替换为\\，eg:\->\\,\\->\\\\,\A->\A
        $sArgument = preg_replace('/(\\\\+)$/', '$1$1', $sArgument);

        //替换特殊字符
        //see http://www.robvanderwoude.com/escapechars.php
        $sArgument = str_replace(
            [
                '"',
                '^',
                '%',
                '!',
                "\n"
            ],[
                '""',
                '"^^"',
                '"^%"',
                '^^!',
                '!LF!'
            ], $sArgument
        );

        return '"'.$sArgument.'"';
    }


    protected function readPipes($bBlocking, $bClose)
    {
        $arrReadGotStrings = $this->m_processPipes->readAndWrite($bBlocking, $bClose);

        foreach ($arrReadGotStrings as $nPipeStdNumber => $sReadGotString){
            call_user_func(
                $this->m_fnOutputCallback,
                $nPipeStdNumber == self::STDOUT ? self::OUT_TYPE_STDOUT : self::OUT_TYPE_STDERR,
                $sReadGotString
            );
        }
    }


    protected function updateStatus($bBlocking)
    {
        if ($this->m_nStatus !== self::STATUS_STARED){
            return;
        }

        //see http://php.net/manual/en/function.proc-get-status.php
        //An array of collected information on success, and FALSE on failure.
        //command 	string 	The command string that was passed to proc_open().
        //pid 	    int 	process id
        //running 	bool 	TRUE if the process is still running, FALSE if it has terminated.
        //signaled 	bool 	TRUE if the child process has been terminated by an uncaught signal. Always set to FALSE on Windows.
        //stopped 	bool 	TRUE if the child process has been stopped by a signal. Always set to FALSE on Windows.
        //exitcode 	int 	The exit code returned by the process (which is only meaningful if running is FALSE). Only first call of this function return real value, next calls return -1.
        //termsig 	int 	The number of the signal that caused the child process to terminate its execution (only meaningful if signaled is TRUE).
        //stopsig 	int 	The number of the signal that caused the child process to stop its execution (only meaningful if stopped is TRUE).
        $this->m_arrProcessInformation = proc_get_status($this->m_hProcess);
        $bRunning = $this->m_arrProcessInformation['running'];//true或false

        $this->readPipes(
            $bRunning && $bBlocking,
            (!$this->isWindows()) || !$bRunning //不是windows或者没有在运行
        );

        if (!$bRunning){
            $this->close();
        }
    }


    /**
     * 获取该进程的pid，进程已经未运行则返回null，不要在进程的输出回调函数中使用，否则无限递归
     * @return null|int
     */
    public function getPid()
    {
        return $this->isRunning() ? $this->m_arrProcessInformation['pid'] : null;
    }


    public function signal($nSignalNumber)
    {
        $this->doSignal($nSignalNumber, true);

        return $this;
    }


    private function doSignal($nSignalNumber, $bThrowOnError)
    {
        if (($nPid = $this->getPid()) === null){
            if ($bThrowOnError){
                throw new LogicException('Can not send signal on a non running process.');
            }

            return false;
        }

        //windows下用taskkill
        if ($this->isWindows()){
            exec(sprintf('taskkill /F /T /PID %d 2>&1', $nPid), $sOutput, $nExitCode);

            //若返回表示错误的返回代码，并且进行还在运行
            if ($nExitCode && $this->isRunning()){
                if ($bThrowOnError){
                    throw new RuntimeException(sprintf('Unable to kill the process (%s).', implode(' ', $sOutput)));
                }

                return false;
            }
        } else {
            $bKilled = proc_terminate($this->m_hProcess, $nSignalNumber);
            if (!$bKilled){
                if ($bThrowOnError){
                    throw new RuntimeException(sprintf('Error while sending signal `%s` to child process.', $nSignalNumber));
                }

                return false;
            }
        }

        $this->m_nLastSignal = $nSignalNumber;

        return true;
    }


    public function getTermSignal()
    {
        $this->requireProcessIsTerminated(__FUNCTION__);

        return $this->m_arrProcessInformation['termsig'];
    }


    /**
     * TRUE if the child process has been terminated by an uncaught signal. Always set to FALSE on Windows.
     * @return bool
     */
    public function hasBeenSignaled()
    {
        $this->requireProcessIsTerminated(__FUNCTION__);

        return $this->m_arrProcessInformation['signaled'];
    }


    /**
     * 获取进程退出码
     * @return int
     */
    public function getExitCode()
    {
        $this->updateStatus(false);

        return $this->m_nExitCode;
    }


    /**
     * 获取退出码的文字表示
     * @return null|string
     */
    public function getExitCodeText()
    {
        if (($nExitCode = $this->getExitCode()) ===  null){
            return null;
        }

        return isset(self::$m_arrExitCode[$nExitCode]) ? self::$m_arrExitCode[$nExitCode] : 'Unknown error';
    }


    /**
     * 通过检查退出码是否为0来判断启动的这个进程是否成功运行
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getExitCode() === 0;
    }


    /**
     * 构建回调函数，一旦是需要输出的，则根据读取的数据，写入到对应的新的php://temp处
     * @param callable|null $fnCallback
     * @return callable
     */
    public function buildOutputCallback($fnCallback = null)
    {
        if ($this->m_bOutputDisabled){
            return function ($sType, $sData) use ($fnCallback){
                if ($fnCallback !== null){
                    call_user_func($fnCallback, $sType, $sData);
                }
            };
        }

        $sTypeStdout = self::OUT_TYPE_STDOUT;
        return function ($sType, $sData) use ($fnCallback, $sTypeStdout){
            if ($sType == $sTypeStdout){
                $this->addOutput($sData);
            } else {
                $this->addErrorOutput($sData);
            }

            if ($fnCallback !== null){
                call_user_func($fnCallback, $sType, $sData);
            }
        };
    }


    /**
     * 重置最近运行的进程的相关信息，即可以多次启动
     */
    public function resetProcessData()
    {
        $this->m_nStartTime = null;
        $this->m_fnOutputCallback = null;
        $this->m_nExitCode = null;
        $this->m_arrProcessInformation = null;
        //see http://php.net/manual/en/wrappers.php.php#wrappers.php.memory
        $this->m_hStdOut = fopen('php://temp/maxmemory:'.(1024 * 1024), 'wb+');
        $this->m_hStdErr = fopen('php://temp/maxmemory:'.(1024 * 1024), 'wb+');
        $this->m_nIncrementalOutputOffset = 0;
        $this->m_nIncrementalErrorOutputOffset = 0;
        $this->m_hProcess = null;
        $this->m_nStatus = self::STATUS_READY;
        $this->m_nLastSignal = null;
    }


    public function addOutput($sData)
    {
        $this->m_nLastOutputTime = microtime(true);

        fseek($this->m_hStdOut, 0, SEEK_END);
        fwrite($this->m_hStdOut, $sData);
        //还原文件指针到上次读取完后的位置
        fseek($this->m_hStdOut, $this->m_nIncrementalOutputOffset);
    }


    public function addErrorOutput($sData)
    {
        $this->m_nLastOutputTime = microtime(true);

        fseek($this->m_hStdErr, 0, SEEK_END);
        fwrite($this->m_hStdErr, $sData);
        //还原文件指针到上次读取完后的位置
        fseek($this->m_hStdErr, $this->m_nIncrementalErrorOutputOffset);
    }

}
