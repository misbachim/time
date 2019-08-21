<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;

class CompanyDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function getOneCompanySettingsByCode($code)
    {
        return DB::connection($this->connection)
            ->table('setting_types')
            ->select(
                'company_settings.fix_value as fixValue'
            )
            ->join('company_settings', 'setting_type_code', 'setting_types.code')
            ->where([
                ['company_settings.tenant_id', $this->requester->getTenantId()],
                ['company_settings.company_id', $this->requester->getCompanyId()],
                ['setting_types.code', $code]
            ])
            ->first();
    }

    public function getAll($activeTenant)
    {
        return DB::connection($this->connection)
            ->table('companies')
            ->select(
                'id'
            )
            ->whereIn('tenant_id', $activeTenant)
            ->get();
    }
}
