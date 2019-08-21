<?php

namespace App\Business\Dao\UM;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tenant related dao
 * @package App\Business\Dao
 */
class TenantDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_um';
        $this->requester = $requester;
    }

    public function getAllActiveTenantId()
    {
        return
            DB::connection($this->connection)
            ->table('tenants')
            ->where([
                ['is_deleted', false]
            ])
            ->whereRaw('? between eff_begin and eff_end', [Carbon::today()])
            ->select('id')
            ->get();
    }
}
