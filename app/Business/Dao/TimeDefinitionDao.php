<?php
namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TimeDefinitionDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * Get all time definition in ONE company
     * @param
     */
    public function getAll()
    {
        return
            DB::table('time_definitions')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
//                    'day',
                    'time_group_code as timeGroupCode',
                    'lov_tdevty as eventType',
                    'leave_code as leaveCode',
                    'attendance_codes as attendanceStatus',
                    'lov_tddaty as dataType'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    /**
     * Get all time definition in ONE company
     */
    public function getLov()
    {
        return
            DB::table('time_definitions')
                ->selectRaw(
                    'DISTINCT ON (code) id, code, name'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['eff_begin', '<=', Carbon::now()],
                    ['eff_end', '>=', Carbon::now()]
                ])
                ->orderBy('code', 'ASC')
                ->orderBy('eff_begin', 'DESC')
                ->orderBy('id', 'DESC')
                ->get();
    }

    /**
     * Get time definition based on id
     * @param
     */
    public function getOne($time_definition_id)
    {
        return
            DB::table('time_definitions')
                ->select(
                    'id',
                    'code',
                    'name',
//                    'day',
                    'time_group_code as timeGroupCode',
                    'description',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'measurement',
                    'lov_tdevty as eventType',
                    'attendance_codes as attendanceStatus',
                    'lov_tddaty as dataType',
                    'leave_code as leaveCode',
                    'is_workday as isWorkday',
                    'is_flexy as isFlexy',
                    'is_value_1 as isValue1',
                    'is_value_2 as isValue2',
                    'maximum',
                    'minimum'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['id', '=', $time_definition_id]
                ])
                ->first();
    }

    /**
     * Get all attendance in ONE company
     */
    public function getLovAtendance()
    {
        return
            DB::table('attendances')
                ->select(
                    'id',
                    'code',
                    'permit_code as permitCode',
                    'name'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    /**
     * Get time definition based on code
     * @param
     */
    public function getOneByCode($code)
    {
        return
            DB::table('time_definitions')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'eff_begin as effBegin',
                    'eff_end as effEnd',
                    'measurement',
                    'lov_tdevty as eventType',
                    'attendance_codes as attendanceStatus',
                    'lov_tddaty as dataType',
                    'leave_code as leaveCode',
                    'is_workday as isWorkday',
                    'is_flexy as isFlexy',
                    'maximum',
                    'minimum'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['code', '=', $code]
                ])
                ->first();
    }

    /**
     * Insert data time definition to DB
     * @param  array obj
     */
    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('time_definitions')->insertGetId($obj);
    }

    /**
     * Update data time definition to DB
     * @param timeDefinitionId , array obj
     */
    public function update($timeDefinitionId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('time_definitions')
            ->where([
                ['id', $timeDefinitionId]
            ])
            ->update($obj);
    }

    public function delete($timeDefinitionId)
    {
        DB::table('time_definitions')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $timeDefinitionId]
            ])
            ->delete();
    }

//    public function checkDuplicateLeaveCode(string $leavecode)
//    {
//        return DB::table('leaves')->where([
//            ['code', $leavecode],
//            ['company_id', $this->requester->getCompanyId()],
//            ['tenant_id', $this->requester->getTenantId()]
//        ])->count();
//    }
//
//    public function checkDuplicateEditLeaveCode(string $leavecode, $id)
//    {
//        $result = DB::table('leaves')->where([
//            ['code', $leavecode],
//            ['company_id', $this->requester->getCompanyId()],
//            ['tenant_id', $this->requester->getTenantId()]
//        ]);
//
//        if (!is_null($id)) {
//            $result->where('id', '!=', $id);
//        }
//
//        return $result->count();
//    }
}
