<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimeGroupDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll()
    {
        return
            DB::table('time_groups')
                ->select(
                    'code',
                    'name',
                    'description',
                    'eff_begin as effBegin',
                    'is_default as isDefault'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    public function getOne($code)
    {
        return
            DB::table('time_groups')
                ->select(
                    'code',
                    'name',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'description',
                    'is_ignore_holiday as isIgnoreHoliday',
                    'is_flexy_hour as isFlexyHour',
                    'is_allow_overtime as isAllowOvertime',
                    'is_flexy_holiday_overtime as isFlexyHolidayOvertime',
                    'is_default as isDefault',
                    'is_absence_as_annual_leave as isAbsenceAsAnnualLeave',
                    'ovt_rounding as ovtRounding',
                    'late_tolerance as lateTolerance'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['code', $code]
                ])
                ->first();
    }

    public function getOneDefault()
    {
        return
            DB::table('time_groups')
                ->select(
                    'code',
                    'name',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'description',
                    'is_ignore_holiday as isIgnoreHoliday',
                    'is_flexy_hour as isFlexyHour',
                    'is_allow_overtime as isAllowOvertime',
                    'is_flexy_holiday_overtime as isFlexyHolidayOvertime',
                    'is_default as isDefault',
                    'is_absence_as_annual_leave as isAbsenceAsAnnualLeave',
                    'ovt_rounding as ovtRounding',
                    'late_tolerance as lateTolerance'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['is_default', true]
                ])
                ->first();
    }

    /**
     * Get all time group in ONE company
     */
    public function getLov()
    {
        return
            DB::table('time_groups')
                ->select(
                    'code',
                    'name'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin','<=',Carbon::now()],
                    ['eff_end','>=',Carbon::now()]
                ])
                ->get();
    }

    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('time_groups')->insert($obj);
    }

    public function update($code, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('time_groups')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['code', $code]
            ])
            ->update($obj);
    }

    public function delete($code)
    {
        DB::table('time_groups')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['code', $code]
            ])
            ->delete();
    }

    public function setAllNotDefault()
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('time_groups')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()]
            ])
            ->update(['is_default' => false]);
    }
}
