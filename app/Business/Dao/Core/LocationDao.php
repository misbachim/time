<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocationDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function getOneByCode($locationCode)
    {
        return DB::connection($this->connection)
            ->table('locations')
            ->select(
                'locations.id',
                'locations.name',
                'locations.code'
            )
            ->where([
                ['locations.code', $locationCode],
                ['locations.company_id', $this->requester->getCompanyId()],
                ['locations.tenant_id', $this->requester->getTenantId()]
            ])
            ->first();
    }

}
