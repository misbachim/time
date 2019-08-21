<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorkSheetActivityDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll()
    {
        return
            DB::table('worksheet_activities')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'eff_begin as effBegin',
                    'eff_end as effEnd'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    /**
     * Get all worksheet activity in ONE company
     */
    public function getLov()
    {
        return
            DB::table('worksheet_activities')
                ->select(
                    'code',
                    'name',
                    'description'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin', '<=', Carbon::now()],
                    ['eff_end', '>=', Carbon::now()]
                ])
                ->get();
    }

    public function search($query)
    {
        info('search',[$query]);
        $now = Carbon::now();
        $tenantId = $this->requester->getTenantId();
        $companyId = $this->requester->getCompanyId();

        $searchString = strtolower("%$query%");

        $querySQL =
            DB::table('worksheet_activities')
                ->select(
                    'code',
                    'name',
                    'description'
                )
                ->where([
                    ['tenant_id', $tenantId],
                    ['company_id', $companyId],
                    ['eff_begin', '<=', $now],
                    ['eff_end', '>=', $now]
                ])
                ->whereRaw('LOWER(code) like ?', [$searchString])
                ->orWhereRaw('LOWER(name) like ?', [$searchString])
                ->orWhereRaw('LOWER(description) like ?', [$searchString]);

        return $querySQL->get();
    }

    public function getOne($id)
    {
        return
            DB::table('worksheet_activities')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'eff_begin as effBegin',
                    'eff_end as effEnd'
                )->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['id', $id]
                ])->first();
    }

    public function getOneByCode($code)
    {
        return
            DB::table('worksheet_activities')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'eff_begin as effBegin',
                    'eff_end as effEnd'
                )->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['code', $code]
                ])->first();
    }

    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('worksheet_activities')->insertGetId($obj);
    }

    public function update($id, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('worksheet_activities')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->update($obj);
    }

    public function delete($id)
    {
        DB::table('worksheet_activities')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->delete();
    }

    public function isCodeDuplicate(string $code)
    {
        return (DB::table('worksheet_activities')->where([
                ['code', $code],
                ['company_id', $this->requester->getCompanyId()],
                ['tenant_id', $this->requester->getTenantId()]
            ])->count() > 0);
    }
}
