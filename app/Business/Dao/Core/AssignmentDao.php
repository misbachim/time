<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssignmentDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function isPersonActiveInCompany($companyId, $personId)
    {
        $today = Carbon::today();
        return DB::connection($this->connection)
            ->table('assignments')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $companyId],
                ['person_id', $personId],
                ['eff_begin', '<=', $today],
                ['eff_end', '>=', $today]
            ])
            ->count() > 0;
    }

    public function getEffBegin($companyId, $employeeId)
    {
        return
            DB::connection($this->connection)
                ->table('assignments')
                ->select(
                    'assignments.eff_begin as effBegin'
                )
                ->where([
                    ['assignments.tenant_id', $this->requester->getTenantId()],
                    ['assignments.company_id', $companyId],
                    ['assignments.employee_id', $employeeId],
                    ['assignments.lov_acty', 'HIRE'],
                    ['is_primary', true]
                ])
                ->orderBy('eff_begin', 'desc')
                ->first();
    }

    public function doesEmployeeIdBelongToPerson($companyId, $personId, $employeeId)
    {
        return
            DB::connection($this->connection)
            ->table('assignments')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $companyId],
                ['person_id', $personId],
                ['employee_id', $employeeId]
            ])
            ->count() > 0;
    }
}
