<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimeGroupScheduleDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($timeGroupCode)
    {
        return
            DB::table('time_group_schedules')
                ->select(
                    'date',
                    'leave_code as leaveCode',
                    'time_in as timeIn',
                    'time_out as timeOut',
                    'break_start as breakStart',
                    'break_end as breakEnd'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode]
                ])
                ->get();
    }

    public function getOne($timeGroupCode, $date)
    {
        return
            DB::table('time_group_schedules')
                ->select(
                    'date',
                    'leave_code as leaveCode',
                    'time_in as timeIn',
                    'time_out as timeOut',
                    'break_start as breakStart',
                    'break_end as breakEnd'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['time_group_code', $timeGroupCode],
                    ['date', $date]
                ])
                ->first();
    }

    public function save($obj)
    {
        if (is_array($obj)) { // TODO: WRONG!
            foreach ($obj as $row) {
                $row['created_by'] = $this->requester->getUserId();
                $row['created_at'] = Carbon::now();
            }
        } else {
            $obj['created_by'] = $this->requester->getUserId();
            $obj['created_at'] = Carbon::now();
        }

        return DB::table('time_group_schedules')->insert($obj);
    }

    public function update($timeGroupCode, $date, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('time_group_schedules')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['time_group_code', $timeGroupCode],
                ['date', $date]
            ])
            ->update($obj);
    }

    public function delete($timeGroupCode, $dateStart, $dateEnd)
    {
        DB::table('time_group_schedules')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['time_group_code', $timeGroupCode],
                ['date', '>=', $dateStart],
                ['date', '<=', $dateEnd]
            ])
            ->delete();
    }
}