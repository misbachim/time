<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogSchedulePatternDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($logScheduleId)
    {
        return
            DB::table('log_schedule_patterns')
                ->select(
                    'sequence',
                    'leave_code as leaveCode',
                    'work_start as workStart',
                    'work_duration as workDuration',
                    'break_start as breakStart',
                    'break_duration as breakDuration'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['log_schedule_id', $logScheduleId]
                ])
                ->orderBy('sequence', 'asc')
                ->get();
    }

    public function getOne($logScheduleId, $sequence)
    {
        return
            DB::table('log_schedule_patterns')
                ->select(
                    'leave_code as leaveCode',
                    'work_start as workStart',
                    'work_duration as workDuration',
                    'break_start as breakStart',
                    'break_duration as breakDuration'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['log_schedule_id', $logScheduleId],
                    ['sequence', $sequence]
                ])
                ->first();
    }

    public function save($obj)
    {
        return DB::table('log_schedule_patterns')->insert($obj);
    }

    public function update($logScheduleId, $sequence, $obj)
    {
        DB::table('log_schedule_patterns')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['log_schedule_id', $logScheduleId],
                ['sequence', $sequence]
            ])
            ->update($obj);
    }

    public function delete($logScheduleId)
    {
        DB::table('log_schedule_patterns')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['log_schedule_id', $logScheduleId]
            ])
            ->delete();
    }
}