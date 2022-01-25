<?php
/*******************************************************************************
 *             Copy Right (c) 2018 Capsheaf Co., Ltd.
 *
 *  Author:          tsoftware<admin@yantao.info>
 *  Date:            2018-05-21 17:58:34 CST
 *  Description:     Model.php's function description
 *  Version:         1.0.0.20180521-alpha
 *  History:
 *        tsoftware<admin@yantao.info> 2018-05-21 17:58:34 CST initialized the file
 ******************************************************************************/

namespace Capsheaf\Database;

use Capsheaf\Database\Exception\NoRecordFoundException;
use Capsheaf\Facades\AutoLoad\DB;
use DateTime;
use LogicException;

class Model
{

    /**
     * @var null|string
     */
    protected $m_sTable;


    /**
     * Model constructor.
     * @param null|string $sTable 数据库表名，若未指定则使用$m_sTable成员变量，可以在子类中覆盖
     */
    public function __construct($sTable = null)
    {
        if (!is_null($sTable)){
            $this->m_sTable = $sTable;
        }
    }


    /**
     * 获取底层查询构造器以便于进行高级查询
     * @param null|string|RawSql $sTable
     * @return QueryBuilder
     */
    public function M($sTable = null)
    {
        if (is_null($sTableToUse = $sTable) && is_null($sTableToUse = $this->m_sTable)){
            throw new LogicException('Table is empty.');
        }

        $m = DB::table($sTableToUse);
        return $m;
    }


    /**
     * 获取指定ID的数据库记录（单条）
     * @param int $nId
     * @param bool $bIncludeDeleted 删除的是否包含在其中
     * @param array $arrColumns
     * @return array|null
     */
    public function find($nId, $bIncludeDeleted = true, $arrColumns = ['*'])
    {
        if ($bIncludeDeleted){
            return $this->M()->find($nId, $arrColumns);
        }

        return $this->M()
            ->where(['deleted_at', '<>', 0])
            ->find($nId, $arrColumns);
    }


    /**
     * 获取指定ID的数据库记录，没有记录则抛出异常
     * @param int $nId
     * @param bool $bIncludeDeleted
     * @param string $sException
     * @param array $arrColumns
     * @return array|null
     * @throws NoRecordFoundException
     */
    public function findOrFailed($nId, $bIncludeDeleted = true, $sException = 'No record found for id %d in table %s.', $arrColumns = ['*'])
    {
        if ($arrRecord = $this->find($nId, $bIncludeDeleted, $arrColumns)){
            return $arrRecord;
        }

        throw new NoRecordFoundException(sprintf($sException, $nId, $this->m_sTable));
    }


    /**
     * 查询全部记录
     * @param bool $bIncludeDeleted 是否deleted_at不等于0的结果包含在结果集中
     * @param array $arrWheres
     * @param array $arrColumns 简单的Where筛选条件，为空数组时表示不筛选
     * @return array|null
     */
    public function all($bIncludeDeleted = false, $arrWheres = [], $arrColumns = ['*'])
    {
        if ($bIncludeDeleted){
            return $this->M()->where($arrWheres)->get($arrColumns);
        }

        return $this->M($this->m_sTable)
            ->where($arrWheres)
            ->where('deleted_at', '<>', '0')
            ->get($arrColumns);
    }


    /**
     * INSERT新的记录，并返回LAST ID
     * @param array $arrKVPair
     * @param bool $bAutoCreatedAt
     * @return int
     */
    public function insert($arrKVPair, $bAutoCreatedAt = true)
    {
        if ($bAutoCreatedAt && !array_key_exists('created_at', $arrKVPair)){
            $arrKVPair['created_at'] = new DateTime();
        }

        return $this->M()->insertGetId($arrKVPair);
    }


    /**
     * UPDATE指定ID的数据库记录值
     * @param int $nId
     * @param array $arrKVPair
     * @param bool $bAutoUpdateAt
     * @param string $sColumnName
     * @return int|string
     */
    public function update($nId, $arrKVPair, $bAutoUpdateAt = true, $sColumnName = 'id')
    {
        if ($bAutoUpdateAt && !array_key_exists('updated_at', $arrKVPair)){
            $arrKVPair['updated_at'] = new DateTime();
        }

        return $this->M()
            ->where($sColumnName, $nId)
            ->update($arrKVPair);
    }


    /**
     * DELETE指定ID的数据库记录值
     * @param int $nId
     * @param bool $bSoftDelete 是否软删除（更新deleted_at字段为当前时间）
     * @param bool $bAutoUpdateAtWithSoftDelete 软删除时是否更新updated_at字段，注意使用时需存在该字段
     * @return int|string
     */
    public function delete($nId, $bSoftDelete = true, $bAutoUpdateAtWithSoftDelete = true)
    {
        if ($bSoftDelete){
            return $this->update(
                $nId, [
                    'deleted_at' => new DateTime(),
                ], $bAutoUpdateAtWithSoftDelete
            );
        }

        return $this->M()->delete($nId);
    }
}
