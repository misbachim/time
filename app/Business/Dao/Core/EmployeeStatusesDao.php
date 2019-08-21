<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeStatusesDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function getOne($employeeStatusCode)
    {
        return
            DB::connection($this->connection)
            ->table('employee_statuses')
            ->select(
                'id',
                'code',
                'name'
            )
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['code', $employeeStatusCode]
            ])
            ->first();
    }


}
