<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Permission Request related dao
 * @package App\Business\Dao
 */
class OvertimeRequestDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all overtime requests in ONE company
     * @param
     */
    public function getAll($companyId)
    {
        return
            DB::table('overtime_requests')
                ->select(
                    'id',
                    'description',
                    'employee_id as employeeId',
                    'schedule_date as scheduleDate',
                    'file_reference as fileReference',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'status',
                    'created_at as requestDate'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->orderByRaw('created_at DESC')
                ->get();
    }

    /**
     * Get all overtime requests in ONE company by employee id
     * @param
     */
    public function getAllByEmployeeId($employeeId, $companyId)
    {
        return
            DB::table('overtime_requests')
                ->select(
                    'id',
                    'description',
                    'employee_id as employeeId',
                    'ordered_by as orderedBy',
                    'schedule_date as scheduleDate',
                    'file_reference as fileReference',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'status',
                    'created_at as requestDate'

                )
                ->where([
                    ['employee_id', $employeeId],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->whereColumn([
                    ['employee_id', '=', 'ordered_by']
                ])
                ->orderByRaw('created_at DESC')
                ->get();
    }

    /**
     * Get all overtime requests in ONE company by employee id
     * @param
     */
    public function getAllOrderedForMe($employeeId, $companyId)
    {
        return
            DB::table('overtime_requests')
                ->select(
                    'id',
                    'description',
                    'employee_id as employeeId',
                    'ordered_by as orderedBy',
                    'schedule_date as scheduleDate',
                    'file_reference as fileReference',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'status',
                    'created_at as requestDate'

                )
                ->where([
                    ['employee_id', $employeeId],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->whereColumn([
                    ['employee_id', '!=', 'ordered_by']
                ])
                ->orderByRaw('created_at DESC')
                ->get();
    }

    /**
     * Get all overtime requests in ONE company by employee id
     * @param
     */
    public function getAllOrderedByMe($orderedBy, $companyId)
    {
        return
            DB::table('overtime_requests')
                ->select(
                    'id',
                    'description',
                    'employee_id as employeeId',
                    'ordered_by as orderedBy',
                    'schedule_date as scheduleDate',
                    'file_reference as fileReference',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'status',
                    'created_at as requestDate'
                )
                ->where([
                    ['ordered_by', $orderedBy],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->whereColumn([
                    ['employee_id', '!=', 'ordered_by']
                ])
                ->orderByRaw('created_at DESC')
                ->get();
    }

    public function getAccumulationOvertime($employeeId, $companyId)
    {
        return
            DB::table('overtime_requests')
                ->select(
                    'id',
                    'description',
                    'employee_id as employeeId',
                    'ordered_by as orderedBy',
                    'schedule_date as scheduleDate',
                    'file_reference as fileReference',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'status',
                    'created_at as requestDate'
                )
                ->where([
                    ['employee_id', $employeeId],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId],
                    ['status', 'A']
                ])
                ->whereRaw('schedule_date >= (CURRENT_DATE - INTERVAL \'30 days\')')
                ->orderByRaw('created_at DESC')
                ->get();
    }

    /**
     * Get overtime request based on id
     * @param
     */
    public function getOne($overtimeReqId, $companyId)
    {
        return
            DB::table('overtime_requests')
                ->select(
                    'id',
                    'description',
                    'employee_id as employeeId',
                    'schedule_date as scheduleDate',
                    'file_reference as fileReference',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'status',
                    'created_at as requestDate'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $companyId],
                    ['id', '=', $overtimeReqId]
                ])
                ->first();
    }

    /**
     * Insert data overtime request to DB
     * @param  array obj
     */
    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('overtime_requests')->insertGetId($obj);
    }

    /**
     * Update data overtime request to DB
     * @param id , array obj
     */
    public function update($overtimeReqId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('overtime_requests')
            ->where([
                ['id', $overtimeReqId]
            ])
            ->update($obj);
    }
}