<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorklistDao {
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function updateByLovWftyAndRequestId($lovWfty, $requestId, $obj) {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        return DB::connection($this->connection)
            ->table('worklists')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['lov_wfty', $lovWfty],
                ['request_id', $requestId]
            ])
            ->update($obj);
    }
}