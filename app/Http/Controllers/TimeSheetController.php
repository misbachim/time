<?php

namespace App\Http\Controllers;

use App\Business\Dao\AttendanceDao;
use App\Business\Dao\CalendarDao;
use App\Business\Dao\Core\LocationDao;
use App\Business\Dao\LeaveDao;
use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\LeaveRequestDetailDao;
use App\Business\Dao\PermissionRequestDao;
use App\Business\Dao\LogScheduleDao;
use App\Business\Dao\LogSchedulePatternDao;
use App\Business\Dao\RawTimeSheetDao;
use App\Business\Dao\ScheduleExceptionDao;
use App\Business\Dao\TimeAttributeDao;
use App\Business\Dao\TimeSheetDao;
use App\Business\Dao\Travel\TravelRequestDao;
use App\Business\Dao\WorkSheetDao;
use App\Business\Model\AppResponse;
use App\Business\Model\PagingAppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use Carbon\Carbon;
use Faker\Provider\DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\ExternalCoreController;
use App\Business\Dao\Core\PersonDao;

class TimeSheetController extends Controller
{
    private $requester;
    private $timeSheetDao;
    private $timeAttributeDao;
    private $rawTimeSheetDao;
    private $logScheduleDao;
    private $logSchedulePatternDao;
    private $leaveRequestDetailDao;
    private $leaveRequestDao;
    private $permissionRequestDao;
    private $workSheetDao;
    private $externalCoreController;
    private $externalCDNController;
    private $calendarDao;
    private $leaveDao;
    private $attendanceDao;
    private $travelRequestDao;
    private $personDao;

    public function __construct(
        Requester $requester,
        TimeSheetDao $timeSheetDao,
        TimeAttributeDao $timeAttributeDao,
        RawTimeSheetDao $rawTimeSheetDao,
        LogScheduleDao $logScheduleDao,
        LogSchedulePatternDao $logSchedulePatternDao,
        LeaveRequestDetailDao $leaveRequestDetailDao,
        LeaveRequestDao $leaveRequestDao,
        PermissionRequestDao $permissionRequestDao,
        WorkSheetDao $workSheetDao,
        ScheduleExceptionDao $scheduleExceptionDao,
        ExternalCoreController $externalCoreController,
        ExternalCDNController $externalCDNController,
        CalendarDao $calendarDao,
        LeaveDao $leaveDao,
        AttendanceDao $attendanceDao,
        TravelRequestDao $travelRequestDao,
        LocationDao $locationDao,
        PersonDao $personDao
    )
    {
        $this->requester = $requester;
        $this->timeSheetDao = $timeSheetDao;
        $this->timeAttributeDao = $timeAttributeDao;
        $this->rawTimeSheetDao = $rawTimeSheetDao;
        $this->logScheduleDao = $logScheduleDao;
        $this->logSchedulePatternDao = $logSchedulePatternDao;
        $this->leaveRequestDetailDao = $leaveRequestDetailDao;
        $this->leaveRequestDao = $leaveRequestDao;
        $this->permissionRequestDao = $permissionRequestDao;
        $this->workSheetDao = $workSheetDao;
        $this->scheduleExceptionDao = $scheduleExceptionDao;
        $this->externalCoreController = $externalCoreController;
        $this->externalCDNController = $externalCDNController;
        $this->calendarDao = $calendarDao;
        $this->leaveDao = $leaveDao;
        $this->attendanceDao = $attendanceDao;
        $this->travelRequestDao = $travelRequestDao;
        $this->locationDao = $locationDao;
        $this->personDao = $personDao;
    }

    // GENERATE THEMPLATE

    public function generateTemplate()
    {
        $csvHeader = [
            'Date',
            'Employee ID',
            'Clock Time',
            'Type (In/Out)'
        ];

        return $this->renderResponse(new AppResponse($csvHeader, trans('messages.dataRetrieved')));
    }

    //END

    public function getAll(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required",
            "pageInfo" => "required|array"
        ]);

        $reqData = $request->pageInfo;
        $request->merge($reqData);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);

        $timeSheets = $this->timeSheetDao->getAll($offset, $limit);

        $totalRows = $this->timeSheetDao->getTotalRows();

        return $this->renderResponse(
            new PagingAppResponse(
                $timeSheets,
                trans('messages.allDataRetrieved'),
                $limit,
                $totalRows,
                $pageNo)
        );
    }


    public function getAllRaw(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required",
            "pageInfo" => "required|array"
        ]);

        $reqData = $request->pageInfo;
        $request->merge($reqData);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);

        $rawTimeSheets = $this->rawTimeSheetDao->getAll($offset, $limit);

        $totalRows = $this->rawTimeSheetDao->getTotalRows();

        return $this->renderResponse(
            new PagingAppResponse(
                $rawTimeSheets,
                trans('messages.allDataRetrieved'),
                $limit,
                $totalRows,
                $pageNo)
        );
    }

    public function getAllByPerson(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required'
        ]);

        $timeSheets = $this->timeSheetDao->getAllByPerson($request->employeeId, $request->limit, $request->offset);
        if ($timeSheets) {
            for ($i = 0; $i < count($timeSheets); $i++) {
                $worksheet = $this->workSheetDao->getAllByPersonAndDate($request->employeeId, $timeSheets[$i]->date);
                $timeSheets[$i]->worksheetLength = count($worksheet);
            }
        }

        return $this->renderResponse(new AppResponse($timeSheets, trans('messages.allDataRetrieved')));
    }

    public function getDisplayDataClocking(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date|after_or_equal:dateStart'
        ]);

        $timeSheets = $this->timeSheetDao->getDisplayDataClocking($request->employeeId, $request->dateStart, $request->dateEnd);

        return $this->renderResponse(new AppResponse($timeSheets, trans('messages.allDataRetrieved')));
    }

    public function getLatestClockingData(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required'
        ]);

        $timeSheets = $this->rawTimeSheetDao->getLatestFiveClockingData($request->employeeId);
        info('ts', [$timeSheets]);

        if ($timeSheets) {
            for ($i = 0; $i < count($timeSheets); $i++) {
                $timeSheets[$i]->locationName = null;
                if ($timeSheets[$i]->locationCode) {
                    $location = $this->locationDao->getOneByCode($timeSheets[$i]->locationCode);
                    $timeSheets[$i]->locationName = $location->name;
                }
            }
        }
        return $this->renderResponse(new AppResponse($timeSheets, trans('messages.allDataRetrieved')));
    }

    public function getAllRawByPerson(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required'
        ]);

        $rawTimeSheets = $this->rawTimeSheetDao->getAllByPerson($request->employeeId);

        return $this->renderResponse(new AppResponse($rawTimeSheets, trans('messages.allDataRetrieved')));
    }

    public function getOne(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required',
            'date' => 'required|date'
        ]);

        $timeSheet = $this->timeSheetDao->getOne($request->employeeId, $request->date);

        return $this->renderResponse(new AppResponse($timeSheet, trans('messages.dataRetrieved')));
    }

    public function getOneRaw(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required',
            'date' => 'required|date'
        ]);

        $rawTimeSheet = $this->rawTimeSheetDao->getOne($request->employeeId, $request->date);

        return $this->renderResponse(new AppResponse($rawTimeSheet, trans('messages.dataRetrieved')));
    }

    public function saveRaw(Request $request)
    {
//      return ['data' => Carbon::parse('1975-05-21 22:23:00.123456')];
        $this->checkRawTimeSheetRequest($request);

        info('', [$request]);

        DB::transaction(function () use (&$request) {
            $rawTimeSheet = $this->constructRawTimeSheet($request);
            info('saveRaw', [$rawTimeSheet]);
            $this->rawTimeSheetDao->save($rawTimeSheet);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }

    public function importRaw(Request $request)
    {
        $this->validate($request, [
            'ref' => 'required|string|max:255',
            'fileId' => 'required|string'
        ]);

        $fileContent = $this->externalCDNController->doc($request->ref, $request->fileId);
        $reader = Reader::createFromString($fileContent);
        $reader->setHeaderOffset(0);

        DB::transaction(function () use (&$reader) {
            foreach ($reader->getRecords() as $record) {
                $date = Carbon::createFromFormat('d/m/Y', $record['date'], 'UTC');

                if (strtolower($record['type']) == 'out') {
                    $type = 'O';
                } else {
                    $type = 'I';
                }

                $clockTime = null;
                if ($record['clockTime']) {
                    $clockTime = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['clockTime'], 'UTC');
                }
//                $timeIn = null;
//                $timeOut = null;
//
//                info("date", (array)$record['date']);
//                info("timeIn", (array)$record['timeIn']);
//
//                if ($record['timeIn']) {
//                    $timeIn = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['timeIn'], 'UTC');
//                }
//                info("timeOut", (array)$record['timeOut']);
//
//                if ($record['timeOut']) {
//                    $timeOut = Carbon::createFromFormat('d/m/Y H:i', $record['date'] . ' ' . $record['timeOut'], 'UTC');
//                }
//
//                if (strtotime($record['timeOut']) < strtotime($record['timeIn']) && $record['timeOut'] != null) {
//                    $tempDate = Carbon::createFromFormat('d/m/Y', $record['date']);
//                    $tempDate2 = Carbon::parse($tempDate)->addDay();
//                    $newDate = date('d/m/Y', strtotime($tempDate2));
//                    info("newDate", [$newDate]);
//                    $timeOut = Carbon::createFromFormat('d/m/Y H:i', $newDate . ' ' . $record['timeOut'], 'UTC');
//                }

                $this->rawTimeSheetDao->save([
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $this->requester->getCompanyId(),
                    'employee_id' => $record['employeeId'],
                    'date' => $date,
                    'type' => $type,
                    'clock_time' => $clockTime,
                    'is_analyzed' => false
                ]);
            }
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }

    public function updateRaw(Request $request)
    {
        $this->checkRawTimeSheetRequest($request);
        $this->validate($request, ['date' => 'exists:raw_timesheets,date']);
        info('', [$request]);

        DB::transaction(function () use (&$request) {
            $rawTimeSheet = $this->constructRawTimeSheet($request);
            $this->rawTimeSheetDao->updateById($request->id, $rawTimeSheet);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataUpdated')));
    }

    public function deleteRaw(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'id' => 'required'
        ]);

        DB::transaction(function () use (&$request) {
            $this->rawTimeSheetDao->delete($request->id);
        });

        return $this->renderResponse(
            new AppResponse(
                null,
                trans('messages.dataDeleted')));
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date',
            'pageInfo' => 'required|array'
        ]);

        $reqData = $request->pageInfo;
        $request->merge($reqData);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);
        $order = PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $result = $this->timeSheetDao->search(
            $request->startDate,
            $request->endDate,
            $offset,
            $limit,
            $order,
            $orderDirection
        );

//        foreach ($result[0] as $raw) {
//            $person = $this->personDao->getOneEmployee($raw->employeeId);
//            if ($person) {
//                $raw->employeeName = $person->firstName . ' ' . $person->lastName;
//                $raw->personId = $person->id;
//            }
//        }

        $tempEmployeeIds = [];
        foreach ($result[0] as $raw) {
            array_push($tempEmployeeIds, $raw->employeeId);
        }

        $person = $this->personDao->getManyEmployee($tempEmployeeIds);

        foreach ($result[0] as $raw) {
            if (count($person) > 0) {
                $tempEmployees = $person->first(function ($datum) use (&$raw) {
                    return (string)$datum->employeeId === (string)$raw->employeeId;
                });
                if ($tempEmployees) {
                    $raw->employeeName = $tempEmployees->firstName . ' ' . $tempEmployees->lastName;
                    $raw->personId = $tempEmployees->id;
                }
            }

            $leave = $this->leaveDao->getOne($raw->leaveCode);
            if ($leave) {
                $raw->leave = $leave->name;
            }

            $attendances = explode(',', $raw->attendanceCode);
            if ($attendances) {
                foreach ($attendances as $atts) {
                    $attendance = $this->attendanceDao->getOne($atts);
                    if ($attendance) {
                        if (isset($raw->attendanceStatus)) {
                            $raw->attendanceStatus = $raw->attendanceStatus . ',' . $attendance->name;
                        } else {
                            $raw->attendanceStatus = $attendance->name;
                        }
                    }
                }
            }
        }

        $timeSheets = $result[0];
        $totalRows = $result[1];

        \Log::info(json_encode(['startDate' => $request->startDate,
            'endDate' => $request->endDate,
        ]));

        return $this->renderResponse(
            new PagingAppResponse(
                $timeSheets,
                trans('messages.allDataRetrieved'),
                $limit, $totalRows, $pageNo)
        );
    }

    public
    function advancedSearch(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'menuCode' => 'required',
            'criteria' => 'nullable|present|array',
            'criteria.*.field' => 'nullable|min:1',
            'criteria.*.val' => '',
            'personCriteria' => 'nullable|present|array',
            'personCriteria.*.field' => 'nullable|min:1|max:4',
            'personCriteria.*.conj' => 'nullable|min:1|max:3',
            'personCriteria.*.val' => '',
            'pageInfo' => 'required'
        ]);
        $request->merge((array)$request->pageInfo);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
            'order' => 'nullable|present|string',
            'orderDirection' => 'nullable|present|in:asc,desc',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);
        $order = PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $employeeIds = [];
        if ($request->personCriteria) {
            $result = $this->externalCoreController->advancedSearchPerson(
                $this->requester->getCompanyId(),
                $request->menuCode,
                ['PI1'],
                $request->personCriteria,
                [
                    'pageLimit' => null,
                    'pageNo' => null,
                    'order' => null,
                    'orderDirection' => null
                ],
                $request->applicationId
            );
            $employeeIds = array_map(function ($person) {
                return $person['employeeId'];
            }, $result);
        }

        $data = $this->timeSheetDao->advancedSearch(
            $request->criteria ? $request->criteria : [],
            $employeeIds,
            $offset,
            $limit,
            $order,
            $orderDirection
        );

        $tempEmployeeIds = [];
        foreach ($data[0] as $raw) {
            array_push($tempEmployeeIds, $raw->employeeId);
        }

        $person = $this->personDao->getManyEmployee($tempEmployeeIds);

        foreach ($data[0] as $raw) {
            if (count($person) > 0) {
                $tempEmployees = $person->first(function ($datum) use (&$raw) {
                    return (string)$datum->employeeId === (string)$raw->employeeId;
                });
                if ($tempEmployees) {
                    $raw->employeeName = $tempEmployees->firstName . ' ' . $tempEmployees->lastName;
                    $raw->personId = $tempEmployees->id;
                }
            }

            $leave = $this->leaveDao->getOne($raw->leaveCode);
            if ($leave) {
                $raw->leave = $leave->name;
            }

            $attendances = explode(',', $raw->attendanceCode);
            if ($attendances) {
                foreach ($attendances as $atts) {
                    $attendance = $this->attendanceDao->getOne($atts);
                    if ($attendance) {
                        if (isset($raw->attendanceStatus)) {
                            $raw->attendanceStatus = $raw->attendanceStatus . ',' . $attendance->name;
                        } else {
                            $raw->attendanceStatus = $attendance->name;
                        }
                    }
                }
            }
        }

        $timeSheets = $data[0];
        $totalRows = $data[1];
        info('timesheet', [$timeSheets]);

        return $this->renderResponse(
            new PagingAppResponse(
                $timeSheets,
                trans('messages.dataRetrieved'),
                $limit,
                $totalRows,
                $pageNo)
        );
    }


    public
    function searchRaw(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date',
            'pageInfo' => 'required|array'
        ]);

        $reqData = $request->pageInfo;
        $request->merge($reqData);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);
        $order = PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $result = $this->rawTimeSheetDao->search(
            $request->startDate,
            $request->endDate,
            $offset,
            $limit,
            $order,
            $orderDirection
        );

        foreach ($result[0] as $raw) {
            $person = $this->personDao->getOneEmployee($raw->employeeId);
            if ($person) {
                $raw->employeeName = $person->firstName . ' ' . $person->lastName;
                $raw->personId = $person->id;
            }
        }
        $rawTimeSheets = $result[0];
        $totalRows = $result[1];

        return $this->renderResponse(
            new PagingAppResponse($rawTimeSheets, trans('messages.allDataRetrieved'), $limit, $totalRows, $pageNo)
        );
    }

    public
    function advancedSearchRaw(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'menuCode' => 'required',
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date',
            'criteria' => 'present|array',
            'criteria.*.field' => 'nullable|min:1',
            'criteria.*.val' => '',
            'personCriteria' => 'nullable|present|array',
            'personCriteria.*.field' => 'nullable|min:1|max:4',
            'personCriteria.*.conj' => 'nullable|min:1|max:3',
            'personCriteria.*.val' => '',
            'pageInfo' => 'required'
        ]);
        $request->merge((array)$request->pageInfo);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
            'order' => 'nullable|present|string',
            'orderDirection' => 'nullable|present|in:asc,desc',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);
        $order = PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $employeeIds = [];
        if ($request->personCriteria) {
            $result = $this->externalCoreController->advancedSearchPerson(
                $this->requester->getCompanyId(),
                $request->menuCode,
                ['PI1'],
                $request->personCriteria,
                [
                    'pageLimit' => $limit,
                    'pageNo' => $pageNo,
                    'order' => $order,
                    'orderDirection' => $orderDirection
                ],
                $request->applicationId
            );
            $employeeIds = array_map(function ($person) {
                return $person['employeeId'];
            }, $result);
        }

        $data = $this->rawTimeSheetDao->advancedSearch(
            $request->startDate,
            $request->endDate,
            $request->criteria ? $request->criteria : [],
            $employeeIds,
            $offset,
            $limit,
            $order,
            $orderDirection
        );
        foreach ($data[0] as $raw) {
            $person = $this->personDao->getOneEmployee($raw->employeeId);
            if ($person) {
                $raw->employeeName = $person->firstName . ' ' . $person->lastName;
                $raw->personId = $person->id;
            }
        }
        $rawTimeSheets = $data[0];
        $totalRows = $data[1];

        return $this->renderResponse(
            new PagingAppResponse($rawTimeSheets, trans('messages.dataRetrieved'), $limit, $totalRows, $pageNo)
        );
    }

    public
    function analyze(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'selectedTimeSheets' => 'required|array|min:1',
            'selectedTimeSheets.*.date' => 'required|date',
            'selectedTimeSheets.*.employeeId' => 'required|string'
        ]);

        DB::transaction(function () use (&$request) {
            foreach ($request->selectedTimeSheets as $selectedTimeSheet) {
                $scheduleException = $this->scheduleExceptionDao->getOneByEmployeeAndDate($selectedTimeSheet['employeeId'], $selectedTimeSheet['date']);
                if ($scheduleException) {
                    $timeGroup = $this->timeAttributeDao->getOneEmployeeId($scheduleException->employeeId);
                    if ($scheduleException->leaveCode) {
                        $this->timeSheetDao->save([
                            'tenant_id' => $this->requester->getTenantId(),
                            'company_id' => $this->requester->getCompanyId(),
                            'employee_id' => $scheduleException->employeeId,
                            'date' => $scheduleException->date,
                            'is_work_day' => false,
                            'is_flexy_hour' => false,
                            'schedule_time_in' => null,
                            'time_in' => null,
                            'time_in_deviation' => null,
                            'schedule_time_out' => null,
                            'time_out' => null,
                            'time_out_deviation' => null,
                            'schedule_duration' => null,
                            'duration' => null,
                            'duration_deviation' => null,
                            'attendance_code' => null,
                            'leave_code' => $scheduleException->leaveCode,
                            'leave_weight' => 1,
                            'value_1' => null,
                            'value_2' => null,
                            'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                            'overtime' => null
                        ]);
                    } else {
                        $rawTimeSheet = $this->rawTimeSheetDao->getOneFull($scheduleException->employeeId, $scheduleException->date);
                        if ($rawTimeSheet) {
                            //worksheet
                            $worksheet = $this->workSheetDao->getTotalValue($rawTimeSheet->employee_id, $rawTimeSheet->date);
                            $value1 = null;
                            $value2 = null;
                            if ($worksheet) {
                                $value1 = $worksheet->value1;
                                $value2 = $worksheet->value2;
                            }
                            info('$worksheet',[$worksheet]);

                            $scheduleTimeIn = Carbon::parse($scheduleException->timeIn);
                            $scheduleTimeOut = Carbon::parse($scheduleException->timeOut);
                            $interval = $scheduleTimeIn->diffInSeconds($scheduleTimeOut);
                            $scheduleDuration = Carbon::parse(date('H:i', mktime(0, $interval)));

                            $logSchedule = $this->logScheduleDao->getOneByTargetDate(
                                $rawTimeSheet->time_group_code,
                                $rawTimeSheet->date
                            );

                            if ($logSchedule) {
                                $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
                                $dateStart = Carbon::parse($logSchedule->dateStart);
                                $targetDate = Carbon::parse($rawTimeSheet->date);
                                $nextTargetDate = Carbon::parse($rawTimeSheet->date)->addDay();

                                if ($pattern->count() !== 0) {
                                    $patternIdx = $targetDate->diffInDays($dateStart) % $pattern->count();
                                    $scheduleDetail = $pattern->slice($patternIdx, 1)->first();
                                    $scheduleForDate = TimeGroupController::constructScheduleForDate($targetDate, $scheduleDetail);

                                    if ($patternIdx != $pattern->count() - 1) {
                                        $nextScheduleDetail = $pattern->slice($patternIdx + 1, 1)->first();
                                    } else {
                                        $nextScheduleDetail = $pattern->slice(0, 1)->first();
                                    }

                                    $nextScheduleForDate = TimeGroupController::constructScheduleForDate($nextTargetDate, $nextScheduleDetail);
                                    $deviation = 5;

                                    $rawTimeIn = $this->rawTimeSheetDao->getOneFirstClockIn($scheduleException->employeeId, $scheduleException->date, $scheduleForDate['timeIn'], $deviation);
                                    $rawTimeOut = $this->rawTimeSheetDao->getOneLastClockOut($scheduleException->employeeId, $scheduleException->date, $scheduleForDate['timeIn'], $nextScheduleForDate['timeIn'], $deviation);

                                    $attendanceCode = $this->getAttendanceCode(
                                        $rawTimeSheet,
                                        $rawTimeIn ? $rawTimeIn->clock_time : null,
                                        $rawTimeOut ? $rawTimeOut->clock_time : null,
                                        $scheduleTimeIn,
                                        $scheduleTimeOut,
                                        $scheduleDuration
                                    );

                                    $timeInDeviation = null;
                                    $timeOutDeviation = null;
                                    $duration = null;
                                    $durationDeviation = null;
                                    $overtime = null;

                                    if ($rawTimeIn) {
                                        $timeIn = Carbon::parse($rawTimeIn->clock_time);
                                    }
                                    if ($rawTimeOut) {
                                        $timeOut = Carbon::parse($rawTimeOut->clock_time);
                                    }

                                    if ($rawTimeIn && $rawTimeOut) {
                                        $duration = (clone $targetDate)->second($timeIn->diffInSeconds($timeOut));

                                        $timeInDiff = $scheduleTimeIn->diffInSeconds($timeIn);
                                        $timeInDeviation = (clone $targetDate)->second($timeInDiff);
                                        $timeOutDiff = $scheduleTimeOut->diffInSeconds($timeOut);
                                        $timeOutDeviation = (clone $targetDate)->second($timeOutDiff);
                                        $durationDiff = $scheduleDuration->diffInSeconds($duration);
                                        $durationDeviation = (clone $targetDate)->second($durationDiff);

                                        $overtime = $this->calculateOvertime(false, $rawTimeSheet, $rawTimeIn->clock_time, $rawTimeOut->clock_time);
                                    }

                                    $this->timeSheetDao->save([
                                        'tenant_id' => $this->requester->getTenantId(),
                                        'company_id' => $this->requester->getCompanyId(),
                                        'employee_id' => $rawTimeSheet->employee_id,
                                        'date' => $rawTimeSheet->date,
                                        'is_work_day' => true,
                                        'is_flexy_hour' => $rawTimeSheet->is_flexy_hour,
                                        'schedule_time_in' => $scheduleTimeIn->toDateTimeString(),
                                        'time_in' => $rawTimeIn ? $rawTimeIn->clock_time : null,
                                        'time_in_deviation' => $timeInDeviation ? $timeInDeviation->toTimeString() : null,
                                        'schedule_time_out' => $scheduleTimeOut->toDateTimeString(),
                                        'time_out' => $rawTimeOut ? $rawTimeOut->clock_time : null,
                                        'time_out_deviation' => $timeOutDeviation ? $timeOutDeviation->toTimeString() : null,
                                        'schedule_duration' => $scheduleDuration->toTimeString(),
                                        'duration' => $duration ? $duration->toTimeString() : null,
                                        'duration_deviation' => $durationDeviation ? $durationDeviation->toTimeString() : null,
                                        'attendance_code' => $attendanceCode,
                                        'leave_code' => null,
                                        'leave_weight' => null,
                                        'value_1' => $value1,
                                        'value_2' => $value2,
                                        'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                        'overtime' => $overtime
                                    ]);
                                    $this->rawTimeSheetDao->update($rawTimeSheet->employee_id, $rawTimeSheet->date, ['is_analyzed' => true]);
                                }
                            }

                        } else {
                            if ($timeGroup) {

                                $a = Carbon::parse($scheduleException->timeIn);
                                $b = Carbon::parse($scheduleException->timeOut);
                                $interval = $a->diffInMinutes($b);
                                info('interval=', (array)$interval);
                                $this->timeSheetDao->save([
                                    'tenant_id' => $this->requester->getTenantId(),
                                    'company_id' => $this->requester->getCompanyId(),
                                    'employee_id' => $scheduleException->employeeId,
                                    'date' => $scheduleException->date,
                                    'is_work_day' => true,
                                    'is_flexy_hour' => false,
                                    'schedule_time_in' => $scheduleException->timeIn,
                                    'time_in' => null,
                                    'time_in_deviation' => null,
                                    'schedule_time_out' => $scheduleException->timeOut,
                                    'time_out' => null,
                                    'time_out_deviation' => null,
                                    'schedule_duration' => $interval ? date('H:i', mktime(0, $interval)) : null,
                                    'duration' => null,
                                    'duration_deviation' => null,
                                    'attendance_code' => null,
                                    'leave_code' => 'ABS',
                                    'leave_weight' => 1,
                                    'value_1' => null,
                                    'value_2' => null,
                                    'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                    'overtime' => null
                                ]);

                                if ($timeGroup->isAbsenceAsAnnualLeave) {
                                    $leaveRequest = [
                                        'tenant_id' => $this->requester->getTenantId(),
                                        'company_id' => $this->requester->getCompanyId(),
                                        'employee_id' => $scheduleException->employee_id,
                                        'leave_code' => 'AL-LV',
                                        'description' => 'Auto-generate because of absence',
                                        'status' => 'A'
                                    ];
                                    $leaveRequestId = $this->leaveRequestDao->save($leaveRequest);

                                    $leaveRequestDetail = [
                                        'tenant_id' => $this->requester->getTenantId(),
                                        'company_id' => $this->requester->getCompanyId(),
                                        'leave_request_id' => $leaveRequestId,
                                        'weight' => 1,
                                        'status' => 'A',
                                        'date' => $scheduleException->date
                                    ];
                                    $this->leaveRequestDetailDao->save($leaveRequestDetail);
                                }
                            }
                        }
                    }
                } else {
                    $rawTimeSheet = $this->rawTimeSheetDao->getOneFull($selectedTimeSheet['employeeId'], $selectedTimeSheet['date']);
                    info('date', [$selectedTimeSheet['date']]);
                    info('$rawTimeSheet', [$rawTimeSheet]);
//                if (!$rawTimeSheet) {
//                    throw new AppException(trans('messages.employeeIsNotInTimeGroup'));
//                }
                    $timeGroup = $this->timeAttributeDao->getOneEmployeeId($selectedTimeSheet['employeeId']);
                    info('$timeGroup', [$timeGroup]);
                    info('not sch exp', []);

                    if ($rawTimeSheet) {
                        //worksheet
                        $worksheet = $this->workSheetDao->getTotalValue($rawTimeSheet->employee_id, $rawTimeSheet->date);
                        $value1 = null;
                        $value2 = null;
                        if ($worksheet) {
                            $value1 = $worksheet->value1;
                            $value2 = $worksheet->value2;
                        }
                        info('$worksheet',[$worksheet]);

                        $logSchedule = $this->logScheduleDao->getOneByTargetDate(
                            $rawTimeSheet->time_group_code,
                            $rawTimeSheet->date
                        );
                        info('have unp timesheet', []);

                        if ($logSchedule) {
                            $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
                            $dateStart = Carbon::parse($logSchedule->dateStart);
                            $targetDate = Carbon::parse($rawTimeSheet->date);
                            $nextTargetDate = Carbon::parse($rawTimeSheet->date)->addDay();

//                        if ($pattern->count() === 0) {
//                            throw new AppException(trans('messages.scheduleNotExists'));
//                        }
                            info('have schedule', []);

                            if ($pattern->count() !== 0) {
                                $patternIdx = $targetDate->diffInDays($dateStart) % $pattern->count();
                                $scheduleDetail = $pattern->slice($patternIdx, 1)->first();
//                            info('$patternIdx',[$patternIdx]);
//                            info('$pattern',[$pattern->count()]);
//                            info('$scheduleDetail',[$scheduleDetail]);
                                info('have pattern', []);

                                if ($patternIdx != $pattern->count() - 1) {
                                    info('not first pattern', []);
                                    $nextScheduleDetail = $pattern->slice($patternIdx + 1, 1)->first();
                                } else {
                                    info('first pattern', []);
                                    $nextScheduleDetail = $pattern->slice(0, 1)->first();
                                }

//                            info('$nextScheduleDetail',[$nextScheduleDetail]);
                                $scheduleForDate = TimeGroupController::constructScheduleForDate($targetDate, $scheduleDetail);
                                $nextScheduleForDate = TimeGroupController::constructScheduleForDate($nextTargetDate, $nextScheduleDetail);

                                $scheduleTimeIn = Carbon::parse($scheduleForDate['timeIn']);
                                $scheduleTimeOut = Carbon::parse($scheduleForDate['timeOut']);
                                $scheduleDuration = (clone $targetDate)->second($scheduleForDate['durationInSeconds']);

                                $deviation = 5;
                                $rawTimeIn = $this->rawTimeSheetDao->getOneFirstClockIn($selectedTimeSheet['employeeId'], $selectedTimeSheet['date'], $scheduleForDate['timeIn'], $deviation);
                                $rawTimeOut = $this->rawTimeSheetDao->getOneLastClockOut($selectedTimeSheet['employeeId'], $selectedTimeSheet['date'], $scheduleForDate['timeIn'], $nextScheduleForDate['timeIn'], $deviation);

                                info('$rawTimeIn', [$rawTimeIn]);
                                info('$rawTimeOut', [$rawTimeOut]);

                                $holiday = $this->calendarDao->getOneHoliday($selectedTimeSheet['date']);

                                if ($scheduleDetail->leaveCode || ($holiday && !$rawTimeIn) || ($holiday && !$rawTimeOut)) {
                                    info('off day', []);
                                    $timeIn = null;
                                    $timeOut = null;
                                    $duration = null;
                                    $overtime = null;

                                    if ($rawTimeIn) {
                                        info('have time in', []);
                                        $timeIn = Carbon::parse($rawTimeIn->clock_time);
                                    }
                                    if ($rawTimeOut) {
                                        info('have time out', []);
                                        $timeOut = Carbon::parse($rawTimeOut->clock_time);
                                    }

                                    if ($rawTimeIn && $rawTimeOut) {
                                        info('have both in out', []);
                                        $duration = (clone $targetDate)->second($timeIn->diffInSeconds($timeOut));
                                        $overtime = $this->calculateOvertime(false, $rawTimeSheet, $rawTimeIn->clock_time, $rawTimeOut->clock_time);
                                    }
                                    $leaveRequestDetail = $this->leaveRequestDetailDao->getOneByPersonLeaveCodeAndDate(
                                        $rawTimeSheet->employee_id,
                                        $scheduleDetail->leaveCode,
                                        $rawTimeSheet->date
                                    );
                                    $weight = $leaveRequestDetail ? $leaveRequestDetail->weight : null;

                                    $leaveCode = $scheduleDetail->leaveCode;
                                    if ($holiday) {
                                        $leaveCode = $holiday->leaveCode;
                                    }
                                    info('holiday', [$holiday]);
                                    info('leave code', [$leaveCode]);

                                    $this->timeSheetDao->save([
                                        'tenant_id' => $this->requester->getTenantId(),
                                        'company_id' => $this->requester->getCompanyId(),
                                        'employee_id' => $rawTimeSheet->employee_id,
                                        'date' => $rawTimeSheet->date,
                                        'is_work_day' => false,
                                        'is_flexy_hour' => $rawTimeSheet->is_flexy_hour,
                                        'schedule_time_in' => null,
                                        'time_in' => $timeIn ? $timeIn->toDateTimeString() : null,
                                        'time_in_deviation' => null,
                                        'schedule_time_out' => null,
                                        'time_out' => $timeOut ? $timeOut->toDateTimeString() : null,
                                        'time_out_deviation' => null,
                                        'schedule_duration' => null,
                                        'duration' => $duration ? $duration->toTimeString() : null,
                                        'duration_deviation' => null,
                                        'attendance_code' => null,
                                        'leave_code' => $leaveCode,
                                        'leave_weight' => $weight,
                                        'value_1' => $value1,
                                        'value_2' => $value2,
                                        'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                        'overtime' => $overtime
                                    ]);
                                } else {
                                    info('work day', []);
                                    $leaveRequestDetail = $this->leaveRequestDetailDao->getOneByPersonAndDate(
                                        $rawTimeSheet->employee_id,
                                        $rawTimeSheet->date
                                    );

                                    if ($leaveRequestDetail) {
                                        info('have leave req', []);
                                        $weight = $leaveRequestDetail ? $leaveRequestDetail->weight : null;

                                        $this->timeSheetDao->save([
                                            'tenant_id' => $this->requester->getTenantId(),
                                            'company_id' => $this->requester->getCompanyId(),
                                            'employee_id' => $rawTimeSheet->employee_id,
                                            'date' => $rawTimeSheet->date,
                                            'is_work_day' => true,
                                            'is_flexy_hour' => false,
                                            'schedule_time_in' => $scheduleTimeIn->toDateTimeString(),
                                            'time_in' => null,
                                            'time_in_deviation' => null,
                                            'schedule_time_out' => $scheduleTimeOut->toDateTimeString(),
                                            'time_out' => null,
                                            'time_out_deviation' => null,
                                            'schedule_duration' => $scheduleDuration->toTimeString(),
                                            'duration' => null,
                                            'duration_deviation' => null,
                                            'attendance_code' => null,
                                            'leave_code' => $leaveRequestDetail->leaveCode,
                                            'leave_weight' => $weight,
                                            'value_1' => $value1,
                                            'value_2' => $value2,
                                            'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                            'overtime' => null
                                        ]);
                                    } else {
                                        info('dont have leave req', []);
                                        $attendanceCode = $this->getAttendanceCode(
                                            $rawTimeSheet,
                                            $rawTimeIn ? $rawTimeIn->clock_time : null,
                                            $rawTimeOut ? $rawTimeOut->clock_time : null,
                                            $scheduleTimeIn,
                                            $scheduleTimeOut,
                                            $scheduleDuration
                                        );

                                        $timeIn = null;
                                        $timeOut = null;
                                        $timeInDeviation = null;
                                        $timeOutDeviation = null;
                                        $duration = null;
                                        $durationDeviation = null;
                                        $overtime = null;

                                        if ($rawTimeIn) {
                                            info('have time in', []);
                                            $timeIn = Carbon::parse($rawTimeIn->clock_time);
                                        }
                                        if ($rawTimeOut) {
                                            info('have time out', []);
                                            $timeOut = Carbon::parse($rawTimeOut->clock_time);
                                        }

                                        if ($rawTimeIn && $rawTimeOut) {
                                            info('have both in out', []);
                                            $duration = (clone $targetDate)->second($timeIn->diffInSeconds($timeOut));

                                            $timeInDiff = $scheduleTimeIn->diffInSeconds($timeIn);
                                            $timeInDeviation = (clone $targetDate)->second($timeInDiff);
                                            $timeOutDiff = $scheduleTimeOut->diffInSeconds($timeOut);
                                            $timeOutDeviation = (clone $targetDate)->second($timeOutDiff);
                                            $durationDiff = $scheduleDuration->diffInSeconds($duration);
                                            $durationDeviation = (clone $targetDate)->second($durationDiff);

                                            $overtime = $this->calculateOvertime(false, $rawTimeSheet, $rawTimeIn->clock_time, $rawTimeOut->clock_time);
                                        }

                                        $this->timeSheetDao->save([
                                            'tenant_id' => $this->requester->getTenantId(),
                                            'company_id' => $this->requester->getCompanyId(),
                                            'employee_id' => $rawTimeSheet->employee_id,
                                            'date' => $rawTimeSheet->date,
                                            'is_work_day' => true,
                                            'is_flexy_hour' => $rawTimeSheet->is_flexy_hour,
                                            'schedule_time_in' => $scheduleTimeIn->toDateTimeString(),
                                            'time_in' => $timeIn ? $timeIn->toDateTimeString() : null,
                                            'time_in_deviation' => $timeInDeviation ? $timeInDeviation->toTimeString() : null,
                                            'schedule_time_out' => $scheduleTimeOut->toDateTimeString(),
                                            'time_out' => $timeOut ? $timeOut->toDateTimeString() : null,
                                            'time_out_deviation' => $timeOutDeviation ? $timeOutDeviation->toTimeString() : null,
                                            'schedule_duration' => $scheduleDuration->toTimeString(),
                                            'duration' => $duration ? $duration->toTimeString() : null,
                                            'duration_deviation' => $durationDeviation ? $durationDeviation->toTimeString() : null,
                                            'attendance_code' => $attendanceCode,
                                            'leave_code' => null,
                                            'leave_weight' => null,
                                            'value_1' => $value1,
                                            'value_2' => $value2,
                                            'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                            'overtime' => $overtime
                                        ]);
                                    }
                                }
                                $this->rawTimeSheetDao->update($rawTimeSheet->employee_id, $rawTimeSheet->date, ['is_analyzed' => true]);
                            }
                        }
                    } else {
                        info('dont have unp timesheet', []);
                        if ($timeGroup) {
                            info('have timegroup', []);
                            $logSchedule = $this->logScheduleDao->getOneByTargetDate(
                                $timeGroup->timeGroupCode,
                                $selectedTimeSheet['date']
                            );

                            if ($logSchedule) {
                                info('have schedule', []);
                                $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
                                $dateStart = Carbon::parse($logSchedule->dateStart);
                                $targetDate = Carbon::parse($selectedTimeSheet['date']);

                                if ($pattern->count() !== 0) {
                                    info('have pattern', []);

                                    $patternIdx = $targetDate->diffInDays($dateStart) % $pattern->count();
                                    $scheduleDetail = $pattern->slice($patternIdx, 1)->first();
//                                    info('adaLogPattern', (array)$scheduleDetail);

                                    $timeIn = null;
                                    $timeOut = null;
                                    $duration = null;
                                    $overtime = null;

                                    $holiday = $this->calendarDao->getOneHoliday($selectedTimeSheet['date']);

                                    if ($scheduleDetail->leaveCode) {
                                        info('off day', []);
                                        $leaveRequestDetail = $this->leaveRequestDetailDao->getOneByPersonLeaveCodeAndDate(
                                            $selectedTimeSheet['employeeId'],
                                            $scheduleDetail->leaveCode,
                                            $selectedTimeSheet['date']
                                        );
                                        $weight = $leaveRequestDetail ? $leaveRequestDetail->weight : null;
                                        $this->timeSheetDao->save([
                                            'tenant_id' => $this->requester->getTenantId(),
                                            'company_id' => $this->requester->getCompanyId(),
                                            'employee_id' => $selectedTimeSheet['employeeId'],
                                            'date' => $selectedTimeSheet['date'],
                                            'is_work_day' => false,
                                            'is_flexy_hour' => $timeGroup->isFlexyHour,
                                            'schedule_time_in' => null,
                                            'time_in' => $timeIn ? $timeIn->toDateTimeString() : null,
                                            'time_in_deviation' => null,
                                            'schedule_time_out' => null,
                                            'time_out' => $timeOut ? $timeOut->toDateTimeString() : null,
                                            'time_out_deviation' => null,
                                            'schedule_duration' => null,
                                            'duration' => $duration ? $duration->toTimeString() : null,
                                            'duration_deviation' => null,
                                            'attendance_code' => null,
                                            'leave_code' => $scheduleDetail->leaveCode,
                                            'leave_weight' => $weight,
                                            'value_1' => null,
                                            'value_2' => null,
                                            'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                            'overtime' => $overtime
                                        ]);
                                    } else {
                                        $travel = $this->travelRequestDao->getOneTravel($selectedTimeSheet['employeeId'], $selectedTimeSheet['date']);
                                        if ($travel) {
                                            info('have travel request', []);
                                            $this->timeSheetDao->save([
                                                'tenant_id' => $this->requester->getTenantId(),
                                                'company_id' => $this->requester->getCompanyId(),
                                                'employee_id' => $selectedTimeSheet['employeeId'],
                                                'date' => $selectedTimeSheet['date'],
                                                'is_work_day' => false,
                                                'is_flexy_hour' => $timeGroup->isFlexyHour,
                                                'schedule_time_in' => null,
                                                'time_in' => $timeIn ? $timeIn->toDateTimeString() : null,
                                                'time_in_deviation' => null,
                                                'schedule_time_out' => null,
                                                'time_out' => $timeOut ? $timeOut->toDateTimeString() : null,
                                                'time_out_deviation' => null,
                                                'schedule_duration' => null,
                                                'duration' => $duration ? $duration->toTimeString() : null,
                                                'duration_deviation' => null,
                                                'attendance_code' => null,
                                                'leave_code' => 'TRV',
                                                'leave_weight' => 1,
                                                'value_1' => null,
                                                'value_2' => null,
                                                'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                                'overtime' => $overtime
                                            ]);
                                        } else {
                                            $holiday = $this->calendarDao->getOneHoliday($selectedTimeSheet['date']);
                                            if ($holiday) {
                                                info('holiday', []);

                                                $this->timeSheetDao->save([
                                                    'tenant_id' => $this->requester->getTenantId(),
                                                    'company_id' => $this->requester->getCompanyId(),
                                                    'employee_id' => $selectedTimeSheet['employeeId'],
                                                    'date' => $selectedTimeSheet['date'],
                                                    'is_work_day' => false,
                                                    'is_flexy_hour' => $timeGroup->isFlexyHour,
                                                    'schedule_time_in' => null,
                                                    'time_in' => $timeIn ? $timeIn->toDateTimeString() : null,
                                                    'time_in_deviation' => null,
                                                    'schedule_time_out' => null,
                                                    'time_out' => $timeOut ? $timeOut->toDateTimeString() : null,
                                                    'time_out_deviation' => null,
                                                    'schedule_duration' => null,
                                                    'duration' => $duration ? $duration->toTimeString() : null,
                                                    'duration_deviation' => null,
                                                    'attendance_code' => null,
                                                    'leave_code' => $holiday->leaveCode,
                                                    'leave_weight' => 1,
                                                    'value_1' => null,
                                                    'value_2' => null,
                                                    'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                                    'overtime' => $overtime
                                                ]);
                                            } else {
                                                info('work day', []);
                                                $scheduleForDate = TimeGroupController::constructScheduleForDate($targetDate, $scheduleDetail);
                                                $scheduleTimeIn = Carbon::parse($scheduleForDate['timeIn']);
                                                $scheduleTimeOut = Carbon::parse($scheduleForDate['timeOut']);
                                                $scheduleDuration = (clone $targetDate)->second($scheduleForDate['durationInSeconds']);

                                                $leaveRequestDetail = $this->leaveRequestDetailDao->getOneByPersonAndDate(
                                                    $selectedTimeSheet['employeeId'],
                                                    $selectedTimeSheet['date']
                                                );
//                                    info('leaveRequestDetail',(array)$leaveRequestDetail);
                                                if ($leaveRequestDetail) {
                                                    $weight = $leaveRequestDetail ? $leaveRequestDetail->weight : null;
                                                    info('have leave request', []);

                                                    $this->timeSheetDao->save([
                                                        'tenant_id' => $this->requester->getTenantId(),
                                                        'company_id' => $this->requester->getCompanyId(),
                                                        'employee_id' => $selectedTimeSheet['employeeId'],
                                                        'date' => $selectedTimeSheet['date'],
                                                        'is_work_day' => true,
                                                        'is_flexy_hour' => $timeGroup->isFlexyHour,
                                                        'schedule_time_in' => $scheduleTimeIn->toDateTimeString(),
                                                        'time_in' => null,
                                                        'time_in_deviation' => null,
                                                        'schedule_time_out' => $scheduleTimeOut->toDateTimeString(),
                                                        'time_out' => null,
                                                        'time_out_deviation' => null,
                                                        'schedule_duration' => $scheduleDuration->toTimeString(),
                                                        'duration' => null,
                                                        'duration_deviation' => null,
                                                        'attendance_code' => null,
                                                        'leave_code' => $leaveRequestDetail->leaveCode,
                                                        'leave_weight' => $weight,
                                                        'value_1' => null,
                                                        'value_2' => null,
                                                        'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                                        'overtime' => $overtime
                                                    ]);
                                                } else {
                                                    info('dont have leave request', []);
                                                    $this->timeSheetDao->save([
                                                        'tenant_id' => $this->requester->getTenantId(),
                                                        'company_id' => $this->requester->getCompanyId(),
                                                        'employee_id' => $selectedTimeSheet['employeeId'],
                                                        'date' => $selectedTimeSheet['date'],
                                                        'is_work_day' => true,
                                                        'is_flexy_hour' => $timeGroup->isFlexyHour,
                                                        'schedule_time_in' => $scheduleTimeIn->toDateTimeString(),
                                                        'time_in' => null,
                                                        'time_in_deviation' => null,
                                                        'schedule_time_out' => $scheduleTimeOut->toDateTimeString(),
                                                        'time_out' => null,
                                                        'time_out_deviation' => null,
                                                        'schedule_duration' => $scheduleDuration->toTimeString(),
                                                        'duration' => null,
                                                        'duration_deviation' => null,
                                                        'attendance_code' => null,
                                                        'leave_code' => 'ABS',
                                                        'leave_weight' => 1,
                                                        'value_1' => null,
                                                        'value_2' => null,
                                                        'time_group_code' => $timeGroup ? $timeGroup->timeGroupCode : null,
                                                        'overtime' => $overtime
                                                    ]);
                                                    if ($timeGroup->isAbsenceAsAnnualLeave) {
                                                        $leaveRequest = [
                                                            'tenant_id' => $this->requester->getTenantId(),
                                                            'company_id' => $this->requester->getCompanyId(),
                                                            'employee_id' => $selectedTimeSheet['employeeId'],
                                                            'leave_code' => 'AL-LV',
                                                            'description' => 'Auto-generate because of absence',
                                                            'status' => 'A'
                                                        ];
                                                        $leaveRequestId = $this->leaveRequestDao->save($leaveRequest);

                                                        $leaveRequestDetail = [
                                                            'tenant_id' => $this->requester->getTenantId(),
                                                            'company_id' => $this->requester->getCompanyId(),
                                                            'leave_request_id' => $leaveRequestId,
                                                            'weight' => 1,
                                                            'status' => 'A',
                                                            'date' => $selectedTimeSheet['date']
                                                        ];
                                                        $this->leaveRequestDetailDao->save($leaveRequestDetail);
                                                    }
                                                }
                                            }
                                        }

                                    }
                                }
                            }

                        }
//
                    }

                }

            }

        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }

    public
    function getOneWorkSheet(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required',
            'date' => 'required'
        ]);

        $workSheet = $this->workSheetDao->getOne($request->employeeId, $request->date);

        return $this->renderResponse(new AppResponse($workSheet, trans('messages.dataRetrieved')));
    }

    public
    function getAllWorkSheetByPerson(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required'
        ]);

        $workSheets = $this->workSheetDao->getAllByPerson($request->employeeId);

        return $this->renderResponse(new AppResponse($workSheets, trans('messages.allDataRetrieved')));
    }

    public
    function getAllWorkSheetByPersonAndDate(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'employeeId' => 'required',
            'date' => 'required|date',
        ]);

        $workSheets = $this->workSheetDao->getAllByPersonAndDate($request->employeeId, $request->date);

        return $this->renderResponse(new AppResponse($workSheets, trans('messages.allDataRetrieved')));
    }

    public
    function saveWorkSheet(Request $request)
    {
        $data = array();
        $this->validate($request, [
            'companyId' => 'required|integer',
//            'employeeId' => 'required',
            'date' => 'required|date',
            'timeStart' => 'required|date|before_or_equal:timeEnd',
            'timeEnd' => 'required|date',
            'description' => 'max:255',
            'description2' => 'max:255'
        ]);

        DB::transaction(function () use (&$request, &$data) {
            $employeeId = null;
            if ($request->has('employeeId')) {
                $employeeId = $request->employeeId;
            } else if ($request->has('subEmployeeId')) {
                $employeeId = $request->subEmployeeId;
            }

            $attribute = [
                'tenant_id' => $this->requester->getTenantId(),
                'company_id' => $request->companyId,
                'employee_id' => $employeeId,
                'date' => $request->date,
                'time_start' => $request->timeStart,
                'time_end' => $request->timeEnd,
                'description' => $request->description ? $request->description : '-',
                'description_2' => $request->description2 ? $request->description2 : '-'
            ];

            if ($request->has('activityValue1')) {
                $attribute['activity_value_1'] = $request->activityValue1;
            }

            if ($request->has('activityValue2')) {
                $attribute['activity_value_2'] = $request->activityValue2;
            }

            if ($request->has('activityCode')) {
                $attribute['activity_code'] = $request->activityCode;
            }

            info('worksheet', [$attribute]);

            $data['id'] = $this->workSheetDao->save($attribute);
            $request->id = $data['id'];
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    public
    function updateWorkSheet(Request $request)
    {
        $data = array();
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required',
            'date' => 'required|date',
            'timeStart' => 'required|date|before_or_equal:timeEnd',
            'timeEnd' => 'required|date',
            'description' => 'required',
            'description2' => 'required'
        ]);

        DB::transaction(function () use (&$request, &$data) {
            $attribute = [
                'tenant_id' => $this->requester->getTenantId(),
                'company_id' => $request->companyId,
                'employee_id' => $request->employeeId,
                'date' => $request->date,
                'time_start' => $request->timeStart,
                'time_end' => $request->timeEnd,
                'description' => $request->description,
                'description_2' => $request->description2
            ];

            if ($request->has('activityValue1')) {
                $attribute['activity_value_1'] = $request->activityValue1;
            }

            if ($request->has('activityValue2')) {
                $attribute['activity_value_2'] = $request->activityValue2;
            }

            if ($request->has('activityCode')) {
                $attribute['activity_code'] = $request->activityCode;
            }

            $this->workSheetDao->update($request->id, $attribute);
        });

        $resp = new AppResponse($data, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    private
    function calculateOvertime($isLeave, $rawTimeSheet, $timeIn, $timeOut)
    {
        if (!$rawTimeSheet->is_allow_overtime || !$rawTimeSheet->overtime_time_start) {
            return null;
        }

        $targetDate = Carbon::parse($rawTimeSheet->date);
        $timeIn = Carbon::parse($timeIn);
        $timeOut = Carbon::parse($timeOut);

        if ($isLeave && $rawTimeSheet->is_flexy_holiday_overtime) {
            $overtimeDuration = (clone $targetDate)->second($timeIn->diffInSeconds($timeOut));
        } else {
            $scheduleOvertimeTimeStart = Carbon::parse($rawTimeSheet->overtime_time_start);
            $overtimeTimeStart = $timeIn->greaterThan($scheduleOvertimeTimeStart) ? $timeIn : $scheduleOvertimeTimeStart;
            if ($rawTimeSheet->overtime_time_end) {
                $scheduleOvertimeTimeEnd = Carbon::parse($rawTimeSheet->overtime_time_end);
                $overtimeTimeEnd = $timeOut->greaterThan($scheduleOvertimeTimeEnd) ? $scheduleOvertimeTimeEnd : $timeOut;
            } else {
                $overtimeTimeEnd = $timeOut;
            }
            $overtimeDuration = (clone $targetDate)->second($overtimeTimeStart->diffInSeconds($overtimeTimeEnd));
        }
        return $overtimeDuration->toTimeString();
    }

    private
    function getAttendanceCode(
        $rawTimeSheet,
        $timeIn,
        $timeOut,
        Carbon $scheduleTimeIn,
        Carbon $scheduleTimeOut,
        Carbon $scheduleDuration
    )
    {
        $attendanceCodes = [];
        if (!$timeIn) {
            array_push($attendanceCodes, '(NTI)'); // NO TIME IN
        } else {
            $timeIn = Carbon::parse($timeIn);
            $timeInDiff = $scheduleTimeIn->diffInSeconds($timeIn);
            if ($timeIn->greaterThan($scheduleTimeIn) && $timeInDiff > 0) {
                $isPermitted = $this->permissionRequestDao->isPermitted('PLAT', $rawTimeSheet->date, $rawTimeSheet->employee_id);
                if ($isPermitted) {
                    array_push($attendanceCodes, '(PLAT)'); // PERMITTED LATE
                } else {
                    array_push($attendanceCodes, '(LAT)'); // LATE
                }
            }
        }

        if (!$timeOut) {
            array_push($attendanceCodes, '(NTO)'); // NO TIME OUT
        } else {
            $timeOut = Carbon::parse($timeOut);
            $timeOutDiff = $scheduleTimeOut->diffInSeconds($timeOut);
            if ($timeOut->lessThan($scheduleTimeOut) && $timeOutDiff > 0) {
                $isPermitted = $this->permissionRequestDao->isPermitted('PEAO', $rawTimeSheet->date, $rawTimeSheet->employee_id);
                if ($isPermitted) {
                    array_push($attendanceCodes, '(PEAO)'); // PERMITTED EARLY OUT
                } else {
                    array_push($attendanceCodes, '(EAO)'); // EARLY OUT
                }
            }
        }

        if ($timeIn && $timeOut) {
            $duration = Carbon::parse($rawTimeSheet->date)->second($timeIn->diffInSeconds($timeOut));
            $durationDiff = $scheduleDuration->diffInSeconds($duration);
            if ($timeIn->lessThanOrEqualTo($scheduleTimeIn) && $timeOut->greaterThanOrEqualTo($scheduleTimeOut)) {
                array_push($attendanceCodes, '(ONT)'); // ON TIME
            } else if ($rawTimeSheet->is_flexy_hour) {
                if ($durationDiff <= 660) {
                    array_push($attendanceCodes, '(ONT)'); // ON TIME
                } else if ($duration->lessThan($scheduleDuration) && $durationDiff > 660) {
                    array_push($attendanceCodes, '(LDR)'); // LESS DURATION
                }
            } else {
                if ($timeInDiff === 0 && $timeOutDiff === 0) {
                    array_push($attendanceCodes, '(ONT)'); // ON TIME
                }
            }
        }

        if (!$timeIn && !$timeOut) {
            return implode(',', $attendanceCodes);
        }

        info('attendance status', [$attendanceCodes]);
        return implode(',', $attendanceCodes);
    }

    private
    function checkRawTimeSheetRequest(Request $request)
    {
        $this->validate($request, [
            // TODO: companyId should exist in companies table
            'companyId' => 'required|integer',
//            'employeeId' => 'required',
            'date' => 'required|date',
            'type' => 'nullable|max:1',
            'locationCode' => 'nullable|max:10',
            'clockTime' => 'nullable|present|date'
        ]);
    }

    private
    function constructRawTimeSheet(Request $request)
    {
        $employeeId = null;
        if ($request->has('employeeId')) {
            $employeeId = $request->employeeId;
        } else if ($request->has('subEmployeeId')) {
            $employeeId = $request->subEmployeeId;
        }

        $rawTimeSheet = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'employee_id' => $employeeId,
            'date' => $request->date,
            'type' => $request->type,
            'clock_time' => $request->clockTime ? $request->clockTime : null,
            'is_analyzed' => false
        ];
        if ($request->has('worksheetId')) {
            $rawTimeSheet['worksheet_id'] = $request->worksheetId;
            $rawTimeSheet['clock_time_lat'] = $request->clockTimeLat;
            $rawTimeSheet['clock_time_long'] = $request->clockTimeLong;
            $rawTimeSheet['project_code'] = $request->projectCode;
        }
        return $rawTimeSheet;
    }


    private
    function get_convert_time($date_time)
    {
        return date('H:i', strtotime($date_time));
    }

    private
    function get_deviation_time($date_time_in, $date_time_out)
    {
        $dt_in = new \Datetime($date_time_in);
        $dt_out = new \Datetime($date_time_out);
        $time_diff = $dt_in->diff($dt_out);
        return $time_diff->format('%H:%i');
    }

    public
    function downloadAllReport(Request $request)
    {
        set_time_limit(1800);
        \Log::info('TimeSheetController:downloadAllReport');
        \Log::info($request);

        // ===========
        header('Cache-Control: no-cache');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="ExportScan.xlsx"');
        header('Cache-Control: max-age=0');

        // STYLE
        $styleHeaderReport = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $styleHeaderTable = [
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                // 'rotation' => 90,
                'type' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '808080'],
                'endColor' => ['argb' => '808080'],
            ],
            'borders' => [
                'diagonaldirection' => Borders::DIAGONAL_BOTH,
                'allborders' => [
                    'style' => Border::BORDER_THIN,
                ],
                'color' => ['argb' => '000000'],
            ],
        ];
        $styleStandardReport = [
            'font' => [
                'bold' => false,
                'size' => 10,
            ],
        ];

        $spreadsheet = new Spreadsheet();

        /* META DATA */
        $spreadsheet->getProperties()
            ->setCreator("LinovHR3")
            ->setLastModifiedBy("LinovHR3")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription(
                "Test document for Office 2007 XLSX, generated using PHP classes."
            )
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $i = 2;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A' . $i, 'From Date');
        $sheet->setCellValue('A' . ($i + 2), 'No');
        $sheet->setCellValue('B' . ($i + 2), 'Date');
        $sheet->setCellValue('C' . ($i + 2), 'Employee');
        $sheet->setCellValue('C' . ($i + 3), 'ID');
        $sheet->setCellValue('D' . ($i + 3), 'Name');
        $sheet->setCellValue('E' . ($i + 2), 'Day Off');
        $sheet->setCellValue('F' . ($i + 2), 'Flexy Hour');
        $sheet->setCellValue('F' . $i, 'ABSENCE AND ATTENDANCE LIST');
        $sheet->setCellValue('G' . ($i + 2), 'Time In');
        $sheet->setCellValue('G' . ($i + 3), 'Schedule');
        $sheet->setCellValue('H' . ($i + 3), 'Actual');
        $sheet->setCellValue('I' . ($i + 3), 'Deviation');
        $sheet->setCellValue('J' . ($i + 2), 'Time Out');
        $sheet->setCellValue('J' . ($i + 3), 'Schedule');
        $sheet->setCellValue('K' . ($i + 3), 'Actual');
        $sheet->setCellValue('L' . ($i + 3), 'Deviation');
        $sheet->setCellValue('M' . ($i + 2), 'Duration');
        $sheet->setCellValue('M' . ($i + 3), 'Schedule');
        $sheet->setCellValue('N' . ($i + 3), 'Actual');
        $sheet->setCellValue('O' . ($i + 3), 'Deviation');
        $sheet->setCellValue('P' . ($i + 2), 'Attendance');
        $sheet->setCellValue('Q' . ($i + 2), 'Leave');
        $sheet->setCellValue('Q' . ($i + 3), 'Status');
        $sheet->setCellValue('R' . ($i + 3), 'Half/Full');
        $sheet->setCellValue('S' . ($i + 2), 'Overtime');

        // Merge header
        $spreadsheet->getActiveSheet()
            ->mergeCells('A' . ($i + 2) . ':A' . ($i + 3))
            ->mergeCells('B' . $i . ':C' . $i)
            ->mergeCells('B' . ($i + 2) . ':B' . ($i + 3))
            ->mergeCells('C' . ($i + 2) . ':D' . ($i + 2))
            ->mergeCells('E' . ($i + 2) . ':E' . ($i + 3))
            ->mergeCells('F' . ($i + 2) . ':F' . ($i + 3))
            ->mergeCells('F' . $i . ':J' . $i)
            ->mergeCells('G' . ($i + 2) . ':I' . ($i + 2))
            ->mergeCells('J' . ($i + 2) . ':L' . ($i + 2))
            ->mergeCells('M' . ($i + 2) . ':O' . ($i + 2))
            ->mergeCells('P' . ($i + 2) . ':P' . ($i + 3))
            ->mergeCells('Q' . ($i + 2) . ':R' . ($i + 2))
            ->mergeCells('S' . ($i + 2) . ':S' . ($i + 3));
        // Styling
        $spreadsheet->getActiveSheet()->getStyle('F' . $i)->applyFromArray($styleHeaderReport);
        $spreadsheet->getActiveSheet()->getStyle('A' . ($i + 2) . ':S' . ($i + 3))->applyFromArray($styleHeaderTable);
        $spreadsheet->getActiveSheet()->getStyle('A' . ($i + 2) . ':S' . ($i + 3))->getAlignment()->setHorizontal('center');
        // Coloring


        // PROCESSING DATA //
        $this->validate($request, [
            'companyId' => 'required',
            'startDate' => 'nullable',
            'endDate' => 'nullable',
            'menuCode' => 'required',
            'criteria' => 'nullable|present|array',
            'criteria.*.field' => 'nullable|min:1',
            'criteria.*.val' => '',
            'personCriteria' => 'nullable|present|array',
            'personCriteria.*.field' => 'nullable|min:1|max:4',
            'personCriteria.*.conj' => 'nullable|min:1|max:3',
            'personCriteria.*.val' => '',
            'pageInfo' => 'required'
        ]);
        $request->merge((array)$request->pageInfo);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
            'order' => 'nullable|present|string',
            'orderDirection' => 'nullable|present|in:asc,desc',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit = PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);
        $order = PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $employeeIds = [];
        if ($request->personCriteria) {
            $result = $this->externalCoreController->advancedSearchPerson(
                $this->requester->getCompanyId(),
                $request->menuCode,
                ['PI1'],
                $request->personCriteria,
                [
                    'pageLimit' => null,
                    'pageNo' => null,
                    'order' => null,
                    'orderDirection' => null
                ],
                $request->applicationId
            );
            $employeeIds = array_map(function ($person) {
                return $person['employeeId'];
            }, $result);
        }

        $startDate = $request->has('startDate') ? $request->startDate : 'null';
        $endDate = $request->has('endDate') ? $request->endDate : 'null';

        $data = $this->timeSheetDao->getReportTimesheets(
            $startDate,
            $endDate,
            $request->criteria ? $request->criteria : [],
            $employeeIds,
            $offset,
            $limit,
            $order,
            $orderDirection
        );
        $timeSheets = $data[0];
        // PROCESSING DATA //

        if ($startDate != 'null') {
            $sheet->setCellValue('B' . $i, date('Y-m-d', strtotime($startDate)) . ' s/d ' . date('Y-m-d', strtotime($endDate)));
        }

        if (!$timeSheets)
            return 0;

        $i = 6;
        \Log::info('Before Foreach TimeSheets(' . count($timeSheets) . ' rows)');
        foreach ($timeSheets as $value) {
            \Log::info('Row No.' . ($i - 5));

            $employee = $this->personDao->getOneEmployee($value->employeeId);
            $leave = $this->leaveDao->getOne($value->leaveCode);
            $attendance = $this->attendanceDao->getOne($value->attendanceCode);

            $sheet->setCellValue('A' . $i, ($i - 5));
            $sheet->setCellValue('B' . $i, $value->date);
            $sheet->setCellValue('C' . $i, $value->employeeId);
            $sheet->setCellValue('D' . $i, (($employee) ? $employee->firstName : '') . ' ' . (($employee) ? $employee->lastName : ''));
            $sheet->setCellValue('E' . $i, ($value->isWorkDay) ? 'Yes' : 'No');
            $sheet->setCellValue('F' . $i, ($value->isFlexyHour) ? 'Yes' : 'No');
            $sheet->setCellValue('G' . $i, ($value->scheduleTimeIn) ? $this->get_convert_time($value->scheduleTimeIn) : '');
            $sheet->setCellValue('H' . $i, ($value->timeIn) ? $this->get_convert_time($value->timeIn) : '');
            $sheet->setCellValue('I' . $i, ($value->timeInDeviation) ? $this->get_deviation_time($value->scheduleTimeIn, $value->timeIn) : '');
            $sheet->setCellValue('J' . $i, ($value->scheduleTimeOut) ? $this->get_convert_time($value->scheduleTimeOut) : '');
            $sheet->setCellValue('K' . $i, ($value->timeOut) ? $this->get_convert_time($value->timeOut) : '');
            $sheet->setCellValue('L' . $i, ($value->timeOutDeviation) ? $this->get_deviation_time($value->scheduleTimeOut, $value->timeOut) : '');
            $sheet->setCellValue('M' . $i, ($value->scheduleDuration) ? $this->get_convert_time($value->scheduleDuration) : '');
            $sheet->setCellValue('N' . $i, ($value->duration) ? $this->get_convert_time($value->duration) : '');
            $sheet->setCellValue('O' . $i, ($value->durationDeviation) ? $this->get_deviation_time($value->scheduleDuration, $value->duration) : '');
            $sheet->setCellValue('P' . $i, ($attendance) ? $attendance->name : '');
            $sheet->setCellValue('Q' . $i, ($leave) ? $leave->name : '');
            $sheet->setCellValue('R' . $i, ($value->leaveWeight) ? $value->leaveWeight : '');
            $sheet->setCellValue('S' . $i, ($value->overtime) ? $value->overtime : '');
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        // $writer = IOFactory::createWriter($spreadsheet, "Mpdf");
        // $writer = IOFactory::createWriter($spreadsheet, "xlsx");
        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        \Log::info('END of TimeSheetController:downloadAllReport');
        return ($response);
    }
}
