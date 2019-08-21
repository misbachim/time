<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class QuotaGeneratorDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getQuotaByEmployee($employeeId)
    {
        return
            DB::table('employee_quotas')
                ->select(
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'max_quota as maxQuotaGenerator',
                    'carried_quota as carriedQuota'
                )
//                ->leftJoin('leaves', 'leaves.code', '=', 'employee_quotas.leave_code')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin', '<=', Carbon::now()],
                    ['eff_end', '>=', Carbon::now()],
                    ['employee_id', '=', $employeeId]
                ])
                ->get();
    }

    public function getQuotaLeaveByEmployee($employeeId)
    {
        return
            DB::table('employee_quotas')
                ->select(
                    'employee_id as employeeId',
                    'leave_code as leaveCode'
                )
                ->distinct()
//                ->leftJoin('leaves', 'leaves.code', '=', 'employee_quotas.leave_code')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin', '<=', Carbon::now()],
                    ['eff_end', '>=', Carbon::now()],
                    ['employee_id', '=', $employeeId]
                ])
                ->get();
    }

    public function getQuotaByEmployeeAndLeaveCode($employeeId, $leaveCode)
    {
        return
            DB::table('employee_quotas')
                ->select(
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'max_quota as maxQuota'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_end', '>=', Carbon::now()],
                    ['employee_id', '=', $employeeId],
                    ['leave_code', '=', $leaveCode]
                ])
                ->orderBy('eff_end')
                ->get();
    }

    public function getAllQuotaByEmployee($employeeId, $leaveCode, $firstDay, $lastDay)
    {
        return
            DB::table('employee_quotas')
                ->select(
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'max_quota as maxQuota'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin', '>=', $firstDay],
                    ['eff_end', '<=', $lastDay],
                    ['employee_id', '=', $employeeId],
                    ['leave_code', '=', $leaveCode]
                ])
                ->orderBy('eff_end')
                ->get();
    }

    public function getQuotaByEmployeeSum($employeeId, $leaveCode, $firstDay, $lastDay)
    {
        return
            DB::table('employee_quotas')
                ->select(
                    'max_quota as maxQuotaGenerator'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin', '>=', $firstDay],
                    ['eff_end', '<=', $lastDay],
                    ['employee_id', '=', $employeeId],
                    ['leave_code', '=', $leaveCode]
                ])
                ->sum('max_quota');
    }

    public function getLeaveQuotaBasedIsTrue($leavecode)
    {
        return
            DB::table('leaves')
                ->select(
                    'id',
                    'name',
                    'code as leaveCode',
                    'description',
                    'day_taken_max as maxDayTaken',
                    'day_taken_min as minDayTaken',
                    'type as leaveType',
                    'is_requestable as isRequestable',
                    'is_quota_based as isQuotaBased',
                    'max_quota as quotaAmount',
                    'quota_expiration as quotaExpiration',
                    'carry_expiration_day as carryForward',
                    'carry_max as maxCarryForward',
                    'is_allow_half_day as isAllowHalfDay',
                    'is_annual_leave as isAnnualLeave',
                    'is_annual_leave_deductor as isAnnualLeaveDeductor',
                    'lov_lcty as cycleType',
                    'lov_lcpt as cyclePeriod',
                    'quota_type as quotaType',
                    'quota_value as quotaValue'
                )->where([
                    ['code', $leavecode],
                    ['is_quota_based', '1'],
                    ['company_id', $this->requester->getCompanyId()],
                    ['tenant_id', $this->requester->getTenantId()]
                ])
                ->first();
    }

    public function saveQuota($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('employee_quotas')->insert($obj);
    }

    public function update($effBeginOld, $effEndOld, $obj)
    {
        $obj['update_by'] = $this->requester->getUserId();
        $obj['update_at'] = Carbon::now();

        DB::table('employee_quotas')
            ->where([
                ['employee_id', $obj['employee_id']],
                ['leave_code', $obj['leave_code']],
                ['eff_begin', $effBeginOld],
                ['eff_end', $effEndOld]
            ])
            ->update($obj);
    }

    // public function getRemainingAndMaxLeaveQuotas($employeeId, $leaveCode)
    // {
    //     return
    //         DB::table('employee_quotas')
    //             ->selectRaw(
    //                 '(employee_quotas.max_quota + employee_quotas.carried_quota) as "remainingQuota",'.
    //                 'leaves.max_quota as "maxQuota"'
    //             )
    //             ->join('leaves', 'leaves.code', '=', 'employee_quotas.leave_code')
    //             ->where([
    //                 ['employee_quotas.tenant_id', $this->requester->getTenantId()],
    //                 ['employee_quotas.company_id', $this->requester->getCompanyId()],
    //                 ['employee_quotas.eff_begin', '<=', Carbon::now()],
    //                 ['employee_quotas.eff_end', '>=', Carbon::now()],
    //                 ['employee_quotas.employee_id', '=', $employeeId],
    //                 ['employee_quotas.leave_code' , '=', $leaveCode],
    //                 ['leaves.is_quota_based', '=', true]
    //             ])
    //             ->orderBy('employee_quotas.eff_begin', 'DESC')
    //             ->first();
    // }
}
