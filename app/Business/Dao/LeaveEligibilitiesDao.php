<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveEligibilitiesDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($leaveCode)
    {
        return
            DB::table('leave_eligibilities')
                ->select(
                    'leave_code as leaveCode',
                    'lov_lvel as lovLvel',
                    'privilege',
                    'value'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['leave_code', $leaveCode]
                ])
                ->get();
    }

    public function save($obj)
    {
        DB::table('leave_eligibilities')-> insert($obj);
    }

    public function update($leaveCode, $date, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('leave_eligibilities')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['leave_code', $leaveCode]
            ])
            ->update($obj);
    }

    public function delete($leaveCode)
    {
        DB::table('leave_eligibilities')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['leave_code', $leaveCode]
            ])
            ->delete();
    }
}
