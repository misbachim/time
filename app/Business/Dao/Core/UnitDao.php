<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnitDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function searchUnit($query)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        $search = strtolower("%$query%");

        return
            DB::connection($this->connection)
                ->table('units')
                ->selectRaw(
                    'assignments.employee_id as "employeeId",' .
                    'units.name as "unitName"'
                )
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('assignments.unit_code', '=', 'units.code')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['units.tenant_id', $tenantId],
                    ['units.company_id', $companyId],
                ])
                ->whereRaw('LOWER(units.name) like ?', [$search])
                ->get();
    }
}
