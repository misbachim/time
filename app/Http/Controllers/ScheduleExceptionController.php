<?php

namespace App\Http\Controllers;

use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\ScheduleExceptionDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalCoreController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

/**
 * Class for handling schedule exception process
 */
class ScheduleExceptionController extends Controller
{
    public function __construct(Requester $requester,
                                ScheduleExceptionDao $scheduleExceptionDao,
                                ExternalCDNController $externalCDNController,
                                PersonDao $personDao)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->scheduleExceptionDao = $scheduleExceptionDao;
        $this->externalCDNController = $externalCDNController;
        $this->personDao = $personDao;
    }

    /**
     * Get all schedule exception
     * @param request
     */
    public function getAll(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, ["companyId" => "required|integer"]);

        $data = array();
        $schedule = $this->scheduleExceptionDao->getAll();

        $count = count($schedule);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $schedule[$i];
//                $data[$i]->person = $externalCoreController->getEmployee($schedule[$i]->employeeId, $request->applicationId);
                $person = $this->personDao->getOneEmployee($schedule[$i]->employeeId);
                if ($person === null) {
                    $person = [];
                }
                $data[$i]->person = $person;
            }
        }
//        info('data alll', array($data));
        $response = new AppResponse($data, trans('messages.allDataRetrieved'));

        return $this->renderResponse($response);
    }

    public function getOne(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            'companyId' => 'required'
        ]);

        $data = $this->scheduleExceptionDao->getOne(
            $request->id
        );

        if (count($data) > 0) {
//            $data->person = $externalCoreController->getEmployee($data->employeeId, $request->applicationId);
            $person = $this->personDao->getOneEmployee($data->employeeId);
            if ($person === null) {
                $person = [];
            }
            $data->person = $person;
            $data->employeeId = $person->employeeId;
        }

        return $this->renderResponse(new AppResponse($data, trans('messages.dataRetrieved')));
    }

    public function save(Request $request)
    {
        $data = [];
        $this->checkScheduleExceptionRequest($request);

        DB::transaction(function () use (&$request, &$data) {
            $this->constructScheduleException($request);
        });

        return $this->renderResponse(new AppResponse($data, trans('messages.dataSaved')));
    }

    public function saveSwitchSchedule(Request $request, ExternalCoreController $externalCoreController)
    {

        $data = [];
        $this->checkSwitchScheduleExceptionRequest($request);

        DB::transaction(function () use (&$request, &$data, &$externalCoreController) {
            $this->constructSwitchScheduleException($request, $externalCoreController);
        });

        return $this->renderResponse(new AppResponse($data, trans('messages.dataSaved')));
    }


    public function update(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            'id' => 'required|integer'
        ]);
        $this->checkScheduleExceptionUpdateRequest($request);

        DB::transaction(function () use (&$request, &$externalCoreController) {
            $scheduleException = $this->constructScheduleExceptionUpdate($request, $externalCoreController);

            $this->scheduleExceptionDao->update($request->id, $scheduleException);
        });

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    public function delete(Request $request)
    {

        DB::transaction(function () use (&$request) {
            $this->scheduleExceptionDao->delete($request->id);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataDeleted')));
    }

    private function checkScheduleExceptionRequest(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'scheduleException.*.employeeId' => 'required',
            'scheduleException.*.reason' => 'required|present|max:255',
            'scheduleException.*.leaveCode' => 'max:10',
            'scheduleException.*.dateChange' => 'required|date',
            'scheduleException.*.timeIn' => 'nullable|date|after_or_equal:scheduleException.*.dateChange|before_or_equal:scheduleException.*.timeOut',
            'scheduleException.*.timeOut' => 'nullable|date|after_or_equal:scheduleException.*.timeIn|after_or_equal:scheduleException.*.breakEnd',
            'scheduleException.*.breakStart' => 'nullable|date|after_or_equal:scheduleException.*.timeIn|before_or_equal:scheduleException.*.breakEnd',
            'scheduleException.*.breakEnd' => 'nullable|date|after_or_equal:scheduleException.*.breakStart|before_or_equal:scheduleException.*.timeOut'

        ]);
    }

    private function constructScheduleException(Request $request)
    {
        for ($i = 0; $i < count($request->scheduleException); $i++) {

            $data = array();
            $scheduleExceptions = $request->scheduleException[$i];
            if ($scheduleExceptions['leaveCode'] === true) {
                $leaveCode = 'DO';
            } else {
                $leaveCode = '';
            }
            array_push($data, [
                'tenant_id' => $this->requester->getTenantId(),
                'company_id' => $request->companyId,
                'employee_id' => $scheduleExceptions['employeeId'],
                'date' => $scheduleExceptions['dateChange'],
                'leave_code' => $leaveCode,
                'time_in' => $scheduleExceptions['timeIn'],
                'time_out' => $scheduleExceptions['timeOut'],
                'break_start' => $scheduleExceptions['breakStart'],
                'break_end' => $scheduleExceptions['breakEnd'],
                'reason' => $scheduleExceptions['reason']
            ]);
            $this->scheduleExceptionDao->save($data);
        }
    }

    private function checkSwitchScheduleExceptionRequest(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId1' => 'required|integer',
            'employeeId2' => 'required|integer',
            'reason' => 'required|present|max:255',
        ]);
    }

    private function constructSwitchScheduleException(Request $request, ExternalCoreController $externalCoreController)
    {

//        $person1 = $externalCoreController->getPerson($request->employeeId1, $request->applicationId);
        info('employee id 1', array($request->employeeId1));
        $employeeId1 = $request->employeeId1;

//        $person2 = $externalCoreController->getPerson($request->employeeId2, $request->applicationId);
        $employeeId2 = $request->employeeId2;

        if ($request->switchTable === true) {
            for ($i = 0; $i < count($request->schedule1); $i++) {
                $data = array();
                $schedule1 = $request->schedule1[$i];
                if ($schedule1['leaveCode'] === null) {
                    $leaveCode = '';
                } else {
                    $leaveCode = $schedule1['leaveCode'];
                }
                array_push($data, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'employee_id' => $employeeId1,
                    'date' => $schedule1['date'],
                    'leave_code' => $leaveCode,
                    'time_in' => $schedule1['timeIn'],
                    'time_out' => $schedule1['timeOut'],
                    'break_start' => $schedule1['breakStart'],
                    'break_end' => $schedule1['breakEnd'],
                    'reason' => $request->reason
                ]);
                $this->scheduleExceptionDao->save($data);
            }
            for ($j = 0; $j < count($request->schedule2); $j++) {
                $data = array();
                $schedule2 = $request->schedule2[$j];
                if ($schedule2['leaveCode'] === null) {
                    $leaveCode = '';
                } else {
                    $leaveCode = $schedule2['leaveCode'];
                }
                array_push($data, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'employee_id' => $employeeId2,
                    'date' => $schedule2['date'],
                    'leave_code' => $leaveCode,
                    'time_in' => $schedule2['timeIn'],
                    'time_out' => $schedule2['timeOut'],
                    'break_start' => $schedule2['breakStart'],
                    'break_end' => $schedule2['breakEnd'],
                    'reason' => $request->reason
                ]);
                $this->scheduleExceptionDao->save($data);
            }
        } else {
            for ($i = 0; $i < count($request->schedule1); $i++) {
                $data = array();
                $schedule1 = $request->schedule1[$i];
                if ($schedule1['leaveCode'] === null) {
                    $leaveCode = '';
                } else {
                    $leaveCode = $schedule1['leaveCode'];
                }
                array_push($data, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'employee_id' => $employeeId2,
                    'date' => $schedule1['date'],
                    'leave_code' => $leaveCode,
                    'time_in' => $schedule1['timeIn'],
                    'time_out' => $schedule1['timeOut'],
                    'break_start' => $schedule1['breakStart'],
                    'break_end' => $schedule1['breakEnd'],
                    'reason' => $request->reason
                ]);
                $this->scheduleExceptionDao->save($data);
            }
            for ($j = 0; $j < count($request->schedule2); $j++) {
                $data = array();
                $schedule2 = $request->schedule2[$j];
                if ($schedule2['leaveCode'] === null) {
                    $leaveCode = '';
                } else {
                    $leaveCode = $schedule2['leaveCode'];
                }
                array_push($data, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'employee_id' => $employeeId1,
                    'date' => $schedule2['date'],
                    'leave_code' => $leaveCode,
                    'time_in' => $schedule2['timeIn'],
                    'time_out' => $schedule2['timeOut'],
                    'break_start' => $schedule2['breakStart'],
                    'break_end' => $schedule2['breakEnd'],
                    'reason' => $request->reason
                ]);
                $this->scheduleExceptionDao->save($data);
            }
        }
    }

    private function checkScheduleExceptionUpdateRequest(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required',
            'reason' => 'required|present|max:255',
            "leaveCode" => 'max:10',
            "date" => 'required|date',
            "timeIn" => "required|date|after_or_equal:date",
            "timeOut" => "required|date",
            "breakStart" => "required|date|after_or_equal:timeIn|before_or_equal:breakEnd",
            "breakEnd" => "required|date|after_or_equal:breakStart|before_or_equal:timeOut"
        ]);
    }

    private function constructScheduleExceptionUpdate(Request $request, ExternalCoreController $externalCoreController)
    {
        $person = $this->personDao->getOneEmployee($request->employeeId);
//        $person = $externalCoreController->getPerson($request->employeeId, $request->applicationId);
        $employeeId = $person->employeeId;

        if ($request->leaveCode === true) {
            $leaveCode = 'DO';
        } else {
            $leaveCode = '';
        }

        $scheduleExceptions = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $request->companyId,
            'employee_id' => $employeeId,
            'date' => $request->date,
            'leave_code' => $leaveCode,
            'time_in' => $request->timeIn,
            'time_out' => $request->timeOut,
            'break_start' => $request->breakStart,
            'break_end' => $request->breakEnd,
            'reason' => $request->reason
        ];
        return $scheduleExceptions;
    }

    // GENERATE THEMPLATE

    public function generateTemplate()
    {
        $csvHeader = [
            'Date',
            'Employee ID',
            'Leave Code',
            'Time In',
            'Time Out',
            'Break Start',
            'Break End',
            'Reason'
        ];

        return $this->renderResponse(new AppResponse($csvHeader, trans('messages.dataRetrieved')));
    }

    public function importRaw(Request $request)
    {
        $this->validate($request, [
            'ref' => 'required|string|max:255',
            'fileId' => 'required|string'
        ]);

        $fileContent = $this->externalCDNController->doc($request->ref, $request->fileId);
//        info("filecontent", (array)$fileContent);
        $reader = Reader::createFromString($fileContent);
        $reader->setHeaderOffset(0);

        DB::transaction(function () use (&$reader) {
            foreach ($reader->getRecords() as $record) {
                info("leaveCode", (array)$record['leaveCode']);
                if ($record['leaveCode'] !== "") {
                    $timeIn = null;
                    $timeOut = null;
                    $breakStart = null;
                    $breakEnd = null;
                } else {
                    $timeIn = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['timeIn'], 'UTC');
                    $timeOut = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['timeOut'], 'UTC');
                    $breakStart = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['breakStart'], 'UTC');
                    $breakEnd = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['breakEnd'], 'UTC');
                }
                $date = Carbon::createFromFormat('d/m/Y', $record['date'], 'UTC');
                info("timeIn", (array)$timeIn);
                $this->scheduleExceptionDao->saveRaw([
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $this->requester->getCompanyId(),
                    'employee_id' => $record['employeeId'],
                    'date' => $date,
                    'leave_code' => $record['leaveCode'],
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                    'reason' => $record['reason']
                ]);
            }
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }
}