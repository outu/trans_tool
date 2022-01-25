<?php
/**
 * Created by PhpStorm.
 * User: YANTAO
 * Date: 2019/3/6
 * Time: 14:47
 */

namespace CapsheafBuilder\Models\Git;

use Capsheaf\Process\Process;
use RuntimeException;

class Git
{

    /**
     * @param null $sGitRepoDir
     * @return string
     * @throws RuntimeException
     */
    public function getCurrentVersion($sGitRepoDir = null)
    {
        $sCmd = 'git rev-parse --short HEAD';
        $process = new Process($sCmd, $sGitRepoDir);
        if ($process->run() == 0){
            $sOutput = $process->getOutput();

            return trim($sOutput);
        } else {
            throw new RuntimeException($process->getErrorOutput());
        }
    }

}