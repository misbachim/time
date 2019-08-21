<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RawTimeSheetDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($offset, $limit)
    {
        return
            DB::table('raw_timesheets')
                ->select(
                    'employee_id as employeeId',
                    'date',
                    'type',
                    'clock_time as clockTime',
                    'location_code as locationCode',
                    'is_analyzed as isAnalyzed'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->limit($limit)
                ->offset($offset)
                ->get();
    }

    public function getTotalRows()
    {
        return
            DB::table('raw_timesheets')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->count();
    }

    public function getAllByPerson($employeeId)
    {
        return
            DB::table('raw_timesheets')
                ->select(
                    'date',
                    'type',
                    'clock_time as clockTime',
                    'is_analyzed as isAnalyzed'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId]
                ])
                ->get();
    }

    public function getLatestFiveClockingData($employeeId)
    {
        return
            DB::table('raw_timesheets')
                ->select(
                    'date',
                    'type',
                    'clock_time as clockTime',
                    'location_code as locationCode',
                    'is_analyzed as isAnalyzed'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['clock_time', '!=', null],
                    ['employee_id', $employeeId]
                ])
                ->orderBy('date', 'desc')
                ->orderBy('clock_time', 'desc')
                ->limit(5)
                ->get();
    }

    public function chunkUnprocessedTimeSheets($count, $callback)
    {
        return
            DB::table('raw_timesheets')
                ->selectRaw(
                    "distinct on (raw_timesheets.tenant_id, raw_timesheets.company_id, raw_timesheets.employee_id, raw_timesheets.date) " .
                    "raw_timesheets.employee_id," .
                    "raw_timesheets.date," .
                    "raw_timesheets.clock_time," .
                    "raw_timesheets.type," .
                    "time_attributes.time_group_code," .
                    "time_groups.is_flexy_hour," .
                    "time_groups.is_allow_overtime," .
                    "time_groups.is_flexy_holiday_overtime," .
                    "log_schedule_patterns.leave_code," .
                    "leave_request_details.weight as leave_weight," .
                    "overtime_requests.time_start as overtime_time_start," .
                    "overtime_requests.time_end as overtime_time_end"
                )
                ->join('time_attributes', 'time_attributes.employee_id', 'raw_timesheets.employee_id')
                ->join('time_groups', 'time_groups.code', 'time_attributes.time_group_code')
                ->join('log_schedules', 'log_schedules.time_group_code', 'time_groups.code')
                ->join('log_schedule_patterns', 'log_schedule_patterns.log_schedule_id', 'log_schedules.id')
                ->leftJoin('leave_requests', function ($join) {
                    $join->on('leave_requests.employee_id', 'raw_timesheets.employee_id')
                        ->on('leave_requests.leave_code', 'log_schedule_patterns.leave_code');
                })
                ->leftJoin('leave_request_details', function ($join) {
                    $join->on('leave_request_details.leave_request_id', 'leave_requests.id')
                        ->on('leave_request_details.date', 'raw_timesheets.date')
                        ->where('leave_request_details.status', 'A');
                })
                ->leftJoin('overtime_requests', function ($join) {
                    $join->on('overtime_requests.employee_id', 'raw_timesheets.employee_id')
                        ->on('overtime_requests.schedule_date', 'raw_timesheets.date')
                        ->where('overtime_requests.status', 'A');
                })
                ->where([
                    ['raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['raw_timesheets.company_id', $this->requester->getCompanyId()],
                    ['raw_timesheets.is_analyzed', false]
                ])
                ->orderBy('raw_timesheets.date')
                ->chunk($count, $callback);
    }

    public function getOne($employeeId, $date)
    {
        return
            DB::table('raw_timesheets')
                ->select(
                    'id',
                    'type',
                    'clock_time as clockTime',
                    'is_analyzed as isAnalyzed'
                )->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId],
                    ['date', $date]
                ])->first();
    }

    public function getOneFull($employeeId, $date)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        return
            DB::table('raw_timesheets')
                ->selectRaw(
                    "distinct on (raw_timesheets.tenant_id, raw_timesheets.company_id, raw_timesheets.employee_id, raw_timesheets.date) " .
                    "raw_timesheets.employee_id," .
                    "raw_timesheets.date," .
                    "raw_timesheets.clock_time," .
                    "raw_timesheets.type," .
                    "time_attributes.time_group_code," .
                    "time_groups.is_flexy_hour," .
                    "time_groups.is_allow_overtime," .
                    "time_groups.is_flexy_holiday_overtime," .
                    "overtime_requests.time_start as overtime_time_start," .
                    "overtime_requests.time_end as overtime_time_end"
                )
                ->join('time_attributes', function ($join) use ($companyId, $tenantId, $date) {
                    $join->on('time_attributes.employee_id', 'raw_timesheets.employee_id')
                        ->where([
                            ['time_attributes.eff_begin', '<=', $date],
                            ['time_attributes.eff_end', '>=', $date],
                            ['time_attributes.tenant_id', $tenantId],
                            ['time_attributes.company_id', $companyId]
                        ]);
                })
                ->join('time_groups', function ($join) use ($companyId, $tenantId) {
                    $join->on('time_groups.code', 'time_attributes.time_group_code')
                        ->where([
                            ['time_groups.tenant_id', $tenantId],
                            ['time_groups.company_id', $companyId]
                        ]);
                })
                ->leftJoin('overtime_requests', function ($join) use ($companyId, $tenantId) {
                    $join->on('overtime_requests.employee_id', 'raw_timesheets.employee_id')
                        ->on('overtime_requests.schedule_date', 'raw_timesheets.date')
                        ->where([
                            ['overtime_requests.status', 'A'],
                            ['overtime_requests.tenant_id', $tenantId],
                            ['overtime_requests.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['raw_timesheets.company_id', $this->requester->getCompanyId()],
                    ['raw_timesheets.employee_id', $employeeId],
                    ['raw_timesheets.date', $date]
                ])
                ->first();
    }

    public function getOneFirstClockIn($employeeId, $date, $scheduleTimeIn, $deviation)
    {
        info('IN', []);
        info('empId', [$employeeId]);
        info('date', [$date]);
        info('schTimeIn', [$scheduleTimeIn]);
        info('deviation', [$deviation]);
        $minScheduleTimeIn = Carbon::parse($scheduleTimeIn)->subHours($deviation);
        $maxScheduleTimeIn = Carbon::parse($scheduleTimeIn)->addHours($deviation);
        info('minSchTimeIn', [$minScheduleTimeIn]);
        info('maxSchTimeIn', [$maxScheduleTimeIn]);

        return
            DB::table('raw_timesheets')
                ->selectRaw(
                    "raw_timesheets.employee_id," .
                    "raw_timesheets.date," .
                    "raw_timesheets.clock_time," .
                    "raw_timesheets.type"
                )
                ->where([
                    ['raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['raw_timesheets.company_id', $this->requester->getCompanyId()],
                    ['raw_timesheets.employee_id', $employeeId],
//                    ['raw_timesheets.type', 'I'],
                    ['raw_timesheets.clock_time', '>=', $minScheduleTimeIn],
                    ['raw_timesheets.clock_time', '<=', $maxScheduleTimeIn],
                    ['raw_timesheets.date', $date]
                ])
                ->orderBy('raw_timesheets.clock_time', 'asc')
                ->first();
    }

    public function getOneLastClockOut($employeeId, $date, $scheduleTimeIn, $nextScheduleTimeIn, $deviation)
    {
        info('OUT', []);
        info('empId', [$employeeId]);
        info('date', [$date]);
        info('deviation', [$deviation]);
        info('schTimeIn', [$scheduleTimeIn]);
        info('nextSchTimeIn', [$nextScheduleTimeIn]);

        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $maxScheduleTimeIn = Carbon::parse($scheduleTimeIn)->addHours($deviation);
        if ($nextScheduleTimeIn !== null) {
            $nextMaxScheduleTimeIn = Carbon::parse($nextScheduleTimeIn)->subHours($deviation);
        } else {
            $nextMaxScheduleTimeIn = Carbon::parse($date)->addDays(2);
        }
        info('maxSchTimeIn', [$maxScheduleTimeIn]);
        info('nextMaxSchTimeIn', [$nextMaxScheduleTimeIn]);

        return
            DB::table('raw_timesheets')
                ->selectRaw(
                    "raw_timesheets.employee_id," .
                    "raw_timesheets.date," .
                    "raw_timesheets.clock_time," .
                    "raw_timesheets.type"
                )
                ->where([
                    ['raw_timesheets.tenant_id', $tenantId],
                    ['raw_timesheets.company_id', $companyId],
                    ['raw_timesheets.employee_id', $employeeId],
//                    ['raw_timesheets.type', 'O'],
                    ['raw_timesheets.clock_time', '>=', $maxScheduleTimeIn],
                    ['raw_timesheets.clock_time', '<=', $nextMaxScheduleTimeIn]
//                    ['raw_timesheets.date', $date]
                ])
                ->orderBy('raw_timesheets.clock_time', 'desc')
                ->first();
    }

    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('raw_timesheets')->insert($obj);
    }

    public function update($employeeId, $date, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('raw_timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['employee_id', $employeeId],
                ['date', $date]
            ])
            ->update($obj);
    }

    public function updateById($id, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('raw_timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->update($obj);
    }

    public function updateByEmployeeAndTime($employeeId, $time, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('raw_timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['clock_time', $time],
                ['employee_id', $employeeId]
            ])
            ->update($obj);
    }

    public function delete($id)
    {
        DB::table('raw_timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->delete();
    }

    public function search($startDate, $endDate, $offset, $limit, $order, $orderDirection)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $query = DB::table('raw_timesheets')
            ->select(
                'raw_timesheets.employee_id as employeeId',
                'raw_timesheets.date',
                'raw_timesheets.id',
                'raw_timesheets.type',
                'raw_timesheets.clock_time as clockTime',
                'worksheets.activity_value_1 as activityValue1',
                'worksheets.activity_value_2 as activityValue2',
                'raw_timesheets.is_analyzed as isAnalyzed'
            )
            ->leftJoin('worksheets', function ($join) {
                $join->on('worksheets.id', 'raw_timesheets.worksheet_id')
                    ->on('worksheets.tenant_id', 'raw_timesheets.tenant_id')
                    ->on('worksheets.company_id', 'raw_timesheets.company_id');
            })
            ->where([
                ['raw_timesheets.tenant_id', $tenantId],
                ['raw_timesheets.company_id',$companyId],
                ['raw_timesheets.date', '>=', $startDate],
                ['raw_timesheets.date', '<=', $endDate]
            ])
            ->orderBy('raw_timesheets.date', 'asc');

        if ($order && $orderDirection) {
            $query->orderBy($order, $orderDirection);
        }
//        $query->limit($limit)->offset($offset);
//        $results = $query->get();
        $totalRows = count($query->get());
        $results = $query->limit($limit)->offset($offset)->get();

        return [$results, $totalRows];
    }

    public function advancedSearch($startDate, $endDate, $criteria, $employeeIds, $offset, $limit, $order, $orderDirection)
    {
        $now = Carbon::now();
        $query = DB::table('raw_timesheets')
            ->distinct()
            ->select(
                'raw_timesheets.employee_id as employeeId',
                'raw_timesheets.date',
                'raw_timesheets.id',
                'raw_timesheets.type',
                'raw_timesheets.clock_time as clockTime',
                'worksheets.activity_value_1 as activityValue1',
                'worksheets.activity_value_2 as activityValue2',
                'raw_timesheets.is_analyzed as isAnalyzed'
            )
            ->leftJoin('worksheets', function ($join) {
                $join->on('worksheets.id', 'raw_timesheets.worksheet_id')
                    ->on('worksheets.tenant_id', 'raw_timesheets.tenant_id')
                    ->on('worksheets.company_id', 'raw_timesheets.company_id');
            })
            ->join('time_attributes', function ($join) {
                $join->on('time_attributes.employee_id', 'raw_timesheets.employee_id')
                    ->on('time_attributes.tenant_id', 'raw_timesheets.tenant_id')
                    ->on('time_attributes.company_id', 'raw_timesheets.company_id');
            })
//            ->join('time_attributes', 'time_attributes.employee_id', '=', 'raw_timesheets.employee_id')
            ->where([
                ['raw_timesheets.tenant_id', $this->requester->getTenantId()],
                ['raw_timesheets.company_id', $this->requester->getCompanyId()],
                ['time_attributes.eff_begin', '<=', $now],
                ['time_attributes.eff_end', '>=', $now],
                ['raw_timesheets.date', '>=', $startDate],
                ['raw_timesheets.date', '<=', $endDate]
            ]);

        $criteriaMap = [
            'timeGroup' => 'LOWER(time_attributes.time_group_code) like ?'
        ];
        foreach ($criteria as $criterion) {
            if (array_key_exists($criterion['field'], $criteriaMap)) {
                $searchString = strtolower($criterion['val']);
                $query->whereRaw($criteriaMap[$criterion['field']], [$searchString]);
            }
        }

        if (count($employeeIds) > 0) {
            $query->whereIn('raw_timesheets.employee_id', $employeeIds);
        }

        if ($order && $orderDirection) {
            $query->orderBy($order, $orderDirection);
        }
//        $query->limit($limit)->offset($offset);
//        $results = $query->get();
//        return [$results, $results->count()];
        $totalRows = count($query->get());
        $results = $query->limit($limit)->offset($offset)->get();

        return [$results, $totalRows];
    }
}
