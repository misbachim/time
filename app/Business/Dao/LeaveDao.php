<?php
namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class LeaveDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all leave in ONE company
     * @param
     */
    public function getAll()
    {
        return
            DB::table('leaves')
            ->select(
                'id',
                'code',
                'name',
                'description',
                'is_requestable as isRequestable',
                'is_quota_based as isQuotaBased',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'day_taken_max as maxDayTaken',
                'day_taken_min as minDayTaken'
            )
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()]
            ])
            ->orderBy('code', 'asc')
            ->get();
    }


    /**
     * Get all leave in ONE company By is Annual Leave and is Annual Leave Deductor
     * @param
     */
    public function getAllLeaveByisAnnualAndisDeductor()
    {
        return
            DB::table('leaves')
            ->select(
                'id',
                'code',
                'name',
                'description',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'is_annual_leave',
                'is_annual_leave_deductor'
            )
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['is_quota_based', 1],
                [function ($query) {
                    $query->where('is_annual_leave_deductor', '=', 1)
                        ->orWhere('is_annual_leave', '=', 1);
                }]
            ])
            ->orderBy('code', 'asc')
            ->get();
    }

    /**
     * Get All leave sort by code
     * @param
     */
    public function getAllAnnualLeave()
    {
        return
            DB::table('leaves')
            ->select(
                'id',
                'name',
                'code',
                'type as leaveType',
                'is_requestable as isRequestable',
                'is_quota_based as isQuotaBased',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'quota_expiration as quotaExpiration',
                'carry_expiration_day as carryForward',
                'carry_max as maxCarryForward',
                'quota_type as quotaType',
                'quota_value as quotaValue'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['is_annual_leave', '=', 1]
            ])
            ->orderBy('code', 'asc')
            ->get();
    }

    /**
     * Get all leave is_annual_leave false in ONE company
     * @param
     */
    public function getAllLeaveCustom($criteria)
    {
        $columns = Schema::getColumnListing('leaves');
        $query = 
            DB::table('leaves')
            ->select(
                'id',
                'name',
                'code',
                'type as leaveType',
                'is_requestable as isRequestable',
                'is_quota_based as isQuotaBased',
                'is_annual_leave as isAnnualLeave',
                'is_annual_leave_deductor as isAnnualLeaveDeductor',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'quota_expiration as quotaExpiration',
                'carry_expiration_day as carryForward',
                'carry_max as maxCarryForward'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()]
            ])
            ->orderBy('code', 'asc');

        // used if need filtering
        if ($criteria) {
            foreach ($criteria as $crit) {
                if (in_array($crit['field'], $columns)) {
                    $query->where($crit['field'], $crit['conj'], $crit['val']);
                }
            }
        }

        return
            $query->get();
    }

    /**
     * Get one first leave sort by code
     * @param
     */
    public function getFirst()
    {
        return
            DB::table('leaves')
            ->select(
                'id',
                'name',
                'code',
                'description',
                'day_taken_max as maxDayTaken',
                'day_taken_min as minDayTaken',
                'type as leaveType',
                'is_requestable as isRequestable',
                'is_quota_based as isQuotaBased',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'quota_expiration as quotaExpiration',
                'carry_expiration_day as carryForward',
                'carry_max as maxCarryForward',
                'is_allow_half_day as isAllowHalfDay',
                'is_annual_leave as isAnnualLeave',
                'is_annual_leave_deductor as isAnnualLeaveDeductor',
                'lov_lcty as cycleType',
                'lov_lcpt as cyclePeriod'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()]
            ])
            ->orderBy('code', 'asc')
            ->first();
    }

    /**
     * Get all leaves in ONE company
     */
    public function getLov()
    {
        return
            DB::table('leaves')
            ->select(
                'code',
                'name'
            )
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()]
            ])
            ->get();
    }

    /**
     * Get leave based on id
     * @param
     */
    public function getOne($leaveCode)
    {
        return
            DB::table('leaves')
            ->select(
                'id',
                'name',
                'code',
                'description',
                'day_taken_max as maxDayTaken',
                'day_taken_min as minDayTaken',
                'type as leaveType',
                'is_requestable as isRequestable',
                'is_quota_based as isQuotaBased',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'quota_expiration as quotaExpiration',
                'carry_expiration_day as carryForward',
                'carry_max as maxCarryForward',
                'is_allow_half_day as isAllowHalfDay',
                'is_annual_leave as isAnnualLeave',
                'is_annual_leave_deductor as isAnnualLeaveDeductor',
                'lov_lcty as cycleType',
                'lov_lcpt as cyclePeriod'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['code', '=', $leaveCode]
            ])
            ->first();
    }

    /**
     * Get leave based on id
     * @param
     */
    public function getDefaultAnnualLeave()
    {
        return
            DB::table('leaves')
            ->select(
                'id',
                'name',
                'code',
                'type as leaveType',
                'is_requestable as isRequestable',
                'is_quota_based as isQuotaBased',
                'max_quota as quotaAmount',
                'quota_type as quotaType',
                'quota_value as quotaValue',
                'quota_expiration as quotaExpiration',
                'carry_expiration_day as carryForward',
                'carry_max as maxCarryForward',
                'quota_type as quotaType',
                'quota_value as quotaValue'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['quota_type', '=', 'VA'],
                ['is_annual_leave', '=', 1]
            ])
            ->first();
    }

    /**
     * Insert data leave to DB
     * @param  array obj
     */
    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('leaves')->insertGetId($obj);
    }

    /**
     * Update data Leave to DB
     * @param leaveId , array obj
     */
    public function update($leaveCode, $leaveId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('leaves')
            ->where([
                ['code', $leaveCode],
                ['id', $leaveId]
            ])
            ->update($obj);
    }

    public function delete($leavecode)
    {
        DB::table('leaves')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['code', $leavecode]
            ])
            ->delete();
    }

    public function checkDuplicateLeaveCode(string $leavecode)
    {
        return (DB::table('leaves')->where([
            ['code', $leavecode],
            ['company_id', $this->requester->getCompanyId()],
            ['tenant_id', $this->requester->getTenantId()]
        ])->count() > 0);
    }

    public function checkDuplicateEditLeaveCode(string $leavecode, $id)
    {
        $result = DB::table('leaves')->where([
            ['code', $leavecode],
            ['company_id', $this->requester->getCompanyId()],
            ['tenant_id', $this->requester->getTenantId()]
        ]);

        if (!is_null($id)) {
            $result->where('id', '!=', $id);
        }

        return $result->count();
    }
}
