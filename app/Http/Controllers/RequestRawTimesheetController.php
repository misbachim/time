<?php

namespace App\Http\Controllers;

use App\Business\Dao\Core\CompanyDao;
use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\PositionDao;
use App\Business\Dao\Core\ProjectDao;
use App\Business\Dao\Core\UnitDao;
use App\Business\Dao\Core\WorklistDao;
use App\Business\Dao\RawTimeSheetDao;
use App\Business\Dao\RequestRawTimeSheetDao;
use App\Business\Dao\WorkSheetActivityDao;
use App\Business\Dao\WorkSheetDao;
use App\Business\Model\AppResponse;
use App\Business\Model\PagingAppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class for handling Request Raw Timesheet process
 */
class RequestRawTimesheetController extends Controller
{
    public function __construct(
        Requester $requester,
        RequestRawTimeSheetDao $requestRawTimeSheetDao,
        RawTimeSheetDao $rawTimeSheetDao,
        WorkSheetDao $workSheetDao,
        WorkSheetActivityDao $workSheetActivityDao,
        WorklistDao $worklistDao,
        ProjectDao $projectDao,
        PositionDao $positionDao,
        UnitDao $unitDao,
        CompanyDao $companyDao,
        PersonDao $personDao
    )
    {
        parent::__construct();

        $this->requester = $requester;
        $this->requestRawTimeSheetDao = $requestRawTimeSheetDao;
        $this->rawTimeSheetDao = $rawTimeSheetDao;
        $this->workSheetDao = $workSheetDao;
        $this->workSheetActivityDao = $workSheetActivityDao;
        $this->worklistDao = $worklistDao;
        $this->projectDao = $projectDao;
        $this->positionDao = $positionDao;
        $this->unitDao = $unitDao;
        $this->companyDao = $companyDao;
        $this->personDao = $personDao;
    }

    /**
     * Get all request raw timesheet
     * @param request
     */
    public function getAll(Request $request)
    {
        if ($request->has('admin')) {
            $this->validate($request, [
                "companyId" => "required|numeric",
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
            $requestTimesheet = $this->requestRawTimeSheetDao->getAll(
                $offset,
                $limit
            );
        } else {
            $this->validate($request, [
                'companyId' => 'required',
                'employeeId' => 'required'
            ]);
            $requestTimesheet = $this->requestRawTimeSheetDao->getLatestFive($request->employeeId);
        }

        if ($requestTimesheet) {
            for ($i = 0; $i < count($requestTimesheet); $i++) {
                $requestTimesheet[$i]->projectName = $requestTimesheet[$i]->projectDescription = $requestTimesheet[$i]->activityName = $requestTimesheet[$i]->activityDescription = null;
                if ($requestTimesheet[$i]->projectCode) {
                    $project = $this->projectDao->getOneByCode($requestTimesheet[$i]->projectCode);
                    if ($project) {
                        $requestTimesheet[$i]->projectName = $project->name;
                        $requestTimesheet[$i]->projectDescription = $project->description;
                    }
                }
                if ($requestTimesheet[$i]->activityCode) {
                    $worksheetActivity = $this->workSheetActivityDao->getOneByCode($requestTimesheet[$i]->activityCode);
                    if ($worksheetActivity) {
                        $requestTimesheet[$i]->activityName = $worksheetActivity->name;
                        $requestTimesheet[$i]->activityDescription = $worksheetActivity->description;
                    }
                }

                if ($request->has('admin')) {
                    $person = $this->personDao->getOneEmployee($requestTimesheet[$i]->employeeId);
                    if ($person) {
                        $requestTimesheet[$i]->person = $person;
                    } else {
                        $requestTimesheet[$i]->person = null;
                    }

                }
            }
        }

        if ($request->has('admin')) {
            $requestForCount = $this->requestRawTimeSheetDao->getCount($this->requester->getCompanyId());
            $count = count($requestForCount);
            return $this->renderResponse(new PagingAppResponse($requestTimesheet, trans('messages.allDataRetrieved'), $limit, $count, $pageNo));
        } else {
            $response = new AppResponse($requestTimesheet, trans('messages.allDataRetrieved'));
            return $this->renderResponse($response);
        }
    }

    /**
     * Get all request raw timesheet by employee id
     * @param request
     */
    public function getHistory(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
            'employeeId' => 'required',
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
        $requestTimesheet = $this->requestRawTimeSheetDao->getAllByEmployeeId(
            $offset,
            $limit,
            $request->employeeId
        );

        if ($requestTimesheet) {
            for ($i = 0; $i < count($requestTimesheet); $i++) {
                $requestTimesheet[$i]->projectName = $requestTimesheet[$i]->projectDescription = $requestTimesheet[$i]->activityName = $requestTimesheet[$i]->activityDescription = null;
                if ($requestTimesheet[$i]->projectCode) {
                    $project = $this->projectDao->getOneByCode($requestTimesheet[$i]->projectCode);
                    if ($project) {
                        $requestTimesheet[$i]->projectName = $project->name;
                        $requestTimesheet[$i]->projectDescription = $project->description;
                    }
                }
                if ($requestTimesheet[$i]->activityCode) {
                    $worksheetActivity = $this->workSheetActivityDao->getOneByCode($requestTimesheet[$i]->activityCode);
                    if ($worksheetActivity) {
                        $requestTimesheet[$i]->activityName = $worksheetActivity->name;
                        $requestTimesheet[$i]->activityDescription = $worksheetActivity->description;
                    }
                }
            }
        }

        $requestForCount = $this->requestRawTimeSheetDao->getCountEmployeeId($this->requester->getCompanyId(), $request->employeeId, null, null);
        $count = count($requestForCount);
        return $this->renderResponse(new PagingAppResponse($requestTimesheet, trans('messages.allDataRetrieved'), $limit, $count, $pageNo));
    }

    /**
     * Search request raw timesheet
     * @param request
     */
    public function search(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
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

        $person = $this->personDao->searchEmployees($request->search);
        $unit = $this->unitDao->searchUnit($request->search);
        $position = $this->positionDao->searchPosition($request->search);
        $project = $this->projectDao->searchProject($request->search);
        $requestTimesheet = [];

        if (count($person) > 0) {
            $empIds = array();
            for ($i = 0; $i < count($person); $i++) {
                info('persEmpId', [$person[$i]->employeeId]);
                array_push($empIds, $person[$i]->employeeId);
            }
            info('$empIds', [$empIds]);
            $requestTimesheet = $this->requestRawTimeSheetDao->searchEmpIds(
                $request->companyId,
                $offset,
                $limit,
                $empIds,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $requestForCount = $this->requestRawTimeSheetDao->getCountSearchEmpIds($request->companyId, $empIds, $request->status, $request->dateStart, $request->dateEnd);
        }

        if (count($unit) > 0 && count($requestTimesheet) === 0) {
            $empIds = array();
            for ($i = 0; $i < count($unit); $i++) {
                info('uniEmpId', [$unit[$i]->employeeId]);
                array_push($empIds, $unit[$i]->employeeId);
            }
            info('$empIds', [$empIds]);
            $requestTimesheet = $this->requestRawTimeSheetDao->searchEmpIds(
                $request->companyId,
                $offset,
                $limit,
                $empIds,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $requestForCount = $this->requestRawTimeSheetDao->getCountSearchEmpIds($request->companyId, $empIds, $request->status, $request->dateStart, $request->dateEnd);

        }

        if (count($position) > 0 && count($requestTimesheet) === 0) {
            $empIds = array();
            for ($i = 0; $i < count($position); $i++) {
                info('posEmpId', [$position[$i]->employeeId]);
                array_push($empIds, $position[$i]->employeeId);
            }
            info('$empIds', [$empIds]);
            $requestTimesheet = $this->requestRawTimeSheetDao->searchEmpIds(
                $request->companyId,
                $offset,
                $limit,
                $empIds,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $requestForCount = $this->requestRawTimeSheetDao->getCountSearchEmpIds($request->companyId, $empIds, $request->status, $request->dateStart, $request->dateEnd);

        }

        if (count($project) > 0 && count($requestTimesheet) === 0) {
            info('$project->code', [$project->code]);
            $requestTimesheet = $this->requestRawTimeSheetDao->search(
                $request->companyId,
                $offset,
                $limit,
                $project->code,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $requestForCount = $this->requestRawTimeSheetDao->getCountProject($request->companyId, $project->code, $request->status, $request->dateStart, $request->dateEnd);
        }

        if (count($requestTimesheet) === 0) {
            info('$request->search', [$request->search]);
            $requestTimesheet = $this->requestRawTimeSheetDao->search(
                $request->companyId,
                $offset,
                $limit,
                $request->search,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $requestForCount = $this->requestRawTimeSheetDao->getCountSearch($request->companyId, $request->search, $request->status, $request->dateStart, $request->dateEnd);
        }

        if ($requestTimesheet) {
            for ($i = 0; $i < count($requestTimesheet); $i++) {
                $requestTimesheet[$i]->projectName = $requestTimesheet[$i]->projectDescription = null;
                if ($requestTimesheet[$i]->projectCode) {
                    $project = $this->projectDao->getOneByCode($requestTimesheet[$i]->projectCode);
                    if ($project) {
                        $requestTimesheet[$i]->projectName = $project->name;
                        $requestTimesheet[$i]->projectDescription = $project->description;
                    }
                }
                $person = $this->personDao->getOneEmployee($requestTimesheet[$i]->employeeId);
                if ($person) {
                    $requestTimesheet[$i]->person = $person;
                } else {
                    $requestTimesheet[$i]->person = null;
                }
            }
        }

        $count = count($requestForCount);
        info('$count', [$count]);

        return $this->renderResponse(new PagingAppResponse($requestTimesheet, trans('messages.allDataRetrieved'), $limit, $count, $pageNo));
    }

    public
    function getOne(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'id' => 'required|integer'
        ]);

        $requestTimesheet = $this->requestRawTimeSheetDao->getOne($request->id);

        if (count($requestTimesheet) > 0) {
//            info('rts', [$requestTimesheet]);
            $person = $this->personDao->getOneEmployee($requestTimesheet->employeeId);
            if ($person) {
                $requestTimesheet->person = $person;
            } else {
                $requestTimesheet->person = null;
            }
            $requestTimesheet->timeStart = null;
            $requestTimesheet->timeEnd = null;
            if ($requestTimesheet->timeIn) {
                $requestTimesheet->timeStart = Carbon::parse($requestTimesheet->timeIn)->format('H:i');
            }
            if ($requestTimesheet->timeOut) {
                $requestTimesheet->timeEnd = Carbon::parse($requestTimesheet->timeOut)->format('H:i');
            }
            $requestTimesheet->requestDate = $requestTimesheet->date;

            if ($requestTimesheet->projectCode) {
                $project = $this->projectDao->getOneByCode($requestTimesheet->projectCode);
                if ($project) {
                    $requestTimesheet->projectName = $project->name;
                    $requestTimesheet->projectDescription = $project->description;
                }
            }
            if ($requestTimesheet->activityCode) {
                $worksheetActivity = $this->workSheetActivityDao->getOneByCode($requestTimesheet->activityCode);
                if ($worksheetActivity) {
                    $requestTimesheet->activityName = $worksheetActivity->name;
                    $requestTimesheet->activityDescription = $worksheetActivity->description;
                }
            }
        }

        return $this->renderResponse(new AppResponse($requestTimesheet, trans('messages.dataRetrieved')));
    }

    public
    function save(Request $request)
    {
        $data = array();

        $this->validate($request, [
            'companyId' => 'required|integer',
            'timeInLat' => 'required|numeric',
            'timeInLong' => 'required|numeric',
            'employeeId' => 'required',
            'date' => 'required|date',
            'timeIn' => 'nullable|date',
            'projectCode' => 'nullable|max:20'
        ]);

        DB::transaction(function () use (&$request, &$data) {
            if ($request->has('timezone')) {
                info('employee id', [$request->employeeId]);
                info('timezone in', [$request->timezone]);
                if (strpos($request->timezone, '+') !== false) {
                    $timezone = explode('+', $request->timezone);
                    $plus = explode(':', $timezone[1]);
                    $timeIn = Carbon::now()->addHours($plus[0]);
                } else if (strpos($request->timezone, '-') !== false) {
                    $timezone = explode('-', $request->timezone);
                    $minus = explode(':', $timezone[1]);
                    $timeIn = Carbon::now()->subHours($minus[0]);
                } else {
                    $timeIn = Carbon::now()->addHours(7);
                }
            } else {
                $timeIn = $request->timeIn;
            }

            $requestTimesheet = [
                'tenant_id' => $this->requester->getTenantId(),
                'company_id' => $this->requester->getCompanyId(),
                'date' => $request->date,
                'time_in' => $timeIn,
                'time_in_lat' => $request->timeInLat,
                'time_in_long' => $request->timeInLong,
                'time_out_lat' => 0,
                'time_out_long' => 0,
                'project_code' => $request->projectCode,
                'employee_id' => $request->employeeId,
                'time_out' => null,
                'value_1' => null,
                'value_2' => null,
                'description' => null,
                'description_2' => null,
                'activity_code' => null,
                'status' => 'P'
            ];
            // info('requestTimesheet', [$requestTimesheet]);
            $data['id'] = $this->requestRawTimeSheetDao->save($requestTimesheet);
        });

        return $this->renderResponse(new AppResponse($data, trans('messages.dataSaved')));
    }

    public
    function updateValue(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'timeIn' => 'nullable|date',
            'timeOut' => 'nullable|date',
            'employeeId' => 'required',
            'activityCode' => 'nullable|max:20',
            'projectCode' => 'nullable|max:20',
            'value1' => 'nullable|integer',
            'value2' => 'nullable|integer'
        ]);

        DB::transaction(function () use (&$request) {
            $requestTimesheet = [
                'activity_code' => $request->activityCode,
                'project_code' => $request->projectCode,
                'value_1' => $request->value1,
                'value_2' => $request->value2
            ];

            $this->requestRawTimeSheetDao->update($request->id, $requestTimesheet);

            $attribute = [
                'activity_code' => $request->activityCode,
                'activity_value_1' => $request->value1,
                'activity_value_2' => $request->value2
            ];

            $this->workSheetDao->updateByEmployeeAndTime($request->employeeId, $request->timeIn, $request->timeOut, $attribute);

            $rawTimesheet=[
                'project_code' => $request->projectCode,
            ];

            $this->rawTimeSheetDao->updateByEmployeeAndTime($request->employeeId, $request->timeIn, $rawTimesheet);
            $this->rawTimeSheetDao->updateByEmployeeAndTime($request->employeeId, $request->timeOut, $rawTimesheet);

        });

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    public
    function update(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        DB::transaction(function () use (&$request) {
            if ($request->has('status')) {
                $requestTimesheet = [
                    'status' => $request->status
                ];
                $this->requestRawTimeSheetDao->update($request->id, $requestTimesheet);

                if ($request->has('worklistAnswer')) {
                    $worklistData = [
                        'is_active' => false,
                    ];

                    $worklistData['answer'] = $request->worklistAnswer;
                    $worklistData['notes'] = $request->worklistNotes;
                    // update table hr_core -> worklists
                    $this->worklistDao->updateByLovWftyAndRequestId('ATTD', $request->id, $worklistData);
                    if ($request->worklistAnswer === 'AA') {
                        $requestTimesheet = [
                            'value_1' => $request->value1,
                            'value_2' => $request->value2,
                            'activity_code' => $request->activityCode,
                            'project_code' => $request->projectCode
                        ];
                        $this->requestRawTimeSheetDao->update($request->id, $requestTimesheet);

                        $requestRawTimesheet = $this->requestRawTimeSheetDao->getOne($request->id);
                        $attribute = [
                            'tenant_id' => $this->requester->getTenantId(),
                            'company_id' => $this->requester->getCompanyId(),
                            'employee_id' => $requestRawTimesheet->employeeId,
                            'date' => $requestRawTimesheet->date,
                            'time_start' => $requestRawTimesheet->timeIn,
                            'time_end' => $requestRawTimesheet->timeOut,
                            'description' => $requestRawTimesheet->description ? $requestRawTimesheet->description : '-',
                            'description_2' => $requestRawTimesheet->description2 ? $requestRawTimesheet->description2 : '-',
                            'activity_value_1' => $requestRawTimesheet->value1 ? $requestRawTimesheet->value1 : 0,
                            'activity_value_2' => $requestRawTimesheet->value2 ? $requestRawTimesheet->value2 : 0,
                            'activity_code' => $requestRawTimesheet->activityCode ? $requestRawTimesheet->activityCode : null
                        ];
                        $id = $this->workSheetDao->save($attribute);
                        $rawTimeSheetIn = [
                            'tenant_id' => $this->requester->getTenantId(),
                            'company_id' => $this->requester->getCompanyId(),
                            'employee_id' => $requestRawTimesheet->employeeId,
                            'date' => $requestRawTimesheet->date,
                            'type' => 'I',
                            'clock_time' => $requestRawTimesheet->timeIn ? $requestRawTimesheet->timeIn : null,
                            'is_analyzed' => false,
                            'worksheet_id' => $id,
                            'project_code' => $requestRawTimesheet->projectCode,
                            'clock_time_lat' => $requestRawTimesheet->timeInLat,
                            'clock_time_long' => $requestRawTimesheet->timeInLong
                        ];
                        $this->rawTimeSheetDao->save($rawTimeSheetIn);
                        $rawTimeSheetOut = [
                            'tenant_id' => $this->requester->getTenantId(),
                            'company_id' => $this->requester->getCompanyId(),
                            'employee_id' => $requestRawTimesheet->employeeId,
                            'date' => $requestRawTimesheet->date,
                            'type' => 'O',
                            'clock_time' => $requestRawTimesheet->timeOut ? $requestRawTimesheet->timeOut : null,
                            'is_analyzed' => false,
                            'worksheet_id' => $id,
                            'project_code' => $requestRawTimesheet->projectCode,
                            'clock_time_lat' => $requestRawTimesheet->timeOutLat,
                            'clock_time_long' => $requestRawTimesheet->timeOutLong
                        ];
                        $this->rawTimeSheetDao->save($rawTimeSheetOut);
                    }
                }
            } else {
                $this->validate($request, [
                    'timeOutLat' => 'required|numeric',
                    'timeOutLong' => 'required|numeric',
                    'timeOut' => 'nullable|date',
                    'value1' => 'nullable|integer',
                    'value2' => 'nullable|integer',
                    'activityCode' => 'required|max:20',
                    'description' => 'nullable|max:255',
                    'description2' => 'nullable|max:255'
                ]);

                $minValue1 = $maxValue1 = $minValue2 = $maxValue2 = null;
                $maxVal1 = $this->companyDao->getOneCompanySettingsByCode('AMA1');
                $minVal1 = $this->companyDao->getOneCompanySettingsByCode('AMI1');
                $maxVal2 = $this->companyDao->getOneCompanySettingsByCode('AMA2');
                $minVal2 = $this->companyDao->getOneCompanySettingsByCode('AMI2');

                if ($maxVal1) {
                    $maxValue1 = $maxVal1->fixValue;
                }
                if ($minVal1) {
                    $minValue1 = $minVal1->fixValue;
                }
                if ($maxVal2) {
                    $maxValue2 = $maxVal2->fixValue;
                }
                if ($minVal2) {
                    $minValue2 = $minVal2->fixValue;
                }

                if ($maxValue1 || $minValue1 || $maxValue2 || $minValue2) {
                    if ($request->value1 != 0) {
                        if ($request->value1 > $maxValue1) {
                            throw new AppException(trans('messages.invalidMaxValue') . $maxValue1);
                        }
                        if ($request->value1 < $minValue1) {
                            throw new AppException(trans('messages.invalidMinValue') . $minValue1);
                        }
                    }

                    if ($request->value2 != 0) {
                        if ($request->value2 > $maxValue2) {
                            throw new AppException(trans('messages.invalidMaxValue') . $maxValue2);
                        }
                        if ($request->value2 < $minValue2) {
                            throw new AppException(trans('messages.invalidMinValue') . $minValue2);
                        }
                    }
                }

                if ($request->has('timezone')) {
                    info('employee id', [$request->employeeId]);
                    info('timezone out', [$request->timezone]);
                    if (strpos($request->timezone, '+') !== false) {
                        $timezone = explode('+', $request->timezone);
                        $plus = explode(':', $timezone[1]);
                        $timeOut = Carbon::now()->addHours($plus[0]);
                    } else if (strpos($request->timezone, '-') !== false) {
                        $timezone = explode('-', $request->timezone);
                        $minus = explode(':', $timezone[1]);
                        $timeOut = Carbon::now()->subHours($minus[0]);
                    } else {
                        $timeOut = Carbon::now()->addHours(7);
                    }
                } else {
                    $timeOut = $request->timeOut;
                }

                $requestTimesheet = [
                    'time_out_lat' => $request->timeOutLat,
                    'time_out_long' => $request->timeOutLong,
                    'time_out' => $timeOut,
                    'value_1' => $request->value1,
                    'value_2' => $request->value2,
                    'activity_code' => $request->activityCode,
                    'description' => $request->description,
                    'description_2' => $request->description2,
                ];

                if ($request->has('projectCode')) {
                    $requestTimesheet['project_code'] = $request->projectCode;
                }

                $this->requestRawTimeSheetDao->update($request->id, $requestTimesheet);
            }
            // info('requestTimesheet', [$requestTimesheet]);

        });

        if ($request->has('generateWorklist')) {
            $requestRawTimesheet = $this->requestRawTimeSheetDao->getOne($request->id);
            if ($requestRawTimesheet->projectCode != null) {
                $externalCoreController->generateWorklist($requestRawTimesheet, $request->applicationId);
            } else {
                //todo get status from setting auto approve/reject
                $status = 'A';
                $requestTimesheet = [
                    'status' => $status
                ];
                $this->requestRawTimeSheetDao->update($request->id, $requestTimesheet);
                // info('$requestRawTimesheet', [$requestRawTimesheet]);
                if ($status === 'A') {
                    $attribute = [
                        'tenant_id' => $this->requester->getTenantId(),
                        'company_id' => $this->requester->getCompanyId(),
                        'employee_id' => $requestRawTimesheet->employeeId,
                        'date' => $requestRawTimesheet->date,
                        'time_start' => $requestRawTimesheet->timeIn,
                        'time_end' => $requestRawTimesheet->timeOut,
                        'description' => $requestRawTimesheet->description ? $requestRawTimesheet->description : '-',
                        'description_2' => $requestRawTimesheet->description2 ? $requestRawTimesheet->description2 : '-',
                        'activity_value_1' => $requestRawTimesheet->value1 ? $requestRawTimesheet->value1 : 0,
                        'activity_value_2' => $requestRawTimesheet->value2 ? $requestRawTimesheet->value2 : 0,
                        'activity_code' => $requestRawTimesheet->activityCode ? $requestRawTimesheet->activityCode : null
                    ];
                    $id = $this->workSheetDao->save($attribute);
                    $rawTimeSheetIn = [
                        'tenant_id' => $this->requester->getTenantId(),
                        'company_id' => $this->requester->getCompanyId(),
                        'employee_id' => $requestRawTimesheet->employeeId,
                        'date' => $requestRawTimesheet->date,
                        'type' => 'I',
                        'clock_time' => $requestRawTimesheet->timeIn ? $requestRawTimesheet->timeIn : null,
                        'is_analyzed' => false,
                        'worksheet_id' => $id,
                        'project_code' => $requestRawTimesheet->projectCode,
                        'clock_time_lat' => $requestRawTimesheet->timeInLat,
                        'clock_time_long' => $requestRawTimesheet->timeInLong
                    ];
                    $this->rawTimeSheetDao->save($rawTimeSheetIn);
                    $rawTimeSheetOut = [
                        'tenant_id' => $this->requester->getTenantId(),
                        'company_id' => $this->requester->getCompanyId(),
                        'employee_id' => $requestRawTimesheet->employeeId,
                        'date' => $requestRawTimesheet->date,
                        'type' => 'O',
                        'clock_time' => $requestRawTimesheet->timeOut ? $requestRawTimesheet->timeOut : null,
                        'is_analyzed' => false,
                        'worksheet_id' => $id,
                        'project_code' => $requestRawTimesheet->projectCode,
                        'clock_time_lat' => $requestRawTimesheet->timeOutLat,
                        'clock_time_long' => $requestRawTimesheet->timeOutLong
                    ];
                    $this->rawTimeSheetDao->save($rawTimeSheetOut);
                }
            }
        }

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }


    public
    function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        DB::transaction(function () use (&$request) {
            $this->requestRawTimeSheetDao->delete($request->id);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataDeleted')));
    }

}
