<?php

namespace App\Business\Dao\UM;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * User related dao
 * @package App\Business\Dao
 */
class UserDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_um';
        $this->requester = $requester;
    }

    /**
     * Get one user
     * @param int $userId
     * @return query result
     */
    public function getOne(int $userId)
    {
        return
            DB::connection($this->connection)
                ->table('users')
                ->select('id',
                    'username',
                    'email',
                    'person_id as personId',
                    'person_name as personName',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'is_sa as isSa'
                )
                ->where([
                    ['id', $userId],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['is_deleted', false]
                ])
                ->first();
    }

    /**
     * Update user
     * @param int $userId
     * @param array $obj
     */
    public function update(int $userId, array $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::connection($this->connection)
            ->table('users')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['id', $userId]
            ])
            ->update($obj);
    }

    /**
     * Get one user
     * @param int $userId
     * @return query result
     */
    public function isSa()
    {
        return
            DB::connection($this->connection)
            ->table('users')
                ->select('is_sa as isSa')
                ->where([
                    ['id', $this->requester->getUserId()],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['is_deleted', false]
                ])
                ->whereRaw('? between eff_begin and eff_end', [Carbon::today()])
                ->first()->isSa;
    }



}
