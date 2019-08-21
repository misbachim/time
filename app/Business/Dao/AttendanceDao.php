<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AttendanceDao
{
    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }


    /**
     * Get all attendance in ONE company
     * @param
     */
    public function getAll()
    {
        return
            DB::table('attendances')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'permit_code as permitCode'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }


    /**
     * Get all attendance in ONE company
     */
    public function getLov()
    {
        return
            DB::table('attendances')
                ->select(
                    'code',
                    'name'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->get();
    }

    /**
     * Get attendance based on code
     * @param
     */
    public function getOne($attendanceCode)
    {
        $code =  str_replace("(","",$attendanceCode);
        $code =  str_replace(")","",$code);

        return
            DB::table('attendances')
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'permit_code as permitCode'
                )
                ->where([
                    ['tenant_id', '=', $this->requester->getTenantId()],
                    ['company_id', '=', $this->requester->getCompanyId()],
                    ['code', '=', $code]
                ])
                ->first();
    }

}
