<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorkSheetDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($offset, $limit)
    {
        return
            DB::table('worksheets')
                ->select(
                    'id',
                    'date',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'description'
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
            DB::table('worksheets')
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->count();
    }

    public function getAllByPerson($employeeId)
    {
        return
            DB::table('worksheets')
                ->select(
                    'id',
                    'date',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'description'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId]
                ])
                ->orderBy('date', 'desc')
                ->get();
    }

    public function getAllByPersonAndDate($employeeId, $date)
    {
        return
            DB::table('worksheets')
                ->select(
                    'id',
                    'date',
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'description'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId],
                    ['date', $date]
                ])
                ->orderBy('date', 'desc')
                ->get();
    }

    public function getOne($employeeId, $date)
    {
        return
            DB::table('worksheets')
                ->select(
                    'time_start as timeStart',
                    'time_end as timeEnd',
                    'description'
                )->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId],
                    ['date', $date]
                ])->first();
    }

    public function getTotalValue($employeeId, $date)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();

        return
            DB::table('worksheets')
                ->selectRaw(
                    "sum(activity_value_1) as value1," .
                    "sum(activity_value_2) as value2"
                )
                ->join('raw_timesheets', function ($join) use ($companyId, $tenantId) {
                    $join->on('raw_timesheets.worksheet_id', 'worksheets.id')
                        ->where([
                            ['raw_timesheets.tenant_id', $tenantId],
                            ['raw_timesheets.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['worksheets.tenant_id', $tenantId],
                    ['worksheets.company_id', $companyId],
                    ['raw_timesheets.employee_id', $employeeId],
                    ['worksheets.date', $date]
                ])
                ->first();
    }


    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('worksheets')->insertGetId($obj);
    }

    public function update($id, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('worksheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->update($obj);
    }

    public function updateByEmployeeAndTime($employeeId, $timeStart, $timeEnd, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('worksheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['time_start', $timeStart],
                ['time_end', $timeEnd],
                ['employee_id', $employeeId]
            ])
            ->update($obj);
    }

    public function delete($employeeId, $date)
    {
        DB::table('worksheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['employee_id', $employeeId],
                ['date', $date]
            ])
            ->delete();
    }
}
