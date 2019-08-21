<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Permission Request related dao
 * @package App\Business\Dao
 */
class LeaveRequestDetailDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all leave request details in ONE leave request
     * @param
     */
    public function getAllByLeaveRequest($leaveReqId, $effBeginQuota=null, $effEndQuota=null)
    {
        $query = DB::table('leave_request_details')
                    ->select(
                        'leave_request_id as leaveRequestId',
                        'date',
                        'status',
                        'weight'
                    )
                    ->where([
                        ['tenant_id', $this->requester->getTenantId()],
                        ['company_id', $this->requester->getCompanyId()],
                        ['leave_request_id',$leaveReqId]
                    ]);

        if ($effBeginQuota && $effEndQuota) {
            $query->where([
                ['date', '>=', $effBeginQuota],
                ['date', '<=', $effEndQuota]
            ]);
        }
        
        return $query->get();
    }
   /**
     * Get all leave requests in ONE company by employee id and leave Code
     * @param
     * - $employeeId: string
     * - $dates: array
     */
    public function getAllByEmployeeIdAndDate($employeeId, $dates)
    {
        $companyId = $this->requester->getCompanyId();
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
                ->join('leave_request_details','leave_request_details.leave_request_id', 'leave_requests.id')
                ->join('leaves', function ($join) use ($companyId, $tenantId) {
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
                ->whereIn('leave_request_details.date', $dates)
                ->whereIn('leave_requests.status', ['A','P'])
                ->distinct()
                ->get();
    }

    public function getOneByPersonLeaveCodeAndDate($employeeId, $leaveCode, $date) {
        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.description',
                    'leave_request_details.weight'
                )
                ->leftJoin('leave_request_details', function ($join) use (&$date) {
                    $join->on('leave_request_details.leave_request_id', 'leave_requests.id');
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $this->requester->getCompanyId()],
                    ['leave_requests.employee_id', $employeeId],
                    ['leave_requests.leave_code', $leaveCode],
                    ['leave_requests.status', 'A'],
                    ['leave_request_details.date', $date]
                ])
                ->first();
    }

    public function getOneByPersonAndDate($employeeId, $date) {
        return
            DB::table('leave_requests')
                ->select(
                    'leave_requests.description',
                    'leave_requests.leave_code as leaveCode',
                    'leave_request_details.weight'
                )
                ->leftJoin('leave_request_details', function ($join) use (&$date) {
                    $join->on('leave_request_details.leave_request_id', 'leave_requests.id');
                })
                ->where([
                    ['leave_requests.tenant_id', $this->requester->getTenantId()],
                    ['leave_requests.company_id', $this->requester->getCompanyId()],
                    ['leave_requests.employee_id', $employeeId],
                    ['leave_requests.status', 'A'],
                    ['leave_request_details.date', $date]
                ])
                ->first();
    }

    /**
     * Get weight leave request details in ONE leave request
     * @param
     */
    public function getWeightLeaveRequest($leaveReqId)
    {
        return
            DB::table('leave_request_details')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['leave_request_id',$leaveReqId]
                ])
                ->sum('weight');
    }

    /**
     * Insert data leave request detail to DB
     * @param  array obj
     */
    public function save($obj)
    {
        return DB::table('leave_request_details')->insert($obj);
    }

    /**
     * Update data leave request to DB
     * @param leaveReqDetId , array obj
     */
    public function update($leaveReqDetId, $obj)
    {
        DB::table('leave_request_details')
            ->where([
                ['id', $leaveReqDetId]
            ])
            ->update($obj);
    }
}
