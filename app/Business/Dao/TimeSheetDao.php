<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property TimeDefinitionDao timeDefinitionDao
 */
class TimeSheetDao
{
    private $requester;

    public function __construct(Requester $requester, TimeDefinitionDao $timeDefinitionDao)
    {
        $this->requester = $requester;
        $this->timeDefinitionDao = $timeDefinitionDao;
    }

    public function getAll($offset, $limit)
    {
        return
            DB::table('timesheets')
                ->select(
                    'employee_id as employeeId',
                    'date',
                    'is_work_day as isWorkDay',
                    'is_flexy_hour as isFlexyHour',
                    'schedule_time_in as scheduleTimeIn',
                    'time_in as timeIn',
                    'time_in_deviation as timeInDeviation',
                    'schedule_time_out as scheduleTimeOut',
                    'time_out as timeOut',
                    'time_out_deviation as timeOutDeviation',
                    'schedule_duration as scheduleDuration',
                    'duration',
                    'duration_deviation as durationDeviation',
                    'attendance_code as attendanceCode',
                    'leave_code as leaveCode',
                    'leave_weight as leaveWeight',
                    'overtime'
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
            DB::table('timesheets')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->count();
    }

    public function getAllByPerson($employeeId, $limit, $offset)
    {
        return
            DB::table('timesheets')
                ->select(
                    'date',
                    'is_work_day as isWorkDay',
                    'is_flexy_hour as isFlexyHour',
                    'schedule_time_in as scheduleTimeIn',
                    'time_in as timeIn',
                    'time_in_deviation as timeInDeviation',
                    'schedule_time_out as scheduleTimeOut',
                    'time_out as timeOut',
                    'time_out_deviation as timeOutDeviation',
                    'schedule_duration as scheduleDuration',
                    'duration',
                    'duration_deviation as durationDeviation',
                    'attendance_code as attendanceCode',
                    'leave_code as leaveCode',
                    'leave_weight as leaveWeight',
                    'overtime'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId]
                ])
                ->limit($limit)
                ->offset($offset)
                ->get();
    }

    public function getDisplayDataClocking($employeeId, $dateStart, $dateEnd)
    {
        $tenant = $this->requester->getTenantId();
        $company = $this->requester->getCompanyId();
        return
            DB::table('timesheets')
                ->selectRaw(
                    "AVG(duration) as averageWorkingHours," .
                    "AVG(cast(time_in as time)) as averageClockInTime," .
                    "(select count(*) from timesheets 
                        where schedule_time_in < time_in
                        AND tenant_id=" . $tenant . "
                        AND company_id=" . $company . "
                        AND employee_id='" . $employeeId . "'
                        AND date >='" . $dateStart . "'
                        AND date <='" . $dateEnd . "'
                        ) as lateCount," .
                    "(select count(*) from timesheets 
                        where schedule_time_out > time_out
                        AND tenant_id=" . $tenant . "
                        AND company_id=" . $company . "
                        AND employee_id='" . $employeeId . "'
                        AND date >='" . $dateStart . "'
                        AND date <='" . $dateEnd . "'
                    ) as earlyOutCount"
                )
                ->where([
                    ['tenant_id', $tenant],
                    ['company_id', $company],
                    ['date', '>=', $dateStart],
                    ['date', '<=', $dateEnd],
                    ['employee_id', $employeeId]
                ])
                ->first();
    }

    public function getOne($employeeId, $date)
    {
        return
            DB::table('timesheets')
                ->select(
                    'is_work_day as isWorkDay',
                    'is_flexy_hour as isFlexyHour',
                    'schedule_time_in as scheduleTimeIn',
                    'time_in as timeIn',
                    'time_in_deviation as timeInDeviation',
                    'schedule_time_out as scheduleTimeOut',
                    'time_out as timeOut',
                    'time_out_deviation as timeOutDeviation',
                    'schedule_duration as scheduleDuration',
                    'duration',
                    'duration_deviation as durationDeviation',
                    'attendance_code as attendanceCode',
                    'leave_code as leaveCode',
                    'leave_weight as leaveWeight',
                    'overtime'
                )->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId],
                    ['date', $date]
                ])->first();
    }

    public function getOneTimeSheet($month)
    {
        return
            DB::table('timesheets')
                ->select(
                    'is_work_day as isWorkDay',
                    'is_flexy_hour as isFlexyHour',
                    'schedule_time_in as scheduleTimeIn',
                    'time_in as timeIn',
                    'time_in_deviation as timeInDeviation',
                    'schedule_time_out as scheduleTimeOut',
                    'time_out as timeOut',
                    'time_out_deviation as timeOutDeviation',
                    'schedule_duration as scheduleDuration',
                    'duration',
                    'duration_deviation as durationDeviation',
                    'attendance_code as attendanceCode',
                    'leave_code as leaveCode',
                    'leave_weight as leaveWeight',
                    'overtime'
                )->whereRaw("tenant_id = " .$this->requester->getTenantId().
                    " and company_id = " .$this->requester->getCompanyId(). " and extract(MONTH from date) = " .$month)
                ->first();
    }

    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('timesheets')->updateOrInsert([
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'employee_id' => $obj['employee_id'],
            'date' => $obj['date']
        ], $obj);
    }

    public function saveMany($obj)
    {
        return DB::table('timesheets')->insert($obj);
    }

    public function update($employeeId, $date, $obj)
    {
        DB::table('timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['employee_id', $employeeId],
                ['date', $date]
            ])
            ->update($obj);
    }

    public function search($startDate, $endDate, $offset, $limit, $order, $orderDirection)
    {
        $now = Carbon::now();
        $query = DB::table('timesheets')
            ->select(
                'timesheets.employee_id as employeeId',
                'date',
                'is_work_day as isWorkDay',
                'is_flexy_hour as isFlexyHour',
                'schedule_time_in as scheduleTimeIn',
                'time_in as timeIn',
                'time_in_deviation as timeInDeviation',
                'schedule_time_out as scheduleTimeOut',
                'time_out as timeOut',
                'time_out_deviation as timeOutDeviation',
                'schedule_duration as scheduleDuration',
                'duration',
                'duration_deviation as durationDeviation',
                'attendance_code as attendanceCode',
                'leave_code as leaveCode',
                'leave_weight as leaveWeight',
                'overtime'
            )
            ->distinct()
            ->join('time_attributes', 'time_attributes.employee_id', '=', 'timesheets.employee_id')
            ->where([
                ['timesheets.tenant_id', $this->requester->getTenantId()],
                ['timesheets.company_id', $this->requester->getCompanyId()],
                ['time_attributes.eff_begin', '<=', $now],
                ['time_attributes.eff_end', '>=', $now],
                ['date', '>=', $startDate],
                ['date', '<=', $endDate]
            ])
            ->orderBy('date', 'asc');

        if ($order && $orderDirection) {
            $query->orderBy($order, $orderDirection);
        }

        $totalRows = count($query->get());
//        $totalRows = (clone $query)->count();

        $results = $query->limit($limit)->offset($offset)->get();

//        info('totRow', [$totalRows]);
        return [$results, $totalRows];

    }

    public function advancedSearch($criteria, $employeeIds, $offset, $limit, $order, $orderDirection)
    {
        $now = Carbon::now();
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $query = DB::table('timesheets')
            ->select(
                'timesheets.employee_id as employeeId',
                'date',
                'is_work_day as isWorkDay',
                'is_flexy_hour as isFlexyHour',
                'schedule_time_in as scheduleTimeIn',
                'time_in as timeIn',
                'time_in_deviation as timeInDeviation',
                'schedule_time_out as scheduleTimeOut',
                'time_out as timeOut',
                'time_out_deviation as timeOutDeviation',
                'schedule_duration as scheduleDuration',
                'duration',
                'duration_deviation as durationDeviation',
                'attendance_code as attendanceCode',
                'leave_code as leaveCode',
                'leave_weight as leaveWeight',
                'overtime'
            )
            ->distinct()
            ->join('time_attributes', function ($join) use ($companyId, $tenantId) {
                $join->on('time_attributes.employee_id', '=', 'timesheets.employee_id')
                    ->on('time_attributes.tenant_id', '=', 'timesheets.tenant_id')
                    ->on('time_attributes.company_id', '=', 'timesheets.company_id');
            })
            ->where([
                ['timesheets.tenant_id', $this->requester->getTenantId()],
                ['timesheets.company_id', $this->requester->getCompanyId()],
                ['time_attributes.eff_begin', '<=', $now],
                ['time_attributes.eff_end', '>=', $now]
            ])
            ->orderBy('date', 'asc');

        $criteriaMap = [
            'timeGroup' => 'LOWER(time_attributes.time_group_code) like ?',
            'attendance' => 'LOWER(timesheets.attendance_code) like ?',
            'leave' => 'LOWER(timesheets.leave_code) like ?',
        ];
        foreach ($criteria as $criterion) {
            if (array_key_exists($criterion['field'], $criteriaMap)) {
                $searchString = strtolower($criterion['val']);
                $query->whereRaw($criteriaMap[$criterion['field']], ['%' . $searchString . '%']);
            }
        }

        if (count($employeeIds) > 0) {
            $query->whereIn('timesheets.employee_id', $employeeIds);
        }

        if ($order && $orderDirection) {
            $query->orderBy($order, $orderDirection);
        }

        $totalRows = count($query->get());
//        $totalRows = (clone $query)->count();
        info('total rows' . $totalRows);

        $results = $query->limit($limit)->offset($offset)->get();

        return [$results, $totalRows];
    }


    public function getReportTimesheets($startDate,
                                        $endDate,
                                        $criteria,
                                        $employeeIds,
                                        $offset,
                                        $limit,
                                        $order,
                                        $orderDirection)
    {
        $now = Carbon::now();
        $query = DB::table('timesheets')
            ->select(
                'timesheets.employee_id as employeeId',
                'date',
                'is_work_day as isWorkDay',
                'is_flexy_hour as isFlexyHour',
                'schedule_time_in as scheduleTimeIn',
                'time_in as timeIn',
                'time_in_deviation as timeInDeviation',
                'schedule_time_out as scheduleTimeOut',
                'time_out as timeOut',
                'time_out_deviation as timeOutDeviation',
                'schedule_duration as scheduleDuration',
                'duration',
                'duration_deviation as durationDeviation',
                'attendance_code as attendanceCode',
                'leave_code as leaveCode',
                'leave_weight as leaveWeight',
                'overtime'
            )
            ->distinct()
            ->join('time_attributes', 'time_attributes.employee_id', '=', 'timesheets.employee_id')
            ->where([
                ['timesheets.tenant_id', $this->requester->getTenantId()],
                ['timesheets.company_id', $this->requester->getCompanyId()],
            ]);

        if ($startDate != 'null') {
            $query->where([
                ['date', '>=', $startDate],
                ['date', '<=', $endDate],
                ['time_attributes.eff_begin', '<=', $now],
                ['time_attributes.eff_end', '>=', $now]
            ]);
        } else {
            $query->where([
                ['date', '>=', $now],
                ['date', '<=', $now]
            ]);
        }

        $criteriaMap = [
            'timeGroup' => 'LOWER(time_attributes.time_group_code) like ?',
            'attendance' => 'LOWER(timesheets.attendance_code) like ?',
            'leave' => 'LOWER(timesheets.leave_code) like ?',
        ];
        foreach ($criteria as $criterion) {
            if (array_key_exists($criterion['field'], $criteriaMap)) {
                $searchString = strtolower($criterion['val']);
                $query->whereRaw($criteriaMap[$criterion['field']], [$searchString]);
            }
        }

        if (count($employeeIds) > 0) {
            $query->whereIn('timesheets.employee_id', $employeeIds);
        }

        if ($order && $orderDirection) {
            $query->orderBy($order, $orderDirection);
        }

        $totalRows = (clone $query)->count();
        info('total rows' . $totalRows);

        $results = $query->limit($limit)->offset($offset)->get();

        return [$results, $totalRows];
    }
}
