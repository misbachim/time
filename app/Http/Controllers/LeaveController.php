<?php

namespace App\Http\Controllers;

use App\Business\Dao\LeaveDao;
use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\QuotaGeneratorDao;
use App\Business\Dao\Core\EmployeeStatusesDao;
use App\Business\Dao\Core\LocationDao;
use App\Business\Dao\Core\PositionDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\QuotaGeneratorController;
use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\LookupDao;

/**
 * Class for handling Leave process
 */
class LeaveController extends Controller
{
    public function __construct(
        Requester $requester,
        LeaveDao $leaveDao,
        QuotaGeneratorController $quotaGeneratorController,
        QuotaGeneratorDao $quotaGeneratorDao,
        LeaveRequestDao $leaveRequestDao,
        LocationDao $locationDao,
        EmployeeStatusesDao $employeeStatusesDao,
        PositionDao $positionDao,
        personDao $personDao,
        lookupDao $lookupDao
    ) {
        parent::__construct();

        $this->requester = $requester;
        $this->leaveDao = $leaveDao;
        $this->leaveRequestDao = $leaveRequestDao;
        $this->quotaGeneratorController = $quotaGeneratorController;
        $this->quotaGeneratorDao = $quotaGeneratorDao;
        $this->locationDao = $locationDao;
        $this->employeeStatusesDao = $employeeStatusesDao;
        $this->positionDao = $positionDao;
        $this->personDao = $personDao;
        $this->lookupDao = $lookupDao;
    }

    /**
     * Get all Leave
     * @param request
     */
    public function getAll(Request $request)
    {
        //        Log::info($request);
        $this->validate($request, ["companyId" => "required|integer"]);
        $leave = $this->leaveDao->getAll();
        $response = new AppResponse($leave, trans('messages.allDataRetrieved'));

        return $this->renderResponse($response);
    }

    public function getAllWithoutFirst(Request $request)
    {
        $this->validate($request, ["companyId" => "required|integer"]);
        $leave = $this->leaveDao->getAll();
        $leave = $leave->splice(1);

        $response = new AppResponse($leave, trans('messages.allDataRetrieved'));
        return $this->renderResponse($response);
    }

    public function getFirst(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required'
        ]);

        $leave = $this->leaveDao->getFirst();

        return $this->renderResponse(new AppResponse($leave, trans('messages.dataRetrieved')));
    }

    public function getLeaveByEligibilities(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required'
        ]);

        // get required employee data for lookup
        $employeeData = $this->personDao->getEmployeeLookupData($request->employeeId);
        $wkmon = $this->personDao->getWorkingMonth($employeeData->ID);
        if (count($wkmon)) {
            $employeeData->WKMON = $wkmon[0]->wkmon;
        }

        $data = new class
        { };
        $data->annualLeave = $this->getAnnualLeave($employeeData);
        $data->notAnnualLeave = $this->getNotAnnualLeave($employeeData);
        $data->notQuotaBased = $this->getNotQuotaBased($employeeData);

        return $this->renderResponse(new AppResponse($data, trans('messages.dataRetrieved')));
    }

    public function getLeaveByEligibilitiesWithQuotas(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required'
        ]);

        // get required employee data for lookup
        $employeeData = $this->personDao->getEmployeeLookupData($request->employeeId);
        $wkmon = $this->personDao->getWorkingMonth($employeeData->ID);
        if (count($wkmon)) {
            $employeeData->WKMON = $wkmon[0]->wkmon;
        }

        $tempAnnualLeave    = array();
        $tempNotAnnualLeave = array();

        $dataEmployeeQuota  = $this->quotaGeneratorDao->getQuotaLeaveByEmployee($request->employeeId);
        // Log::info(print_r($dataEmployeeQuota, true));
        if(count($dataEmployeeQuota) > 0) {
            foreach ($dataEmployeeQuota AS $datum)
            {
                $getLeave = $this->leaveDao->getOne($datum->leaveCode);
//                info('$getLeave', [$getLeave]);
                $getLeave->isAnnualLeave ? array_push($tempAnnualLeave, $getLeave) :
                    array_push($tempNotAnnualLeave, $getLeave);
            }
        }

        $data = new class
        { };
        $data->annualLeave    = $tempAnnualLeave;
        $data->notAnnualLeave = $tempNotAnnualLeave;
        $data->notQuotaBased  = $this->getNotQuotaBased($employeeData);

        // get quota for annual leave
        if (count($data->annualLeave)) {
            foreach ($data->annualLeave as $anl) {
                $anl->quotas = $this->quotaGeneratorController->getQuota(
                    $anl->code,
                    $request->employeeId,
                    $request->companyId,
                    $anl->isAnnualLeave
                );
            }
        }

        // get quota for not annual leave
        if (count($data->notAnnualLeave)) {
            foreach ($data->notAnnualLeave as $notAnl) {
                $notAnl->quotas = $this->quotaGeneratorController->getQuota(
                    $notAnl->code,
                    $request->employeeId,
                    $request->companyId,
                    $notAnl->isAnnualLeave
                );
            }
        }

        // get used leave for not quota based
        if (count($data->notQuotaBased)) {
            foreach ($data->notQuotaBased as $notQuota) {
                $leaves = $this->leaveRequestDao->getAllByEmployeeIdAndLeaveCode(
                    $request->employeeId,
                    $request->companyId,
                    $notQuota->code
                );
                $notQuota->leaves = count($leaves);
            }
        }

        return $this->renderResponse(new AppResponse($data, trans('messages.dataRetrieved')));
    }

    public function getOne(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'code' => 'required'
        ]);

        $leave = $this->leaveDao->getOne($request->code);

        return $this->renderResponse(new AppResponse($leave, trans('messages.dataRetrieved')));
    }

    /**
     * Get all leave LOV
     * Criteria : 
     * - Lov with eligibility for this employee
     * - Lov without eligibility (for everyone)
     * @param request
     */
    public function getLovEmployee(Request $request)
    {
        $lov = [];
        $this->validate($request, [
            "companyId" => "required",
            "employeeId" => "required"
        ]);

        // get required employee data for lookup
        $employeeData = $this->personDao->getEmployeeLookupData($request->employeeId);
        $wkmon = $this->personDao->getWorkingMonth($employeeData->ID);
        if (count($wkmon)) {
            $employeeData->WKMON = $wkmon[0]->wkmon;
        }

        $annualleave = $this->getAnnualLeave($employeeData);
        foreach($annualleave as $leave) {
            array_push($lov, $leave);
        }

        $notAnnualleave = $this->getNotAnnualLeave($employeeData);
        foreach($notAnnualleave as $leave) {
            array_push($lov, $leave);
        }
        
        $notQuotaBased = $this->getNotQuotaBased($employeeData);
        foreach($notQuotaBased as $leave) {
            array_push($lov, $leave);
        }

        $resp = new AppResponse($lov, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }


    /**
     * Get all leave in one company
     * @param request
     */
    public function getLov(Request $request)
    {
        $this->validate($request, ["companyId" => "required"]);
        $lov = $this->leaveDao->getLov();
        $resp = new AppResponse($lov, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function save(Request $request)
    {
        $data = array();
        $this->checkLeaveRequest($request);
        if ($this->leaveDao->checkDuplicateLeaveCode($request->code)) {
            throw new AppException(trans('messages.duplicateCode'));
        }

        // check if default annual leave is exist
        if ($request->quotaType === 'VA' && $request->isAnnualLeave) {
            $defaultAnnualLeave = $this->leaveDao->getDefaultAnnualLeave();
            if (count($defaultAnnualLeave)) {
                throw new AppException(trans('messages.defaultAnnualLeaveExist') . $defaultAnnualLeave->code);
            }
        }

        DB::transaction(function () use (&$request) {
            $leave = $this->constructLeave($request);
            $this->leaveDao->save($leave);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }


    public function update(Request $request)
    {
        $this->validate($request, ['code' => 'required']);
        $this->checkLeaveRequest($request);

        // check if default annual leave is exist
        if ($request->quotaType === 'VA' && $request->isAnnualLeave) {
            $defaultAnnualLeave = $this->leaveDao->getDefaultAnnualLeave();
            if (count($defaultAnnualLeave) && $defaultAnnualLeave->code !== $request->code) {
                throw new AppException(trans('messages.defaultAnnualLeaveExist') . $defaultAnnualLeave->code);
            }
        }

        DB::transaction(function () use (&$request) {
            $leave = $this->constructLeave($request);
            $this->leaveDao->update($request->code, $request->id, $leave);
        });

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'code' => 'required'
        ]);

        DB::transaction(function () use (&$request) {
            $this->leaveDao->delete($request->code);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataDeleted')));
    }

    private function checkLeaveRequest(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'code' => 'required|max:20',
            'name' => 'required|max:50',
            'leaveType' => 'required|max:2',
            'description' => 'present|max:255',
            'minDayTaken' => 'required|integer',
            'maxDayTaken' => 'integer|nullable',
            'isRequestable' => 'boolean',
            'isQuotaBased' => 'boolean',
            'isAllowHalfDay' => 'boolean',
            'isAnnualLeave' => 'boolean',
            'isAnnualLeaveDeductor' => 'required|boolean',
            'max_quota' => 'integer|nullable',
            'quotaType' => 'nullable|max:2',
            'quotaValue'  => 'nullable|max:20',
            'quota_expiration' => 'integer|nullable',
            'carry_expiration_day' => 'integer|nullable',
            'carry_max' => 'integer|nullable',
            'cycleType' => 'max:10',
            'cyclePeriod' => 'max:10'
        ]);
    }

    private function constructLeave(Request $request)
    {
        $leave = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->leaveType,
            'description' => $request->description,
            'day_taken_max' => $request->maxDayTaken,
            'day_taken_min' => $request->minDayTaken,
            'is_requestable' => $request->isRequestable,
            'is_quota_based' => $request->isQuotaBased,
            'max_quota' => $request->quotaAmount,
            'quota_type' => $request->quotaType,
            'quota_value' => $request->quotaValue,
            'quota_expiration' => $request->quotaExpiration,
            'carry_expiration_day' => $request->carryForward,
            'carry_max' => $request->maxCarryForward,
            'is_allow_half_day' => $request->isAllowHalfDay,
            'is_annual_leave' => $request->isAnnualLeave,
            'is_annual_leave_deductor' => $request->isAnnualLeaveDeductor,
            'lov_lcty' => $request->cycleType,
            'lov_lcpt' => $request->cyclePeriod
        ];
        return $leave;
    }

    private function hasDupilcate($array, $obj)
    {
        foreach ($array as $val) {
            if ($val->id === $obj->id) {
                return true;
            }
        }
        return false;
    }

    //
    // ────────────────────────────────────────────────────────────── I ──────────
    //   :::::: L O O K U P   C O D E : :  :   :    :     :        :          :
    // ────────────────────────────────────────────────────────────────────────
    //

    // this function used for queue employee quotas reseter
    public function getLeaveByEligibilitiesPrivate($employeeId)
    {
        // get required employee data for lookup
        $employeeData = $this->personDao->getEmployeeLookupData($employeeId);
        $data = [];
        if (count($employeeData)) {

            $wkmon = $this->personDao->getWorkingMonth($employeeData->ID);
            if (count($wkmon)) {
                $employeeData->WKMON = $wkmon[0]->wkmon;
            }

            $annualLeave = $this->getAnnualLeave($employeeData);
            foreach ($annualLeave as $leave) {
                array_push($data, $leave);
            }
            $notAnnualLeave = $this->getNotAnnualLeave($employeeData);
            foreach ($notAnnualLeave as $leave) {
                array_push($data, $leave);
            }
        }

        return $data;
    }

    private function getAnnualLeave($employeeData)
    {
        $annualLeave = [];
        $annualLeaveQuery = [
            ['field' => 'is_annual_leave', 'conj' => '=', 'val' => '1'],
            ['field' => 'quota_type', 'conj' => '=', 'val' => 'LO']
        ];
        $annualLeaveLookup =  $this->leaveDao->getAllLeaveCustom($annualLeaveQuery);

        foreach ($annualLeaveLookup as $eachLeave) {
            $oneLookUp =  $this->lookupDao->getOneByCode($eachLeave->quotaValue);
            $usedLookupAr = $this->getUsedLookup($oneLookUp);

            $matchLookupDetail = $this->getMatchLookup($employeeData, $oneLookUp, $usedLookupAr);
            if (count($matchLookupDetail)) {
                $eachLeave->quotaAmount = $matchLookupDetail->amount;
                array_push($annualLeave, $eachLeave);
                break;
            }
        }

        // if no eligible leave based on lookup, then give default annual leave
        if (!count($annualLeave)) {
            $annualLeaveDefault = [
                ['field' => 'is_annual_leave', 'conj' => '=', 'val' => '1'],
                ['field' => 'quota_type', 'conj' => '=', 'val' => 'VA']
            ];
            $annualLeaveData = $this->leaveDao->getAllLeaveCustom($annualLeaveDefault);
            foreach ($annualLeaveData as $eachLeave) {
                array_push($annualLeave, $eachLeave);
            }
        }

        return $annualLeave;
    }

    private function getNotAnnualLeave($employeeData)
    {
        $notAnnualLeave = [];
        $notAnnualLeaveQuery = [
            ['field' => 'is_annual_leave', 'conj' => '=', 'val' => '0'],
            ['field' => 'quota_type', 'conj' => '=', 'val' => 'LO'],
            ['field' => 'is_quota_based', 'conj' => '=', 'val' => '1']
        ];
        $notAnnualLeaveData = $this->leaveDao->getAllLeaveCustom($notAnnualLeaveQuery);

        foreach ($notAnnualLeaveData as $eachLeave) {
            $oneLookUp =  $this->lookupDao->getOneByCode($eachLeave->quotaValue);
            $usedLookupAr = $this->getUsedLookup($oneLookUp);

            $matchLookupDetail = $this->getMatchLookup($employeeData, $oneLookUp, $usedLookupAr);
            if (count($matchLookupDetail)) {
                $eachLeave->quotaAmount = $matchLookupDetail->amount;
                array_push($notAnnualLeave, $eachLeave);
            }
        }

        // default not annual leave with quota
        $notAnnualLeaveQuotaBasedDefault = [
            ['field' => 'is_annual_leave', 'conj' => '=', 'val' => '0'],
            ['field' => 'quota_type', 'conj' => '=', 'val' => 'VA'],
            ['field' => 'is_quota_based', 'conj' => '=', 'val' => '1']
        ];
        $notAnnualLeaveData = $this->leaveDao->getAllLeaveCustom($notAnnualLeaveQuotaBasedDefault);
        foreach ($notAnnualLeaveData as $eachLeave) {
            array_push($notAnnualLeave, $eachLeave);
        }

        return $notAnnualLeave;
    }

    private function getNotQuotaBased($employeeData)
    {
        $notQuotaBased = [];
        $notQuotaBasedQuery = [
            ['field' => 'is_annual_leave', 'conj' => '=', 'val' => '0'],
            ['field' => 'quota_type', 'conj' => '=', 'val' => 'LO'],
            ['field' => 'is_quota_based', 'conj' => '=', 'val' => '0']
        ];
        $notQuotaBasedData = $this->leaveDao->getAllLeaveCustom($notQuotaBasedQuery);

        foreach ($notQuotaBasedData as $eachLeave) {
            $oneLookUp =  $this->lookupDao->getOneByCode($eachLeave->quotaValue);
            $usedLookupAr = $this->getUsedLookup($oneLookUp);

            $matchLookupDetail = $this->getMatchLookup($employeeData, $oneLookUp, $usedLookupAr);
            if (count($matchLookupDetail)) {
                $eachLeave->quotaAmount = $matchLookupDetail->amount;
                array_push($notQuotaBased, $eachLeave);
            }
        }

        // default not annual leave with no quota
        $notAnnualLeaveNotQuotaBasedDefault = [
            ['field' => 'is_annual_leave', 'conj' => '=', 'val' => '0'],
            ['field' => 'quota_type', 'conj' => '=', 'val' => 'VA'],
            ['field' => 'is_quota_based', 'conj' => '=', 'val' => '0']
        ];
        $notQuotaBasedData = $this->leaveDao->getAllLeaveCustom($notAnnualLeaveNotQuotaBasedDefault);
        foreach ($notQuotaBasedData as $eachLeave) {
            array_push($notQuotaBased, $eachLeave);
        }

        return $notQuotaBased;
    }

    //
    // ─── HELPER FUNCTION ────────────────────────────────────────────────────────────
    // 
    private function getMatchLookup($employeeData, $oneLookUp, $usedLookupAr)
    {
        foreach ($oneLookUp as $detail) {
            $notMatch = false;
            foreach ($usedLookupAr as $usedLookup) {
                $value = $usedLookup['value'];
                $field = $usedLookup['field'];
                if ($value == 'WKMON') {
                    if ($employeeData->$value <  $detail->$field) {
                        $notMatch = true;
                    }
                } else {
                    if ($employeeData->$value !==  $detail->$field) {
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
        return null;
    }

    private function getUsedLookup($oneLookUp)
    {
        $usedLookup = [];
        $oneLookUpFirst = $oneLookUp[0];

        if ($oneLookUpFirst->lovLook1) {
            $temp = [
                "field"   => 'look1Code',
                "value"   => $oneLookUpFirst->lovLook1,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook2) {
            $temp = [
                "field"   => 'look2Code',
                "value"   => $oneLookUpFirst->lovLook2,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook3) {
            $temp = [
                "field"   => 'look3Code',
                "value"   => $oneLookUpFirst->lovLook3,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook4) {
            $temp = [
                "field"   => 'look4Code',
                "value"   => $oneLookUpFirst->lovLook4,
            ];
            array_push($usedLookup, $temp);
        }
        if ($oneLookUpFirst->lovLook5) {
            $temp = [
                "field"   => 'look5Code',
                "value"   => $oneLookUpFirst->lovLook5,
            ];
            array_push($usedLookup, $temp);
        }

        return $usedLookup;
    }
}
