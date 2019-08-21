<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PositionDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function getOne($positionCode)
    {
        return
            DB::connection($this->connection)
                ->table('positions')
                ->select(
                    'positions.code',
                    'positions.name'
                )
                ->where([
                    ['positions.tenant_id', $this->requester->getTenantId()],
                    ['positions.company_id', $this->requester->getCompanyId()],
                    ['positions.code', $positionCode]
                ])
                ->first();
    }

    public function searchPosition($query)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        $search = strtolower("%$query%");

        return
            DB::connection($this->connection)
                ->table('positions')
                ->selectRaw(
                    'assignments.employee_id as "employeeId",' .
                    'positions.name as "positionName"'
                )
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('assignments.position_code', '=', 'positions.code')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['positions.tenant_id', $tenantId],
                    ['positions.company_id', $companyId],
                ])
                ->whereRaw('LOWER(positions.name) like ?', [$search])
                ->get();
    }
}
