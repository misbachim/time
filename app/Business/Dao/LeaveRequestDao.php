<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Permission Request related dao
 * @package App\Business\Dao
 */
class LeaveRequestDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all leave requests in ONE company
     * @param
     */
    public function getAll($companyId, $offset, $limit)
    {
        $tenantId = $this->requester->getTenantId();

        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.status',
                    'leaves.name as leave'
                )
                ->leftJoin('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId]
                ])
                ->orderByRaw('leave_requests.created_at DESC')
                ->offset($offset)
                ->limit($limit)
                ->get();
    }

    /**
     * search leave requests in ONE company
     * @param
     */
    public function search($companyId, $offset, $limit, $query, $status, $dateStart, $dateEnd)
    {
        $tenantId = $this->requester->getTenantId();
        if ($query) {
            $search = strtolower("%$query%");
        } else {
            $search = "%";
        }

        $querySQL =
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.status',
                    'leaves.name as leave'
                )
                ->distinct('leave_requests.id')
                ->leftJoin('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->join('leave_request_details', function ($join) use ($companyId, $tenantId) {
                    $join->on('leave_request_details.leave_request_id', '=', 'leave_requests.id')
                        ->where([
                            ['leave_request_details.tenant_id', $tenantId],
                            ['leave_request_details.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId]
                ])
                ->whereRaw('LOWER(leaves.name) like ?', [$search])
                ->orderByRaw('leave_requests.created_at DESC')
                ->offset($offset)
                ->limit($limit);

        if ($status) {
            $querySQL->where('leave_requests.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('leave_request_details.date', '>=', $dateStart);
            $querySQL->where('leave_request_details.date', '<=', $dateEnd);
        }

        return $querySQL->get();
    }

    /**
     * search leave requests in ONE company
     * @param
     */
    public function searchEmpIds($companyId, $offset, $limit, $empIds, $status, $dateStart, $dateEnd)
    {
        $tenantId = $this->requester->getTenantId();

        $querySQL =
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.status',
                    'leaves.name as leave'
                )
                ->distinct('leave_requests.id')
                ->leftJoin('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->join('leave_request_details', function ($join) use ($companyId, $tenantId) {
                    $join->on('leave_request_details.leave_request_id', '=', 'leave_requests.id')
                        ->where([
                            ['leave_request_details.tenant_id', $tenantId],
                            ['leave_request_details.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId]
                ])
                ->whereIn('leave_requests.employee_id', $empIds)
                ->orderByRaw('leave_requests.created_at DESC')
                ->offset($offset)
                ->limit($limit);

        if ($status) {
            $querySQL->where('leave_requests.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('leave_request_details.date', '>=', $dateStart);
            $querySQL->where('leave_request_details.date', '<=', $dateEnd);
        }

        return $querySQL->get();
    }

    /**
     * Get all leave requests in ONE company
     * @param
     */
    public function getCount($companyId)
    {
        return
            DB::table('leave_requests')
                ->select(
                    'id'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->get();
    }

    /**
     * Get all leave requests in ONE company
     * @param
     */
    public function getCountSearch($companyId, $search, $status, $dateStart, $dateEnd)
    {
        if ($search) {
            $search = strtolower("%$search%");
        } else {
            $search = "%";
        }

        $tenantId = $this->requester->getTenantId();

        $querySQL =
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id'
                )
                ->distinct('leave_requests.id')
                ->leftJoin('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->join('leave_request_details', function ($join) use ($companyId, $tenantId) {
                    $join->on('leave_request_details.leave_request_id', '=', 'leave_requests.id')
                        ->where([
                            ['leave_request_details.tenant_id', $tenantId],
                            ['leave_request_details.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId]
                ])
                ->whereRaw('LOWER(leaves.name) like ?', [$search]);

        if ($status) {
            $querySQL->where('leave_requests.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('leave_request_details.date', '>=', $dateStart);
            $querySQL->where('leave_request_details.date', '<=', $dateEnd);
        }

        return $querySQL->get();
    }

    /**
     * Get all leave requests in ONE company
     * @param
     */
    public function getCountSearchEmpIds($companyId, $empIds, $status, $dateStart, $dateEnd)
    {
        $tenantId = $this->requester->getTenantId();

        $querySQL =
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id'
                )
                ->distinct('leave_requests.id')
                ->join('leave_request_details', function ($join) use ($companyId, $tenantId) {
                    $join->on('leave_request_details.leave_request_id', '=', 'leave_requests.id')
                        ->where([
                            ['leave_request_details.tenant_id', $tenantId],
                            ['leave_request_details.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId]
                ])
                ->whereIn('leave_requests.employee_id', $empIds);

        if ($status) {
            $querySQL->where('leave_requests.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('leave_request_details.date', '>=', $dateStart);
            $querySQL->where('leave_request_details.date', '<=', $dateEnd);
        }

        return $querySQL->get();
    }

    /**
     * Get all leave requests in ONE company by employee id
     * @param
     */
    public function getAllByEmployeeId($employeeId, $companyId)
    {
        $tenantId = $this->requester->getTenantId();

        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.status',
                    'leave_requests.leave_code as leaveCode',
                    'leaves.name as leave'
                )
                ->leftJoin('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.employee_id', $employeeId],
                    ['leave_requests.tenant_id', $tenantId],
                    ['leave_requests.company_id', $companyId]
                ])
                ->orderByRaw('leave_requests.created_at DESC')
                ->get();
    }

    /**
     * Get all leave requests in ONE company by employee id and leave Code
     * @param
     */
    public function getAllByEmployeeIdAndLeaveCode($employeeId, $companyId, $leaveCode)
    {
        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.status',
                    'leave_requests.leave_code as leaveCode'
                )
                ->where([
                    ['leave_requests.employee_id', $employeeId],
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId],
                    ['leave_requests.leave_code', $leaveCode]
                ])
                ->whereIn('leave_requests.status', ['A', 'P'])
                ->get();
    }

    /**
     * Get leave request based on id
     * @param
     */
    public function getOne($leaveReqId, $companyId)
    {
        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.status',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.updated_at as updatedAt',
                    'leave_requests.leave_code as leaveCode',
                    'leaves.name as leave'
                )
                ->join('leaves', function ($join) {
                    $join
                        ->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->on('leaves.tenant_id', '=', 'leave_requests.tenant_id')
                        ->on('leaves.company_id', '=', 'leave_requests.company_id');
                })
                ->where([
                    ['leave_requests.tenant_id', '=', $this->requester->getTenantId()],
                    ['leave_requests.company_id', '=', $companyId],
                    ['leave_requests.id', '=', $leaveReqId]
                ])
                ->first();
    }

    /**
     * Get many leave requests in ONE company
     * @param
     */
    public function getMany($companyId, $leaveCodes, $employeeId)
    {
        $tenantId = $this->requester->getTenantId();

        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.id',
                    'leave_requests.description',
                    'leave_requests.employee_id as employeeId',
                    'leave_requests.file_reference as fileReference',
                    'leave_requests.created_at as requestDate',
                    'leave_requests.status',
                    'leaves.name as leave'
                )
                ->leftJoin('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'leave_requests.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $companyId],
                    ['leave_requests.employee_id', $employeeId]
                ])
                ->whereIn('leave_code', $leaveCodes)
                ->orderByRaw('leave_requests.created_at DESC')
                ->get();
    }

    /**
     * Insert data permit request to DB
     * @param  array obj
     */
    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('leave_requests')->insertGetId($obj);
    }

    /**
     * Update data leave request to DB
     * @param leaveReqId , array obj
     */
    public function update($leaveReqId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('leave_requests')
            ->where([
                ['id', $leaveReqId]
            ])
            ->update($obj);
    }
}
