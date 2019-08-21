<?php

namespace App\Http\Controllers;

use App\Business\Dao\Core\CompanyDao;
use App\Business\Dao\QuotaGeneratorDao;
use App\Business\Dao\Core\AssignmentDao;
use App\Business\Dao\LeaveDao;
use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\LeaveRequestDetailDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use DatePeriod;
use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\LookupDao;
use App\Jobs\ResetLeaveQuotaJob;

/**
 * Class for handling Quota Generator process
 */
class QuotaGeneratorController extends Controller
{
    public function __construct(
        Requester $requester,
        QuotaGeneratorDao $quotaGeneratorDao,
        AssignmentDao $assignmentDao,
        LeaveDao $leaveDao,
        LeaveRequestDao $leaveRequestDao,
        LeaveRequestDetailDao $leaveRequestDetailDao,
        PersonDao $personDao,
        CompanyDao $companyDao,
        LookupDao $lookupDao
    )
    {
        $this->requester = $requester;
        $this->quotaGeneratorDao = $quotaGeneratorDao;
        $this->assignmentDao = $assignmentDao;
        $this->leaveDao = $leaveDao;
        $this->leaveRequestDao = $leaveRequestDao;
        $this->leaveRequestDetailDao = $leaveRequestDetailDao;
        $this->personDao = $personDao;
        $this->companyDao = $companyDao;
        $this->lookupDao = $lookupDao;
    }

    public function getQuotaByEmployee(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required'
        ]);

        $quota = $this->quotaGeneratorDao->getQuotaByEmployee($request->employeeId);
        $response = new AppResponse($quota, trans('messages.allDataRetrieved'));

        return $this->renderResponse($response);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required',
            'leaveCode' => 'required',
            'maxQuota' => 'required',
            'effBegin' => 'required',
            'effEnd' => 'required',
            'effBeginOld' => 'required',
            'effEndOld' => 'required'
        ]);

        $leaveQuota = $this->constructQuota($request);
        $this->quotaGeneratorDao->update($request->effBeginOld, $request->effEndOld, $leaveQuota);

        $response = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($response);
    }

    public function getRemainingAndMaxLeaveQuotas(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required',
            'leaveCode' => 'required|exists:leaves,code'
        ]);
        $quotas = null;
        $leaveDesc = $this->leaveDao->getOne($request->leaveCode);

        if ($leaveDesc->isQuotaBased) {
            $quotas = $this->getQuota(
                $request->leaveCode,
                $request->employeeId,
                $request->companyId,
                $leaveDesc->isAnnualLeave
            );
        } else {
            throw new AppException(trans('messages.thisLeaveHasNoQuota'));
        }

        $response = new AppResponse($quotas, trans('messages.allDataRetrieved'));
        return $this->renderResponse($response);
    }

    public function getQuota($leaveCode, $employeeId, $companyId, $isAnnualLeave)
    {
        $quotas = $this->quotaGeneratorDao->getQuotaByEmployeeAndLeaveCode($employeeId, $leaveCode);
        $tempLeaveCode = null;
        if (count($quotas)) {
            foreach ($quotas as $quota) {
                // init remaining Quota with max
                $quota->remainingQuota = $quota->maxQuota;
                if ($isAnnualLeave) {
                    // get all leave which able to decrease annual leave quota
                    // because there is attribute is_annual_leave_deductor in leave data
                    $allLeaveDecreaseAnnualLeave = $this->leaveDao->getAllLeaveByisAnnualAndisDeductor();

                    // init quotas result
                    $quotaResult = $quota;
                    foreach ($allLeaveDecreaseAnnualLeave as $eachLeave) {
                        if ($tempLeaveCode !== $quota->leaveCode) {
                            $arrayLeaveReq = $this->getRequestByEmployeeIdAndLeaveCode(
                                $companyId,
                                $employeeId,
                                $eachLeave->code,
                                $quota->effBegin,
                                $quota->effEnd
                            );
                            // calculate final quota
                            $quotaResult = $this->decreaseQuotaByLeaveRequest(
                                $quotaResult,
                                $arrayLeaveReq
                            );
                        }
                    }
                } else {
                    if ($tempLeaveCode !== $quota->leaveCode) {
                        $leaveRequests = $this->getRequestByEmployeeIdAndLeaveCode(
                            $companyId,
                            $employeeId,
                            $leaveCode,
                            $quota->effBegin,
                            $quota->effEnd
                        );
                        // calculate final quota
                        $quotaResult = $this->decreaseQuotaByLeaveRequest(
                            $quota,
                            $leaveRequests
                        );
                    }
                }
                $tempLeaveCode = $quota->leaveCode;
                $quota = $quotaResult;
            }
        } else {
            // no quota generated in table employee_quotas
            return null;
        }

        return $quotas;
    }

    private function decreaseQuotaByLeaveRequest($quota, $leaveRequests)
    {
        if (count($leaveRequests)) {
            foreach ($leaveRequests as $eachReq) {
                foreach ($eachReq as $eachReqDetail) {
                    $quota->remainingQuota = $quota->remainingQuota - (float)$eachReqDetail->weight;
                }
            }
        }

        return $quota;
    }

    private function getRequestByEmployeeIdAndLeaveCode($companyId, $employeeId, $leaveCode, $effBeginQuota, $effEndQuota)
    {
        $data = array();
        $leaveRequests = $this->leaveRequestDao->getAllByEmployeeIdAndLeaveCode($employeeId, $companyId, $leaveCode);

        if (count($leaveRequests)) {
            foreach ($leaveRequests as $eachReq) {
                $leaveReqDetail = $this->leaveRequestDetailDao->getAllByLeaveRequest($eachReq->id, $effBeginQuota, $effEndQuota);
                array_push($data, $leaveReqDetail);
            }
        }

        return $data;
    }

    public function createQuotaGeneratorForHireEmployee(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'leaveCode' => 'required',
            'employeeId' => 'required'
        ]);

        // var used for special case, quota generate after X working month
        $useWorkingMonth = false;

        // get required employee data for lookup
        $employeeData = $this->personDao->getEmployeeLookupData($request->employeeId);
        $wkmon = $this->personDao->getWorkingMonth($employeeData->ID);
        if (count($wkmon)) {
            $employeeData->WKMON = $wkmon[0]->wkmon;
        }

        $leave = $this->quotaGeneratorDao->getLeaveQuotaBasedIsTrue($request->leaveCode);

        // replace quotaAmount from Lookup if leave using lookup
        if ($leave->quotaType == 'LO') {
            $oneLookUp = $this->lookupDao->getOneByCode($leave->quotaValue);
            $usedLookupAr = $this->getUsedLookup($oneLookUp);

            $matchLookupDetail = $this->getMatchLookup($employeeData, $oneLookUp, $usedLookupAr);
            if (count($matchLookupDetail)) {
                $leave->quotaAmount = $matchLookupDetail->amount;

                // special case, if quota only generated after X working Month
                foreach ($usedLookupAr as $used) {
                    if ($used['value'] == 'WKMON') {
                        $useWorkingMonth = true;
                        // info('$useWorkingMonth', array($useWorkingMonth));
                    }
                }
            }
        }

        // employee join date
        $effBeginAss = $this->assignmentDao->getEffBegin($request->companyId, $request->employeeId);

        $maxAdvanceLeave = $this->companyDao->getOneCompanySettingsByCode('MAL');
        if(!is_numeric($maxAdvanceLeave)) {
            \Log::warning('maxAdvanceLeave not numeric!');
            \Log::warning(print_r($maxAdvanceLeave,true));
        }

        if (count($leave) && count($effBeginAss)) {
            // info('leaveCode : ' . $request->leaveCode);
            if ($leave->cycleType === 'F') {
                // info('cycleType F');
                if ($leave->cyclePeriod === 'FY') {
                    // info('F cyclePeriod FY');
                    $dtF = Carbon::now();
                    $yearF = Carbon::createFromFormat('Y-m-d H:i:s', $dtF)->year;
                    $firstDayF = 'first day of January' . $yearF;

                    // Only for Create New Employee First Date = EffBegin Assignment
                    if($request->has('state')) {
                        if($request->state === 'new') {
                            $firstDayF = $effBeginAss->effBegin;
                        }
                    }

                    $lastDayF = 'last day of December' . $yearF;

                    $firstDayDateF = Carbon::parse($firstDayF);
                    $lastDayFDateF = Carbon::parse($lastDayF);

                    if ($useWorkingMonth) {
                        // check if not generated before (will be the first time generated quta)
                        $previousGenerated = $this->quotaGeneratorDao->getQuotaByEmployeeAndLeaveCode($employeeData->ID, $request->leaveCode);
                        if (count($previousGenerated) == 0) {
                            $firstDayDateF->addMonths($employeeData->WKMON - 1);
                        }
                    }

                    $quota = $leave->quotaAmount;
                    if($request->has('state')) {
                        if($request->state === 'new') {
                            // info('F cyclePeriod FY First New Employee');
                            $quota = $this->DateDifference($effBeginAss->effBegin, $leave->quotaAmount);
                        }
                    }

                    // GENERATE MAIN QUOTA
                    $leaveQuota = [
                        'tenant_id' => $this->requester->getTenantId(),
                        'company_id' => $this->requester->getCompanyId(),
                        'employee_id' => $request->employeeId,
                        'leave_code' => $request->leaveCode,
                        'eff_begin' => $firstDayDateF,
                        'eff_end' => $lastDayFDateF,
                        'max_quota' => $quota,
                        'carried_quota' => 0
                    ];
                    $this->quotaGeneratorDao->saveQuota($leaveQuota);

                    /*
                    *   GENERATE CARRIED QUOTA
                    *   - The last active leave in previouse period will be calculated with
                    *     leave request to get remaining quota.
                    *   - Maximal carried quota and carried forward are depend on master leave
                    *   - Currently, it is only generate if remaingin quota > 0, 
                    *     it is not cover if quota is negative (employee ngutang cuti gitu)
                    */
                    if ($leave->maxCarryForward > 0) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, Carbon::parse($firstDayF));
                        if ($remainingQuota > 0 && $remainingQuota != null) {
                            if ($remainingQuota > $leave->maxCarryForward) {
                                $remainingQuota = $leave->maxCarryForward;
                            }
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $firstDayDateF,
                                'eff_end' => $firstDayDateF->copy()->addDays($leave->carryForward),
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }

                    /*GENERATE ADVANCED LEAVE/HUTANG CUTI
                    * Max quota is depend on company time setting = MAX ADVANCED LEAVE
                    * it is ONLY COVER negative quota (employee ngutang cuti gitu)
                    */
                    if (is_numeric($maxAdvanceLeave) && ($maxAdvanceLeave > 0)) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, Carbon::parse($firstDayF));
                        if ($remainingQuota < 0 && $remainingQuota != null) {
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $firstDayDateF,
                                'eff_end' => $lastDayFDateF,
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }

                } else {
                    // info('F cyclePeriod bukan FY');
                    $currentYear = Carbon::now()->year;
                    $effBeginJoinF = Carbon::parse($effBeginAss->effBegin)->year($currentYear);
                    $effParseF = Carbon::parse($effBeginAss->effBegin)->year($currentYear);
                    $addYearsF = $effParseF->addYears(1);
                    $effEndScheduleF = $addYearsF->subDay();

                    if ($useWorkingMonth) {
                        // check if not generated before (will be the first time generated quta)
                        $previousGenerated = $this->quotaGeneratorDao->getQuotaByEmployeeAndLeaveCode($employeeData->ID, $request->leaveCode);
                        if (count($previousGenerated) == 0) {
                            $effBeginJoinF->addMonths($employeeData->WKMON - 1);
                        }
                    }

                    $leaveQuota = [
                        'tenant_id' => $this->requester->getTenantId(),
                        'company_id' => $this->requester->getCompanyId(),
                        'employee_id' => $request->employeeId,
                        'leave_code' => $request->leaveCode,
                        'eff_begin' => $effBeginJoinF,
                        'eff_end' => $effEndScheduleF,
                        'max_quota' => $leave->quotaAmount,
                        'carried_quota' => 0
                    ];
                    $this->quotaGeneratorDao->saveQuota($leaveQuota);

                    if ($leave->maxCarryForward > 0) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, $effBeginJoinF);
                        if ($remainingQuota > 0 && $remainingQuota != null) {
                            if ($remainingQuota > $leave->maxCarryForward) {
                                $remainingQuota = $leave->maxCarryForward;
                            }
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $effBeginJoinF,
                                'eff_end' => $effBeginJoinF->copy()->addDays($leave->carryForward),
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }

                    if (is_numeric($maxAdvanceLeave) && ($maxAdvanceLeave > 0)) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, $effBeginJoinF);
                        if ($remainingQuota < 0 && $remainingQuota != null) {
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $effBeginJoinF,
                                'eff_end' => $effEndScheduleF,
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }
                }
            } else if ($leave->cycleType === 'R') {
                // info('cycleType R');
                if ($leave->cyclePeriod === 'FY') {
                    // info('R cyclePeriod FY');
                    $dtR = Carbon::now();
                    $yearR = Carbon::createFromFormat('Y-m-d H:i:s', $dtR)->year;
                    $firstDayR = Carbon::parse('first day of January' . $yearR);

                    // Only for Create New Employee First Date = EffBegin Assignment
                    if($request->has('state')) {
                        if($request->state === 'new') {
                            $firstDayR = $effBeginAss->effBegin;
                            $yearR = Carbon::parse($effBeginAss->effBegin)->year;
                        }
                    }

                    $lastDayR = Carbon::parse('last day of December' . $yearR);

                    $wkmonMin = 0;
                    if ($useWorkingMonth) {
                        // check if not generated before (will be the first time generated quta)
                        $previousGenerated = $this->quotaGeneratorDao->getQuotaByEmployeeAndLeaveCode($employeeData->ID, $request->leaveCode);
                        if (count($previousGenerated) == 0) {
                            $firstDayR->addMonths($employeeData->WKMON - 1);
                            $wkmonMin = $employeeData->WKMON % 12;
                        }
                    }

                    for ($i = 12 - $wkmonMin; $i > 0; $i--) {
                        $monthR = $this->getMonthDesc($i - $wkmonMin);
                        $effBeginR = Carbon::parse('first day of ' . $monthR . ' ' . $yearR);
                        $quotaR = $this->quotaGeneratorDao->getQuotaByEmployeeSum($request->employeeId, $leave, $firstDayR, $lastDayR);

                        if($request->has('state')) {
                            if($request->state === 'new') {
                                // info('R cyclePeriod FY First New employee');
                                $quotaR = $this->DateDifference($effBeginAss->effBegin, $leave->quotaAmount);
                            }
                        }

                        if ($quotaR > 0) {
                            $diffR = $leave->quotaAmount - $quotaR;
                            $quotaMaxR = $diffR % $i;
                            if ($quotaMaxR > 0) {
                                $devR = floor($diffR / $i);
                                $sumQuotaR = $devR + 1;
                            } else {
                                $sumQuotaR = $diffR / $i;
                            }
                        } else {
                            $quotaMaxR = $leave->quotaAmount % $i;
                            if ($quotaMaxR > 0) {
                                $devR = floor($leave->quotaAmount / $i);
                                $sumQuotaR = $devR + 1;
                            } else {
                                $sumQuotaR = $leave->quotaAmount / $i;
                            }
                        }

                        $leaveQuota = [
                            'tenant_id' => $this->requester->getTenantId(),
                            'company_id' => $this->requester->getCompanyId(),
                            'employee_id' => $request->employeeId,
                            'leave_code' => $request->leaveCode,
                            'eff_begin' => $effBeginR,
                            'eff_end' => $lastDayR,
                            'max_quota' => $sumQuotaR,
                            'carried_quota' => 0
                        ];
                        $this->quotaGeneratorDao->saveQuota($leaveQuota);
                    }

                    if ($leave->maxCarryForward > 0) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, Carbon::parse($firstDayR));
                        if ($remainingQuota > 0 && $remainingQuota != null) {
                            if ($remainingQuota > $leave->maxCarryForward) {
                                $remainingQuota = $leave->maxCarryForward;
                            }
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $effBeginR,
                                'eff_end' => $effBeginR->copy()->addDays($leave->carryForward),
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }

                    if (is_numeric($maxAdvanceLeave) && ($maxAdvanceLeave > 0)) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, Carbon::parse($firstDayR));
                        if ($remainingQuota < 0 && $remainingQuota != null) {
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $effBeginR,
                                'eff_end' => $lastDayR,
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }
                } else {
                    // info('R cyclePeriod bukan FY');
                    $thisYear = Carbon::now()->year;
                    $effBeginJoinRJD = Carbon::parse($effBeginAss->effBegin)->year($thisYear);
                    $effBeginEndRJD = Carbon::parse($effBeginAss->effBegin)->year($thisYear);
                    $effSubBeginRJD = Carbon::parse($effBeginAss->effBegin)->year($thisYear);
                    $effSubBeginJoinRJD = $effSubBeginRJD->subMonth();

                    $effParseRJD = Carbon::parse($effBeginAss->effBegin)->year($thisYear);
                    $addYearsRJD = $effParseRJD->addYear();
                    $effEndScheduleRJD = $addYearsRJD->subDay();

                    $wkmonMin = 0;
                    if ($useWorkingMonth) {
                        // check if not generated before (will be the first time generated quta)
                        $previousGenerated = $this->quotaGeneratorDao->getQuotaByEmployeeAndLeaveCode($employeeData->ID, $request->leaveCode);
                        if (count($previousGenerated) == 0) {
                            // info('count($previousGenerated)', array(count($previousGenerated)));
                            $effSubBeginJoinRJD->addMonths($employeeData->WKMON - 1);
                            $wkmonMin = $employeeData->WKMON % 12;
                            // info('$wkmonMin', array($wkmonMin));
                        }
                    }

                    for ($i = 12 - $wkmonMin; $i > 0; $i--) {
                        $effBeginRJD = $effSubBeginJoinRJD->addMonth();

                        $intervalEndRJD = $effBeginEndRJD->addMonth();
                        $lastMonthRJD = Carbon::parse($intervalEndRJD);

                        $quotaRJD = $this->quotaGeneratorDao->getQuotaByEmployeeSum($request->employeeId, $request->leaveCode, $effBeginJoinRJD, $effEndScheduleRJD);

                        if ($quotaRJD > 0) {
                            $diffRJD = $leave->quotaAmount - $quotaRJD;
                            $quotaMaxRJD = $diffRJD % $i;
                            if ($quotaMaxRJD > 0) {
                                $devRJD = floor($diffRJD / $i);
                                $sumQuotaRJD = $devRJD + 1;
                            } else {
                                $sumQuotaRJD = $diffRJD / $i;
                            }
                        } else {
                            $quotaMaxRJD = $leave->quotaAmount % $i;
                            if ($quotaMaxRJD > 0) {
                                $devRJD = floor($leave->quotaAmount / $i);
                                $sumQuotaRJD = $devRJD + 1;
                            } else {
                                $sumQuotaRJD = $leave->quotaAmount / $i;
                            }
                        }
                        $leaveQuota = [
                            'tenant_id' => $this->requester->getTenantId(),
                            'company_id' => $this->requester->getCompanyId(),
                            'employee_id' => $request->employeeId,
                            'leave_code' => $request->leaveCode,
                            'eff_begin' => $effBeginRJD,
                            'eff_end' => $effEndScheduleRJD,
                            'max_quota' => $sumQuotaRJD,
                            'carried_quota' => 0
                        ];
                        $this->quotaGeneratorDao->saveQuota($leaveQuota);
                    }

                    if ($leave->maxCarryForward > 0) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, Carbon::parse($effBeginAss->effBegin)->year($thisYear));
                        // info('$remainingQuota', array($remainingQuota));
                        $effBeginCarried = Carbon::parse($effBeginAss->effBegin)->year($thisYear);
                        if ($remainingQuota > 0 && $remainingQuota != null) {
                            if ($remainingQuota > $leave->maxCarryForward) {
                                $remainingQuota = $leave->maxCarryForward;
                            }
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $effBeginCarried,
                                'eff_end' => $effBeginCarried->copy()->addDays($leave->carryForward),
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }

                    if (is_numeric($maxAdvanceLeave) && ($maxAdvanceLeave > 0)) {
                        $remainingQuota = $this->getPreviousQuota($request->companyId, $request->employeeId, $leave, Carbon::parse($effBeginAss->effBegin)->year($thisYear));
                        if ($remainingQuota < 0 && $remainingQuota != null) {
                            $carriedQuota = [
                                'tenant_id' => $this->requester->getTenantId(),
                                'company_id' => $this->requester->getCompanyId(),
                                'employee_id' => $request->employeeId,
                                'leave_code' => $request->leaveCode,
                                'eff_begin' => $effBeginRJD,
                                'eff_end' => $effEndScheduleRJD,
                                'max_quota' => $remainingQuota,
                                'carried_quota' => 0
                            ];
                            $this->quotaGeneratorDao->saveQuota($carriedQuota);
                        }
                    }
                }
            }
        }
        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }

    private
    function getMonthDesc($month)
    {
        switch ($month) {
            case 12:
                return "January";
                break;
            case 11:
                return "February";
                break;
            case 10:
                return "March";
                break;
            case 9:
                return "April";
                break;
            case 8:
                return "May";
                break;
            case 7:
                return "June";
                break;
            case 6:
                return "July";
                break;
            case 5:
                return "August";
                break;
            case 4:
                return "September";
                break;
            case 3:
                return "October";
                break;
            case 2:
                return "November";
                break;
            case 1:
                return "December";
                break;
        }
    }

    private
    function DateDifference($effBeginAss, $quotaAmount) {

        $dtF   = Carbon::now();
        $yearF = Carbon::createFromFormat('Y-m-d H:i:s', $dtF)->year;

        $firstDayF = $effBeginAss;
        $lastDayF  = 'last day of December' . $yearF;

        $firstDayDateF = Carbon::parse($firstDayF);
        $lastDayFDateF = Carbon::parse($lastDayF);

        $datetime1   = date_create($firstDayDateF);
        $datetime2   = date_create($lastDayFDateF);
        $getDiffDate = date_diff($datetime1, $datetime2);
        $getQuota    = null;

        if($getDiffDate) {
            if($getDiffDate->d >= 15) {
                $getQuota = $getDiffDate->m + 1;
            } else {
                $getQuota = $getDiffDate->m;
            }
        }

        $newQuota = round(($getQuota / 12) * $quotaAmount);

        return $newQuota;
    }

    private
    function constructQuota(Request $request)
    {
        $leaveQuota = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'employee_id' => $request->employeeId,
            'leave_code' => $request->leaveCode,
            'eff_begin' => $request->effBegin,
            'eff_end' => $request->effEnd,
            'max_quota' => $request->maxQuota,
        ];
        return $leaveQuota;
    }

//
// ─── HELPER FUNCTION ────────────────────────────────────────────────────────────
//
    private
    function getMatchLookup($employeeData, $oneLookUp, $usedLookupAr)
    {
        foreach ($oneLookUp as $detail) {
            $notMatch = false;
            foreach ($usedLookupAr as $usedLookup) {
                $value = $usedLookup['value'];
                $field = $usedLookup['field'];
                if ($value == 'WKMON') {
                    if ($employeeData->$value < $detail->$field) {
                        $notMatch = true;
                    }
                } else {
                    if ($employeeData->$value !== $detail->$field) {
                        $notMatch = true;
                    }
                }
            }
            if ($notMatch) {
                continue;
            } else {
                return $detail;
            }
        }
    }

    private
    function getUsedLookup($oneLookUp)
    {
        $usedLookup = [];
        $oneLookUpFirst = $oneLookUp[0];

        if ($oneLookUpFirst->lovLook1) {
            $temp = [
                "field" => 'look1Code',
                "value" => $oneLookUpFirst->lovLook1,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook2) {
            $temp = [
                "field" => 'look2Code',
                "value" => $oneLookUpFirst->lovLook2,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook3) {
            $temp = [
                "field" => 'look3Code',
                "value" => $oneLookUpFirst->lovLook3,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook4) {
            $temp = [
                "field" => 'look4Code',
                "value" => $oneLookUpFirst->lovLook4,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook5) {
            $temp = [
                "field" => 'look5Code',
                "value" => $oneLookUpFirst->lovLook5,
            ];
            array_push($usedLookup, $temp);
        }

        return $usedLookup;
    }


    private
    function getPreviousQuota($companyId, $employeeId, $leave, $date)
    {
        $bDate = Carbon::parse($date);
        $bDateLastYear = $bDate->subYear();
        $beginDate = $bDateLastYear->startOfMonth();

        $eDate = Carbon::parse($date);
        $endDate = $eDate->endOfMonth();

        // info('$endDate', array($endDate));
        // info('$beginDate', array($beginDate));

        $lastActiveQuotas = $this->quotaGeneratorDao->getAllQuotaByEmployee($employeeId, $leave->leaveCode, $beginDate, $endDate);

        // info('$lastActiveQuotas', array($lastActiveQuotas));

        $remainingQuota = 0;


        if (count($lastActiveQuotas)) {
            foreach ($lastActiveQuotas as $quota) {

                // init remaining Quota with max
                $quota->remainingQuota = $quota->maxQuota;

                if ($leave->isAnnualLeave) {

                    // get all leave which able to decrease annual leave quota
                    // because there is attribute is_annual_leave_deductor in leave data
                    $allLeaveDecreaseAnnualLeave = $this->leaveDao->getAllLeaveByisAnnualAndisDeductor();

                    // init quotas result
                    $quotaResult = $quota;
                    foreach ($allLeaveDecreaseAnnualLeave as $eachLeave) {
                        $arrayLeaveReq = $this->getRequestByEmployeeIdAndLeaveCode(
                            $companyId,
                            $employeeId,
                            $eachLeave->code,
                            $quota->effBegin,
                            $quota->effEnd
                        );
                        // calculate final quota
                        $quotaResult = $this->decreaseQuotaByLeaveRequest(
                            $quotaResult,
                            $arrayLeaveReq
                        );
                    }
                } else {
                    $leaveRequests = $this->getRequestByEmployeeIdAndLeaveCode(
                        $companyId,
                        $employeeId,
                        $leave->leaveCode,
                        $quota->effBegin,
                        $quota->effEnd
                    );
                    // calculate final quota
                    $quotaResult = $this->decreaseQuotaByLeaveRequest(
                        $quota,
                        $leaveRequests
                    );
                }

                $quota = $quotaResult;
                $remainingQuota = $remainingQuota + $quotaResult->remainingQuota;
            }
        } else {
            // no quota generated in table employee_quotas
            return null;
        }

        return $remainingQuota;
    }

    public
    function forceGenerateQuota()
    {
        // ─── NOTE ────────────────────────────────────────────────────────
        // You can trigger it via postman
        // Acccessing this API will required body :
        // {
        //     "applicationId":1,
        //     "companyId":1000000000
        // }
        // companyId is id which u wanna force to generate the quota
        // ────────────────────────────────────────────────── END NOTE ─────

        dispatch(new ResetLeaveQuotaJob($this->requester->getCompanyId()));
        return $this->renderResponse(new AppResponse($this->requester->getCompanyId(), trans('messages.leaveQuotaWillBeForcedGenerated')));
    }
}
