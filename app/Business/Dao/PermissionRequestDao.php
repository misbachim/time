<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Permission Request related dao
 * @package App\Business\Dao
 */
class PermissionRequestDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all permit requests in ONE company
     * @param
     */
    public function getAll($companyId)
    {
        return
            DB::table('permit_requests')
                ->select(
                    'id',
                    'date as permissionDate',
                    'file_reference as fileReference',
                    'employee_id as employeeId',
                    'permit_code as permitCode',
                    'created_at as requestDate',
                    'reason',
                    'status'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->orderByRaw('created_at DESC')
                ->get();
    }

    /**
     * @param
     * @return
     */
    public function getTotalRow()
    {
        return DB::table('permit_requests')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()]
            ])
            ->count();
    }

    /**
     * Get all permission requests in ONE company by employee id
     * @param
     */
    public function getAllByEmployeeId($employeeId,$companyId)
    {
        return
            DB::table('permit_requests')
                ->select(
                    'id',
                    'date as permissionDate',
                    'file_reference as fileReference',
                    'employee_id as employeeId',
                    'permit_code as permitCode',
                    'created_at as requestDate',
                    'reason',
                    'status'
                )
                ->where([
                    ['employee_id', $employeeId],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->orderByRaw('created_at DESC')
                ->get();
    }

    /**
     * Get permission request based on id
     * @param
     */
    public function getOne($permitId,$companyId)
    {
        return
            DB::table('permit_requests')
                ->select(
                    'permit_requests.id',
                    'permit_requests.date as permissionDate',
                    'permit_requests.employee_id as employeeId',
                    'permit_requests.permit_code as permitCode',
                    'permit_requests.created_at as requestDate',
                    'permit_requests.file_reference as fileReference',
                    'permit_requests.reason',
                    'permit_requests.status'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $companyId],
                    ['id', '=', $permitId]
                ])
                ->first();
    }

    public function isPermitted($permitCode, $date, $employeeId)
    {
        return
            DB::table('permit_requests')
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['permit_code', '=', $permitCode],
                    ['date', '=', $date],
                    ['status', '=', 'A'],
                    ['employee_id', '=', $employeeId],
                ])
                ->count() > 0;
    }

    /**
     * Insert data permit request to DB
     * @param  array obj
     */
    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('permit_requests')->insertGetId($obj);
    }

    /**
     * Update data Permit request to DB
     * @param permitId , array obj
     */
    public function update($permitId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('permit_requests')
            ->where([
                ['id', $permitId]
            ])
            ->update($obj);
    }
}
