<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @property string connection
 * @property Requester requester
 */
class LookupDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
    }

    /**
     * Get all Lookup in ONE company
     * @param  typeCode
     */
    public function getAll()
    {
        return
            DB::connection($this->connection)
                ->table('lookups')
                ->select(
                    'id',
                    'description',
                    'name',
                    'code',
                    'eff_end as effEnd',
                    'eff_begin as effBegin',
                    'lov_look_1',
                    'lov_look_2',
                    'lov_look_3',
                    'lov_look_4',
                    'lov_look_5'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    /**
     * Get Lookup based on code
     * @param
     */
    public function getOneByCode($code)
    {
        return
            DB::connection($this->connection)
                ->table('lookups')
                ->select(
                    'lookups.id',
                    'lookups.description',
                    'lookups.code',
                    'lookups.name',
                    'lookups.eff_end as effEnd',
                    'lookups.eff_begin as effBegin',
                    'lov_look_1 as lovLook1',
                    'lov_look_2 as lovLook2',
                    'lov_look_3 as lovLook3',
                    'lov_look_4 as lovLook4',
                    'lov_look_5 as lovLook5',
                    'lookup_details.look_1_code as look1Code',
                    'lookup_details.look_2_code as look2Code',
                    'lookup_details.look_3_code as look3Code',
                    'lookup_details.look_4_code as look4Code',
                    'lookup_details.look_5_code as look5Code',
                    'lookup_details.amount'
                )
                ->leftJoin('lookup_details', function ($join) {
                    $join->on('lookup_details.lookup_id', '=', 'lookups.id')
                        ->where([
                            ['lookup_details.tenant_id', $this->requester->getTenantId()],
                            ['lookup_details.company_id', $this->requester->getCompanyId()],
                        ]);
                })
                ->where([
                    ['lookups.tenant_id', $this->requester->getTenantId()],
                    ['lookups.company_id', $this->requester->getCompanyId()],
                    ['lookups.code', $code]
                ])
                ->orderBy('id', 'desc')
                ->get();

    }

    /**
     * Get Lookup based on code
     * @param
     */
    public function getAllByCode($code)
    {
        return
            DB::connection($this->connection)
                ->table('lookups')
                ->select(
                    'lookups.id',
                    'lookups.description',
                    'lookups.code',
                    'lookups.name',
                    'lookups.eff_end as effEnd',
                    'lookups.eff_begin as effBegin',
                    'lov_look_1 as lovLook1',
                    'lov_look_2 as lovLook2',
                    'lov_look_3 as lovLook3',
                    'lov_look_4 as lovLook4',
                    'lov_look_5 as lovLook5',
                    'lookup_details.look_1_code as look1',
                    'lookup_details.look_2_code as look2',
                    'lookup_details.look_3_code as look3',
                    'lookup_details.look_4_code as look4',
                    'lookup_details.look_5_code as look5',
                    'lookup_details.amount'
                )
                ->leftJoin('lookup_details', function ($join) {
                    $join->on('lookup_details.lookup_id', '=', 'lookups.id')
                        ->where([
                            ['lookup_details.tenant_id', $this->requester->getTenantId()],
                            ['lookup_details.company_id', $this->requester->getCompanyId()],
                        ]);
                })
                ->where([
                    ['lookups.tenant_id', $this->requester->getTenantId()],
                    ['lookups.company_id', $this->requester->getCompanyId()],
                    ['lookups.code', $code]
                ])
                ->orderBy('id', 'desc')
                ->get();

    }
}
