<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    public function getOneByCode($projectCode)
    {
        return DB::connection($this->connection)
            ->table('projects')
            ->select(
                'projects.id',
                'projects.name',
                'projects.code',
                'projects.description'
            )
            ->where([
                ['projects.code', $projectCode],
                ['projects.company_id', $this->requester->getCompanyId()],
                ['projects.tenant_id', $this->requester->getTenantId()]
            ])
            ->first();
    }

    /**
     * Get project based on search
     * @param search
     * @return mixed
     */
    public function searchProject($query)
    {
        $now = Carbon::now();
        $search = strtolower("%$query%");
        return
            DB::connection($this->connection)
                ->table('projects')
                ->select(
                    'projects.name',
                    'projects.code'
                )
                ->where([
                    ['projects.company_id', $this->requester->getCompanyId()],
                    ['projects.tenant_id', $this->requester->getTenantId()],
                    ['projects.eff_begin', '<=', $now],
                    ['projects.eff_end', '>=', $now]
                ])
                ->whereRaw('LOWER(projects.name) like ?', [$search])
                ->orWhereRaw('LOWER(projects.code) like ?', [$search])
                ->orderBy('projects.eff_end', 'desc')
                ->first();
    }

}
