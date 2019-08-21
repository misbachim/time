<?php

namespace App\Http\Controllers;

use App\Business\Dao\TimeDefinitionDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class for handling Time Definition process
 */
class TimeDefinitionController extends Controller
{
    public function __construct(Requester $requester, TimeDefinitionDao $timeDefinitionDao)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->timeDefinitionDao = $timeDefinitionDao;
    }

    /**
     * Get all Time Definition
     * @param request
     */
    public function getAll(Request $request)
    {
        $this->validate($request, ["companyId" => "required|integer"]);
        $timeDefinition = $this->timeDefinitionDao->getAll();
        $response = new AppResponse($timeDefinition, trans('messages.allDataRetrieved'));

        return $this->renderResponse($response);
    }

    public function getOne(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required'
        ]);

        $timeDefinition = $this->timeDefinitionDao->getOne($request->id);

        return $this->renderResponse(new AppResponse($timeDefinition, trans('messages.dataRetrieved')));
    }

    public function getLovAttendance(Request $request)
    {
        $this->validate($request, ["companyId" => "required|integer"]);
        $attendance = $this->timeDefinitionDao->getLovAtendance();
        $response = new AppResponse($attendance, trans('messages.allDataRetrieved'));

        return $this->renderResponse($response);
    }

    public function getLov(Request $request)
    {
        $this->validate($request, ['companyId' => 'required']);
        $data = $this->timeDefinitionDao->getLov();
        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));

        return $this->renderResponse($resp);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|max:20'
        ]);
//        info('minimum',array($request->minimum));
        $data = array();
        $this->checkTimeDefinitionRequest($request);

        DB::transaction(function () use (&$request, &$data) {
            $timeDefinition= $this->constructTimeDefinition($request);
            $this->timeDefinitionDao->save($timeDefinition);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }


    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer'
        ]);
//        $timezone = new DateTimeZone('Asia/Jakarta');
//        $date = $request->maximum;
//        $date->setTimeZone($timezone);
//        info('maximum',$date);
        $this->checkTimeDefinitionRequest($request);

        DB::transaction(function () use (&$request) {
            $timeDefinition = $this->constructTimeDefinition($request);

            $this->timeDefinitionDao->update($request->id, $timeDefinition);
        });

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    public function delete(Request $request)
    {

        DB::transaction(function () use (&$request) {
            $this->timeDefinitionDao->delete($request->id);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataDeleted')));
    }

    private function checkTimeDefinitionRequest(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'name' => 'required|max:50',
            'description' => 'present|max:255',
            'effBegin' => 'required|date|before_or_equal:effEnd',
            'effEnd' => 'required|date',
            'measurement' => 'required|max:1',
            'eventType' => 'required|max:2',
            'isFlexy' => 'boolean',
            'isWorkday' => 'boolean',
            'isValue1' => 'boolean',
            'isValue2' => 'boolean',
//            'day' => 'max:1',
            'time_group_code' => 'max:20',
            'minimum' => 'before_or_equal:maximum',
            'attendanceStatus' => 'present|max:255'
        ]);
    }

    private function constructTimeDefinition(Request $request)
    {
        $timeDefinition = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'eff_begin' => substr($request->effBegin, 0, 10),
            'eff_end' => substr($request->effEnd, 0, 10),
            'measurement' => $request->measurement,
            'lov_tdevty' => $request->eventType,
            'attendance_codes' => $request->attendanceStatus,
            'lov_tddaty' => $request->dataType,
            'leave_code' => $request->leaveCode,
            'is_workday' => $request->isWorkday,
            'is_flexy' => $request->isFlexy,
            'is_value_1' => $request->isValue1,
            'is_value_2' => $request->isValue2,
//            'day' => $request->day,
            'time_group_code' => $request->timeGroupCode,
            'maximum' => substr($request->maximum, 11, 8),
            'minimum' => substr($request->minimum, 11, 8)
        ];
        return $timeDefinition;
    }
}