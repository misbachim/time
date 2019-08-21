<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ScheduleExceptionDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all schedule exception in ONE company
     * @param
     */
    public function getAll()
    {
        return
            DB::table('schedule_exceptions')
                ->select(
                    'id',
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'date',
                    'time_in as timeIn',
                    'time_out as timeOut',
                    'break_start as breakStart',
                    'break_end as breakEnd',
                    'reason'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    /**
     * Get schedule exception based on id
     * @param
     */
    public function getPersonDayOff($personId, $dateStart, $dateEnd)
    {
        return
            DB::table('schedule_exceptions')
                ->select(
                    'id',
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'date',
                    'time_in as timeIn',
                    'time_out as timeOut',
                    'break_start as breakStart',
                    'break_end as breakEnd',
                    'reason'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['person_id', '=', $personId],
                    ['date', '>=', $dateStart],
                    ['date', '<=', $dateEnd]
                ])
                ->first();
    }

    /**
     * Get schedule exception based on id
     * @param
     */
    public function getOne($schedule_exception_id)
    {
        return
            DB::table('schedule_exceptions')
                ->select(
                    'id',
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'date',
                    'time_in as timeIn',
                    'time_out as timeOut',
                    'break_start as breakStart',
                    'break_end as breakEnd',
                    'reason'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['id', '=', $schedule_exception_id]
                ])
                ->first();
    }

    /**
     * Get schedule exception based on employee id and date
     * @param
     */
    public function getOneByEmployeeAndDate($employeeId, $date)
    {
        return
            DB::table('schedule_exceptions')
                ->select(
                    'id',
                    'employee_id as employeeId',
                    'leave_code as leaveCode',
                    'date',
                    'time_in as timeIn',
                    'time_out as timeOut',
                    'break_start as breakStart',
                    'break_end as breakEnd',
                    'reason'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['employee_id', '=', $employeeId],
                    ['date', '=', $date]
                ])
                ->first();
    }

    public function save($obj)
    {
//        info('objek', $obj);
        foreach ($obj as $row) {
            $row['created_by'] = $this->requester->getUserId();
            $row['created_at'] = Carbon::now();
        }
        return DB::table('schedule_exceptions')->insert($row);
    }

    public function saveRaw($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('schedule_exceptions')->updateOrInsert([
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'employee_id' => $obj['employee_id'],
            'date' => $obj['date']
        ], $obj);
    }

    /**
     * Update data schedule exception to DB
     * @param schedule_exception_id , array obj
     */
    public function update($schedule_exception_id, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('schedule_exceptions')
            ->where([
                ['id', $schedule_exception_id]
            ])
            ->update($obj);
    }

    public function delete($schedule_exception_id)
    {
        DB::table('schedule_exceptions')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $schedule_exception_id]
            ])
            ->delete();
    }
}
