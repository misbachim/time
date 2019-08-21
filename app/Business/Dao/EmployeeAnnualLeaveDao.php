<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EmployeeAnnualLeaveDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * Get all leave in ONE company
     * @param
     */
    public function getListEmployeeLeave($employeeId)
    {
        $tenantId = $this->requester->getTenantId();
        $companyId = $this->requester->getCompanyId();
        $getLeaveEmp = DB::table('leave_requests')
            ->select(
                'leaves.name as leaveName',
                'leave_code as leaveCode',
                'leave_requests.id',
                'leave_requests.description',
                'leave_requests.status'
            )
            ->join('leaves', function ($join) use ($companyId, $tenantId) {
                $join->on('leaves.code', '=', 'leave_requests.leave_code')
                    ->where([
                        ['leaves.tenant_id', $tenantId],
                        ['leaves.company_id', $companyId]
                    ]);
            })
            ->where([
                ['leave_requests.tenant_id', $this->requester->getTenantId()],
                ['leave_requests.company_id', $this->requester->getCompanyId()],
                ['leave_requests.employee_id', $employeeId]
            ])
            ->whereIn('status', ['A'])
            ->orderBy('leave_requests.id', 'asc')
            ->get();

        if (count($getLeaveEmp) > 0) {
            for ($i = 0; $i < count($getLeaveEmp); $i++) {
                $getLeaveEmp[$i]->effBegin = $this->getMinMaxDateFromLeaveRequest($getLeaveEmp[$i]->id)->min;
                $getLeaveEmp[$i]->effEnd = $this->getMinMaxDateFromLeaveRequest($getLeaveEmp[$i]->id)->max;
            }
        }

        return $getLeaveEmp;
    }

    public function getMinMaxDateFromLeaveRequest($leaveRequestId)
    {

        return
            DB::table('leave_request_details')
                ->select(DB::raw('min(date), max(date)'))
                ->where('leave_request_id', '=', $leaveRequestId)
                ->first();
    }

    public function getLeaveEmployee($employeeId)
    {
        $tenantId = $this->requester->getTenantId();
        $companyId = $this->requester->getCompanyId();
        $now = Carbon::now();

        return
            DB::table('employee_quotas')
                ->select('leaves.name', 'employee_quotas.max_quota', 'employee_quotas.carried_quota')
                ->join('leaves', function ($join) use ($companyId, $tenantId) {
                    $join->on('leaves.code', '=', 'employee_quotas.leave_code')
                        ->where([
                            ['leaves.tenant_id', $tenantId],
                            ['leaves.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['employee_quotas.tenant_id', $tenantId],
                    ['employee_quotas.company_id', $companyId],
                    ['employee_quotas.employee_id', $employeeId],
                    ['employee_quotas.eff_begin', '<=', $now],
                    ['employee_quotas.eff_end', '>=', $now]
                ])
                ->get();
    }

    public function getEmployeeQuotas($employeeId)
    {

        return
            DB::table('employee_quotas')
                ->select('max_quota', 'carried_quota')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId],
                    ['eff_begin', '<=', Carbon::now()],
                    ['eff_end', '>=', Carbon::now()]
                ])
                ->first();
    }

    public function getRemainingLeaveQuotas($employeeId)
    {

        $getRemaining = DB::table('leave_requests')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['employee_id', $employeeId],
            ])
            ->whereIn('status', ['A', 'P'])
            ->count();

        $data = $getRemaining ? $this->getEmployeeLeaveQuotas($employeeId) - $getRemaining : $this->getEmployeeLeaveQuotas($employeeId);

        return $data;
    }

//    public function checkDateLeaveEmp($employeeId) {
//
//        $getLeaveEmp = DB::table('leave_requests')
//                        ->select(
//                            'leave_requests.id',
//                            'leave_requests.description',
//                            'leave_requests.status',
//                            'leave_request_details.date'
//                        )
//                        ->join('leave_request_details', 'leave_requests.id', '=', 'leave_request_details.leave_request_id')
//                        ->where([
//                            ['leave_requests.tenant_id', $this->requester->getTenantId()],
//                            ['leave_requests.company_id', $this->requester->getCompanyId()],
//                            ['leave_requests.employee_id', $employeeId]
//                        ])
//                        ->orderBy('leave_requests.id', 'asc')
//                        ->get();
//
//        if(count($getLeaveEmp) > 0) {
//
//            for($i = 0 ; $i < count($getLeaveEmp) ; $i++) {
//
//            }
//
//        }
//    }

//    public function checkBetweenDate($date, $employeeId) {
//
//        $getEmpLeaveQuota = DB::table('employee_quotas')
//                            ->select('eff_begin','eff_end')
//                            ->where([
//                                ['tenant_id', $this->requester->getTenantId()],
//                                ['company_id', $this->requester->getCompanyId()],
//                                ['employee_id', $employeeId]
//                            ])
//                            ->first();

    //->whereBetween($date, ['eff_begin','eff_end'])

    //}

}