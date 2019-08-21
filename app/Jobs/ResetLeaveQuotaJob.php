<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business\Dao\QuotaGeneratorDao;
use App\Business\Dao\Core\AssignmentDao;
use App\Business\Model\Requester;
use App\Http\Controllers\QuotaGeneratorController;
use App\Business\Dao\LeaveDao;
use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\LeaveRequestDetailDao;
use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\LookupDao;
use App\Business\Dao\Core\CompanyDao;


class ResetLeaveQuotaJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        info('ResetLeaveQuotaJob: I am ready to reset leave quotas.');
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('ResetLeaveQuotaJob Start');

        $now = Carbon::now();
        $allEmployee =
            DB::connection('pgsql_core')
            ->table('assignments')
            ->select('assignments.employee_id as employeeId'
                    ,'assignments.company_id as companyId'
                    ,'assignments.tenant_id as tenantId'
            )
            ->join('persons','persons.id','=','assignments.person_id')
            ->join('companies','companies.id','=','assignments.company_id')
            ->join('v_active_tenant_ids','v_active_tenant_ids.id','=','companies.tenant_id')
            ->where([
                ['assignments.eff_begin', '<=', $now],
                ['assignments.eff_end', '>=', $now],
                ['assignments.lov_acty', '!=', 'TERM'],
            ])->distinct()->get();

        foreach ($allEmployee as $emp) {
            $query = DB::table('employee_quotas')
                ->select(
                    'tenant_id as tenantId',
                    'company_id as companyId',
                    'employee_id as employeeId',
                    'leave_code as leaveCode'
                )->where([
                    ['employee_id', '=', $emp->employeeId],
                    ['company_id', '=', $emp->companyId],
                    ['tenant_id', '=', $emp->tenantId],
                    ['eff_end', '>=', $now],
                ])->distinct();

            $activeLeave = $query->get();
            $this->resetLeaveQuota($activeLeave, $emp);
        }

        info('ResetLeaveQuotaJob: Leave quotas successfully reset.');
    }


    /**
     * Handle failures.
     *
     * @return void
     */
    public function failed(\Exception $ex)
    {
        info($ex);
    }


    private function resetLeaveQuota($activeLeaveQuotas, $emp)
    {
        $leaveNeedGenerate = [];

        $requester = $this->setRequester($emp);
        $quotaGeneratorCtrl = $this->constructQuotaGeneratorCtrl($requester);

        $eligibleLeave = app()->call('App\Http\Controllers\LeaveController@getLeaveByEligibilitiesPrivate', [$emp->employeeId]);

        foreach ($eligibleLeave as $leave) {
            if (!$this->existOn($activeLeaveQuotas, $leave)) {
                array_push($leaveNeedGenerate, $leave);
            }
        }

        if (count($leaveNeedGenerate)) {
            info(print_r($emp, true));
            info('leaveNeedGenerate:', array($leaveNeedGenerate));
            info('---------------------');
        }

        foreach ($leaveNeedGenerate as $leave) {
            $request = $this->constructRequest($emp, $leave->code);
            $quotaGeneratorCtrl->createQuotaGeneratorForHireEmployee($request);
        }
    }

    private function setRequester($employee)
    {
        $requester = app(Requester::class);
        $requester->setTenantId($employee->tenantId);
        $requester->setCompanyId($employee->companyId);
        $requester->setUserId(0);

        return $requester;
    }

    private function constructQuotaGeneratorCtrl($requester)
    {
        $quotaGeneratorDao = new QuotaGeneratorDao($requester);
        $assignmentDao = new AssignmentDao($requester);
        $leaveDao = new LeaveDao($requester);
        $leaveRequestDao = new LeaveRequestDao($requester);
        $leaveRequestDetailDao = new LeaveRequestDetailDao($requester);
        $personDao = new PersonDao($requester);
        $lookupDao = new LookupDao($requester);
        $companyDao = new CompanyDao($requester);

        return new QuotaGeneratorController(
            $requester,
            $quotaGeneratorDao,
            $assignmentDao,
            $leaveDao,
            $leaveRequestDao,
            $leaveRequestDetailDao,
            $personDao,
            $companyDao,
            $lookupDao
        );
    }

    private function constructRequest($emp, $leaveCode)
    {
        $request = new \Illuminate\Http\Request();
        $request->setMethod('POST');
        $request->request->add([
            'companyId' => $emp->companyId,
            'employeeId' => $emp->employeeId,
            'leaveCode' => $leaveCode
        ]);

        return $request;
    }

    private function existOn($array, $data)
    {
        foreach ($array as $eachAr) {
            if ($eachAr->leaveCode === $data->code) {
                return true;
            }
        }
        return false;
    }
}
