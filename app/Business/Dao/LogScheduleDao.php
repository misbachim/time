<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogScheduleDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($timeGroupCode)
    {
        return
            DB::table('log_schedules')
                ->select(
                    'id',
                    'date_start as dateStart',
                    'date_end as dateEnd',
                    'created_at as createdAt'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode]
                ])
                ->orderBy('id', 'desc')
                ->get();
    }

    public function getOneByTargetDate($timeGroupCode, $targetDate)
    {
        return
            DB::table('log_schedules')
                ->select(
                    'id',
                    'date_start as dateStart',
                    'date_end as dateEnd'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode],
                    ['date_start', '<=', $targetDate],
                    ['date_end', '>=', $targetDate]
                ])
                ->orderBy('id', 'desc')
                ->first();
    }

    public function getTimeGroupByPerson($employee_id)
    {
        return
            DB::table('time_attributes')
                ->select(
                    'id',
                    'employee_id as employeeId',
                    'time_group_code as timeGroupCode',
                    'eff_begin as effBegin',
                    'eff_end as effEnd'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['employee_id', '=', $employee_id]
                ])
                ->orderBy('id', 'desc')
                ->first();
    }

    public function getAllByTargetDates($timeGroupCode, $startDate, $endDate)
    {
        return
            DB::table('log_schedules')
                ->select(
                    'id',
                    'date_start as dateStart',
                    'date_end as dateEnd'
                )
                ->distinct()
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode]
                ])
                ->where(function ($query) use (&$startDate, &$endDate) {
                    $query->where([
                        ['date_start', '<=', $startDate],
                        ['date_end', '>=', $startDate]
                    ])->orWhere([
                        ['date_start', '<=', $endDate],
                        ['date_end', '>=', $endDate]
                    ]);
                })
                ->orderBy('id', 'desc')
                ->get();
    }


    public function getOneForStressTest()
    {
        return
            DB::table('log_schedules')
                ->select(
                    'log_schedules.id',
                    'time_attributes.employee_id as employeeId',
                    'log_schedules.date_start as dateStart',
                    'log_schedules.date_end as dateEnd'
                )
                ->join('time_attributes', 'time_attributes.time_group_code', '=', 'log_schedules.time_group_code')
                ->join('log_schedule_patterns', 'log_schedule_patterns.log_schedule_id', 'log_schedules.id')
                ->where([
                    ['log_schedules.tenant_id', $this->requester->getTenantId()],
                    ['log_schedules.company_id', $this->requester->getCompanyId()],
                ])
                ->orderBy('log_schedules.id', 'desc')
                ->first();
    }

    public function getOneByEmployeeId($employeeId, $targetDate)
    {
        return
            DB::table('log_schedules')
                ->select(
                    'log_schedules.id',
                    'log_schedules.date_start as dateStart',
                    'log_schedules.date_end as dateEnd'
                )
                ->join('time_attributes', 'time_attributes.time_group_code', '=', 'log_schedules.time_group_code')
                ->where([
                    ['log_schedules.tenant_id', $this->requester->getTenantId()],
                    ['log_schedules.company_id', $this->requester->getCompanyId()],
                    ['time_attributes.employee_id', $employeeId],
                    ['log_schedules.date_start', '<=', $targetDate],
                    ['log_schedules.date_end', '>=', $targetDate]
                ])
                ->orderBy('log_schedules.id', 'desc')
                ->first();
    }

    public function getOne($timeGroupCode, $id)
    {
        return
            DB::table('log_schedules')
                ->select(
                    'date_start as dateStart',
                    'date_end as dateEnd',
                    'created_at as createdAt'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode],
                    ['id', $id]
                ])
                ->first();
    }

    public function getOneLast($dateStart,$timeGroupCode)
    {
        return
            DB::table('log_schedules')
                ->select(
                    'id',
                    'date_start as dateStart',
                    'date_end as dateEnd',
                    'time_group_code as timeGroupCode'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode],
                    ['date_start', '<=', $dateStart]
                ])
                ->orderBy('date_start','desc')
                ->first();
    }

    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('log_schedules')->insertGetId($obj);
    }

    public function update($timeGroupCode, $id, $obj)
    {
        DB::table('log_schedules')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['time_group_code', $timeGroupCode],
                ['id', $id]
            ])
            ->update($obj);
    }

    public function delete($timeGroupCode, $id)
    {
        DB::table('log_schedules')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['time_group_code', $timeGroupCode],
                ['id', $id]
            ])
            ->delete();
    }

    public function deleteAll($timeGroupCode)
    {
        DB::table('log_schedules')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['time_group_code', $timeGroupCode]
            ])
            ->delete();
    }
}
