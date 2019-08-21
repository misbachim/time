<?php
namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TimeAttributeDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all time attribute in ONE company
     * @param
     */
    public function getAll()
    {
        return
            DB::table('time_attributes')
                ->select(
                    'id',
                    'employee_id as employeeId',
                    'time_group_code as timeGroupCOde',
                    'eff_begin as effBegin',
                    'eff_end as effEnd'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    public function getTimeGroupEmployee($employeeId)
    {
        return
            DB::table('time_attributes')
                ->select(
                    'time_attributes.id',
                    'time_groups.name as timeGroupName',
                    'time_attributes.time_group_code as timeGroupCode',
                    'time_attributes.eff_begin as effBegin',
                    'time_attributes.eff_end as effEnd',
                    'time_attributes.employee_id as employeeId'
                )
                ->join('time_groups','time_groups.code','time_attributes.time_group_code')
                ->where([
                    ['time_attributes.tenant_id', $this->requester->getTenantId()],
                    ['time_attributes.company_id', $this->requester->getCompanyId()],
                    ['time_attributes.employee_id',$employeeId],
                    ['time_attributes.eff_begin', '<=', Carbon::now()],
                    ['time_attributes.eff_end', '>=', Carbon::now()]
                ])
                ->orderBy('time_attributes.eff_begin', 'DESC')
                ->first();
    }

    /**
     * Get time attribute based on id
     * @param
     */
    public function getOne($time_attributes)
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
                    ['id', '=', $time_attributes]
                ])
                ->first();
    }

    /**
     * Get time attribute based on employee id
     * @param
     */
    public function getOneEmployeeId($employeeId)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        return
            DB::table('time_attributes')
                ->select(
                    'time_attributes.id',
                    'time_attributes.employee_id as employeeId',
                    'time_attributes.time_group_code as timeGroupCode',
                    'time_groups.name as timeGroupName',
                    'time_groups.is_flexy_hour as isFlexyHour',
                    'time_groups.is_absence_as_annual_leave as isAbsenceAsAnnualLeave',
                    'time_attributes.eff_begin as effBegin',
                    'time_attributes.eff_end as effEnd'
                )
                ->leftJoin('time_groups', function ($join) use ($companyId, $tenantId) {
                    $join->on('time_groups.code', 'time_attributes.time_group_code')
                        ->where([
                            ['time_groups.tenant_id', $tenantId],
                            ['time_groups.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['time_attributes.tenant_id', '=', $this->requester->getTenantId()],
                    ['time_attributes.company_id', '=', $this->requester->getCompanyId()],
                    ['time_attributes.employee_id', '=', $employeeId],
                    ['time_attributes.eff_begin', '<=', Carbon::now()],
                    ['time_attributes.eff_end', '>=', Carbon::now()]
                ])
                ->first();
    }

    public function getHistory($personId) {

        return
            DB::table('time_attributes')
                ->select(
                    'time_attributes.id',
                    'time_attributes.employee_id as employeeId',
                    'time_attributes.time_group_code as timeGroupCode',
                    'time_groups.name as timeGroupName',
                    'time_attributes.eff_begin as effBegin',
                    'time_attributes.eff_end as effEnd'
                )
                ->leftJoin('time_groups', 'time_groups.code', '=', 'time_attributes.time_group_code')
                ->where([
                    ['time_attributes.tenant_id', '=', $this->requester->getTenantId()],
                    ['time_attributes.company_id', '=', $this->requester->getCompanyId()],
                    ['time_attributes.person_id', '=', $personId]
                ])
                ->get();
    }

    /**
     * Insert data time definition to DB
     * @param  array obj
     */
    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('time_attributes')->insertGetId($obj);
    }

    /**
     * Update data time attribute to DB
     * @param timeAttributeId , array obj
     */
    public function update($timeAttributeId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('time_attributes')
            ->where([
                ['id', $timeAttributeId]
            ])
            ->update($obj);
    }

    public function delete($timeAttributeId)
    {
        DB::table('time_attributes')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $timeAttributeId]
            ])
            ->delete();
    }

    public function checkLastDataEmployeeTimeAttributes($personId, string $employeeId, $timeGroupCode, $effBegin, $effEnd) {
       $cek = false;

       $getDataLast = DB::table('time_attributes')
                        ->select(
                            'time_group_code as timeGroupCode',
                            'employee_id as employeeId',
                            'eff_begin as effBegin',
                            'eff_end as effEnd'
                        )
                        ->where([
                            ['time_attributes.tenant_id', $this->requester->getTenantId()],
                            ['time_attributes.company_id', $this->requester->getCompanyId()],
                            ['time_attributes.person_id',$personId],
                            ['time_attributes.eff_begin', '<=', Carbon::now()],
                            ['time_attributes.eff_end', '>=', Carbon::now()]
                        ])
                        ->first();

       if($getDataLast) {
           if ($getDataLast->employeeId === $employeeId &&
               $getDataLast->timeGroupCode === $timeGroupCode &&
               $getDataLast->effBegin === $effBegin &&
               $getDataLast->effEnd === $effEnd) {
               $cek = true;
           }
       }

        return $cek;
    }
}