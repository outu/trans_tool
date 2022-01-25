<?php
/******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-03-14 14:05:17 CST
 *  Description:     IndexController.php's function description
 *  Version:         1.0.0.20180314-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-03-14 14:05:17 CST initialized the file
 ******************************************************************************/

namespace ServerApp\Modules\Index;

use Capsheaf\Process\Process;
use CapsheafBuilder\Models\Git\Git;
use CapsheafBuilder\Modules\BaseController;
use Noodlehaus\Exception;

class IndexController extends BaseController
{

    public function index()
    {
        return $this->success();
    }


    public function getGitVersion($sGitRepoDir = null)
    {
        try {
            $sVersion = (new Git())->getCurrentVersion($sGitRepoDir);

            return $this->success($sVersion);
        } catch (Exception $exception) {
            return $this->error($exception);
        }
    }


}