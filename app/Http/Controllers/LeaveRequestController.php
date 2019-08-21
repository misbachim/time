<?php

namespace App\Http\Controllers;

use App\Business\Dao\Core\CompanyDao;
use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\WorklistDao;
use App\Business\Dao\LeaveDao;
use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\LeaveRequestDetailDao;
use App\Business\Dao\TimeSheetDao;
use App\Business\Model\Requester;
use App\Business\Model\AppResponse;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Business\Dao\LogScheduleDao;
use App\Business\Dao\LogSchedulePatternDao;
use App\Business\Dao\CalendarDao;
use Carbon\Carbon;
use App\Business\Model\PagingAppResponse;

class LeaveRequestController extends Controller
{
    public function __construct(
        Requester $requester,
        LeaveRequestDao $leaveRequestDao,
        LeaveDao $leaveDao,
        QuotaGeneratorController $quotaGeneratorController,
        LeaveRequestDetailDao $leaveRequestDetailDao,
        PersonDao $personDao,
        WorklistDao $worklistDao,
        LogScheduleDao $logScheduleDao,
        LogSchedulePatternDao $logSchedulePatternDao,
        CompanyDao $companyDao,
        CalendarDao $calendarDao,
        TimeSheetDao $timeSheetDao
    )
    {
        parent::__construct();

        $this->requester = $requester;
        $this->leaveRequestDao = $leaveRequestDao;
        $this->leaveDao = $leaveDao;
        $this->quotaGeneratorController = $quotaGeneratorController;
        $this->leaveRequestDetailDao = $leaveRequestDetailDao;
        $this->personDao = $personDao;
        $this->worklistDao = $worklistDao;
        $this->logScheduleDao = $logScheduleDao;
        $this->logSchedulePatternDao = $logSchedulePatternDao;
        $this->companyDao = $companyDao;
        $this->calendarDao = $calendarDao;
        $this->timeSheetDao = $timeSheetDao;
    }

    /**
     * Get all Leave Request
     * @param request
     */
    public function getAll(Request $request, ExternalCoreController $externalCoreController)
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

        $data = array();
        $leave = $this->leaveRequestDao->getAll(
            $request->companyId,
            $offset,
            $limit
        );

        $leaveForCount = $this->leaveRequestDao->getCount($request->companyId);
        $count = count($leaveForCount);

        if (count($leave) > 0) {
            for ($i = 0; $i < count($leave); $i++) {
                $data[$i] = $leave[$i];
                $data[$i]->detail = $this->leaveRequestDetailDao->getAllByLeaveRequest($leave[$i]->id);
                $data[$i]->weight = $this->leaveRequestDetailDao->getWeightLeaveRequest($leave[$i]->id);
                //                $data[$i]->person = $externalCoreController->getEmployee($leave[$i]->employeeId, $request->applicationId, $request->applicationId);
                $person = $this->personDao->getOneEmployee($leave[$i]->employeeId);
                if ($person) {
                    $data[$i]->person = $person;
                } else {
                    $data[$i]->person = null;
                }
            }
        }

        return $this->renderResponse(new PagingAppResponse($data, trans('messages.allDataRetrieved'), $limit, $count, $pageNo));
    }

    /**
     * Get all Leave Request
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

        $data = array();
        $person = $this->personDao->searchEmployees($request->search);
//        info('$person', [$person]);

        if (count($person) > 0) {
            $empIds = array();
            for ($i = 0; $i < count($person); $i++) {
                info('persEmpId', [$person[$i]->employeeId]);
                array_push($empIds, $person[$i]->employeeId);
            }
            info('$empIds', [$empIds]);
            $leave = $this->leaveRequestDao->searchEmpIds(
                $request->companyId,
                $offset,
                $limit,
                $empIds,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $leaveForCount = $this->leaveRequestDao->getCountSearchEmpIds($request->companyId, $empIds, $request->status, $request->dateStart, $request->dateEnd);
        } else {
            $leave = $this->leaveRequestDao->search(
                $request->companyId,
                $offset,
                $limit,
                $request->search,
                $request->status,
                $request->dateStart,
                $request->dateEnd
            );
            $leaveForCount = $this->leaveRequestDao->getCountSearch($request->companyId, $request->search, $request->status, $request->dateStart, $request->dateEnd);
        }

//        info('leave', [$leave]);


        if (count($leave) > 0) {
            for ($i = 0; $i < count($leave); $i++) {
                $data[$i] = $leave[$i];
                $data[$i]->detail = $this->leaveRequestDetailDao->getAllByLeaveRequest($leave[$i]->id);
                $data[$i]->weight = $this->leaveRequestDetailDao->getWeightLeaveRequest($leave[$i]->id);
                $person = $this->personDao->getOneEmployee($leave[$i]->employeeId, $request->search);
                if ($person) {
                    $data[$i]->person = $person;
                } else {
                    $data[$i]->person = null;
                }
            }
        }

        $count = count($leaveForCount);
//        info('$count', [$count]);

        return $this->renderResponse(new PagingAppResponse($data, trans('messages.allDataRetrieved'), $limit, $count, $pageNo));
    }

    /**
     * Get all Permit Request by Employee Id
     * @param request
     */
    public function getAllByEmployeeId(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
            "employeeId" => "required"
        ]);

        $data = array();
        $leave = $this->leaveRequestDao->getAllByEmployeeId($request->employeeId, $request->companyId);
        $count = count($leave);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $leave[$i];
                $data[$i]->detail = $this->leaveRequestDetailDao->getAllByLeaveRequest($leave[$i]->id);
                $data[$i]->weight = (int)$this->leaveRequestDetailDao->getWeightLeaveRequest($leave[$i]->id);
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function getOne(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            "id" => "required|integer",
            "companyId" => "required|integer"
        ]);

        $data = $this->leaveRequestDao->getOne(
            $request->id,
            $request->companyId
        );

        if (count($data) > 0) {
            $data->detail = $this->leaveRequestDetailDao->getAllByLeaveRequest($request->id);
            $data->weight = $this->leaveRequestDetailDao->getWeightLeaveRequest($request->id);
            //            $data->person = $externalCoreController->getEmployee($data->employeeId, $request->applicationId);
            $person = $this->personDao->getOneEmployee($data->employeeId);
            if ($person) {
                $data->person = $person;
            } else {
                $data->person = null;
            }
        }

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get many Leave Request
     * @param request
     */
    public function getMany(Request $request)
    {
        $this->validate($request, [
            "leaveCodes" => "required|array",
            "companyId" => "required|integer",
            "employeeId" => "required"
        ]);

        $data = array();
        $leave = $this->leaveRequestDao->getMany(
            $request->companyId,
            $request->leaveCodes,
            $request->employeeId
        );

        if (count($leave) > 0) {
            for ($i = 0; $i < count($leave); $i++) {
                $data[$i] = $leave[$i];
                $data[$i]->detail = $this->leaveRequestDetailDao->getAllByLeaveRequest($leave[$i]->id);
                $data[$i]->weight = $this->leaveRequestDetailDao->getWeightLeaveRequest($leave[$i]->id);

                $person = $this->personDao->getOneEmployee($leave[$i]->employeeId);
                if ($person) {
                    $data[$i]->person = $person;
                } else {
                    $data[$i]->person = null;
                }
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function save(Request $request)
    {
        info('request', array($request));
        $data = array();
        $this->checkLeaveRequest($request);

        // check if requested month already in timesheet
        $month = date('m', strtotime($request->startDate));
        $timeSheet = $this->timeSheetDao->getOneTimeSheet($month);


        // check if requested leave reuest only 1 day and it is day off or holiday
        $isDayOff = $this->isDayOff($request->detail[0]->date, $request->employeeId);
        $isHoliday = $this->isHoliday($request->detail[0]->date);
        if ((count($request->detail) == 1 && ($isDayOff || $isHoliday)) || count($request->detail) === 0) {
            throw new AppException(trans('messages.requestedDateIsDayOff'));
        }

        // check if any leave request on the requested dates
        $leaveReqOnDate = $this->getLeaveRequestByEmployeeIdAndDate($request);
        if (count($leaveReqOnDate)) {
            foreach ($leaveReqOnDate as $leaveReq) {
                array_push($data, $leaveReq);
            }
            $resp = new AppResponse($data, trans('messages.thereIsLeaveRequestOnThisDate'));
            return $this->renderResponse($resp);
        }

        //getRemainingQuotas
        $quotas = null;
        $leaveDesc = $this->leaveDao->getOne($request->leaveCode);
        if ($leaveDesc->isQuotaBased) {
            $quotas = $this->quotaGeneratorController->getQuota(
                $request->leaveCode,
                $request->employeeId,
                $request->companyId,
                $leaveDesc->isAnnualLeave
            );
        }
        info('quotas', [$quotas]);
        info('$leaveDesc', [$leaveDesc]);

        //getSettingMaxAdvanceLeave
        $maxAdvanceLeave = $this->companyDao->getOneCompanySettingsByCode('MAL');

        //compareRemainingQuotaWithMaxAdvLeave
        if ($maxAdvanceLeave && $quotas != null) {
            $remainingQouta = $isHolidays = $isDayOffs = 0;
            for ($i = 0; $i < count($quotas); $i++) {
                $remainingQouta = $remainingQouta + $quotas[$i]->remainingQuota;
            }

            for ($i = 0; $i < count($request->detail); $i++) {
                $tempDayOffs = $this->isDayOff($request->detail[$i]->date, $request->employeeId);
                if (count($tempDayOffs)) {
                    info('$tempDayOffs', [$tempDayOffs]);
                    $isDayOffs++;
                }
                $tempHolidays = $this->isHoliday($request->detail[$i]->date);
                if ($tempHolidays > 0) {
                    info('$tempHolidays', [$tempHolidays]);
                    $isHolidays++;
                }
            }

            info('$maxAdvanceLeave->fixValue', [$maxAdvanceLeave->fixValue]);
            info('count($request->detail)', [count($request->detail)]);
            info('$isDayOffs', [$isDayOffs]);
            info('$isHolidays', [$isHolidays]);
            info('$remainingQouta * -1', [$remainingQouta * -1]);

            if ((($remainingQouta - (count($request->detail) - $isDayOffs - $isHolidays)) * -1) > $maxAdvanceLeave->fixValue) {
                throw new AppException(trans('messages.maxLeaveRequestLimit') . $remainingQouta);
            }
        }

        DB::transaction(function () use (&$request, &$data) {
            $leaveRequest = $this->constructLeaveRequest($request);

            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $leaveRequest['file_reference'] = $fileUris['DOC'];
                }
            }

            $data['id'] = $this->leaveRequestDao->save($leaveRequest);
            $request->id = $data['id'];
            $this->saveLeaveRequestDetail($request);
        });

        if (count($timeSheet) > 0) {
            $resp = new AppResponse($data, trans('messages.timeSheetOfChosenMonthHasBeenGenerated'));
        } else {
            $resp = new AppResponse($data, trans('messages.dataSaved'));
        }
        return $this->renderResponse($resp);
    }

    public function saveMobile(Request $request)
    {
        $data = array();
        $this->checkLeaveRequest($request);

        // check if requested month already in timesheet
        $month = date('m', strtotime($request->startDate));
        $timeSheet = $this->timeSheetDao->getOneTimeSheet($month);


        // check if requested leave reuest only 1 day and it is day off or holiday
        $isDayOff = $this->isDayOff($request->detail[0]->date, $request->employeeId);
        $isHoliday = $this->isHoliday($request->detail[0]->date);
        if ((count($request->detail) == 1 && ($isDayOff || $isHoliday)) || count($request->detail) === 0) {
            throw new AppException(trans('messages.requestedDateIsDayOff'));
        }

        // check if any leave request on the requested dates
        $leaveReqOnDate = $this->getLeaveRequestByEmployeeIdAndDate($request);
        if (count($leaveReqOnDate)) {
            throw new AppException(trans('messages.thereIsLeaveRequestOnThisDate'));
        }

        //getRemainingQuotas
        $quotas = null;
        $leaveDesc = $this->leaveDao->getOne($request->leaveCode);
        if ($leaveDesc->isQuotaBased) {
            $quotas = $this->quotaGeneratorController->getQuota(
                $request->leaveCode,
                $request->employeeId,
                $request->companyId,
                $leaveDesc->isAnnualLeave
            );
        }

        //getSettingMaxAdvanceLeave
        $maxAdvanceLeave = $this->companyDao->getOneCompanySettingsByCode('MAL');

        //compareRemainingQuotaWithMaxAdvLeave
        if ($maxAdvanceLeave && $quotas != null) {
            $remainingQouta = $isHolidays = $isDayOffs = 0;
            for ($i = 0; $i < count($quotas); $i++) {
                $remainingQouta = $remainingQouta + $quotas[$i]->remainingQuota;
            }

            for ($i = 0; $i < count($request->detail); $i++) {
                $tempDayOffs = $this->isDayOff($request->detail[$i]->date, $request->employeeId);
                if (count($tempDayOffs)) {
                    $isDayOffs++;
                }
                $tempHolidays = $this->isHoliday($request->detail[$i]->date);
                if ($tempHolidays > 0) {
                    $isHolidays++;
                }
            }

            if ((($remainingQouta - (count($request->detail) - $isDayOffs - $isHolidays)) * -1) > $maxAdvanceLeave->fixValue) {
                throw new AppException(trans('messages.maxLeaveRequestLimit') . $remainingQouta);
            }
        }

        DB::transaction(function () use (&$request, &$data) {
            $leaveRequest = $this->constructLeaveRequest($request);

            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $leaveRequest['file_reference'] = $fileUris['DOC'];
                }
            }

            $data['id'] = $this->leaveRequestDao->save($leaveRequest);
            $request->id = $data['id'];
            $this->saveLeaveRequestDetail($request);
        });

        if (count($timeSheet) > 0) {
            $resp = new AppResponse($data, trans('messages.timeSheetOfChosenMonthHasBeenGenerated'));
        } else {
            $resp = new AppResponse($data, trans('messages.dataSaved'));
        }
        return $this->renderResponse($resp);
    }

    public function update(Request $request)
    {
        $data = array();
        $this->validate($request, ['id' => 'required|integer']);

        DB::transaction(function () use (&$request, &$data) {
            $leaveRequest = [];

            // check if requested leave reuest only 1 day and it is day off or holiday
            if ($request->has('detail')) {
                if (count($request->detail) == 1) {
                    $isDayOff = $this->isDayOff($request->detail[0]['date'], $request->employeeId);
                    $isHoliday = $this->isHoliday($request->detail[0]['date']);
                    if ($isDayOff || $isHoliday) {
                        throw new AppException(trans('messages.requestedDateIsDayOff'));
                    }
                }
            }

            //getRemainingQuotas
            $quotas = null;
            $leaveDesc = $this->leaveDao->getOne($request->leaveCode);
            if ($leaveDesc !== null && $leaveDesc !== '') {
                if ($leaveDesc->isQuotaBased) {
                    $quotas = $this->quotaGeneratorController->getQuota(
                        $request->leaveCode,
                        $request->employeeId,
                        $request->companyId,
                        $leaveDesc->isAnnualLeave
                    );
                }
            }
            info('quotas', [$quotas]);
            info('$leaveDesc', [$leaveDesc]);

            if ($request->status !== 'C') {
                //getSettingMaxAdvanceLeave
                $maxAdvanceLeave = $this->companyDao->getOneCompanySettingsByCode('MAL');

                //compareRemainingQuotaWithMaxAdvLeave
                if ($maxAdvanceLeave && $quotas != null) {
                    $remainingQouta = $isHolidays = $isDayOffs = 0;
                    for ($i = 0; $i < count($quotas); $i++) {
                        $remainingQouta = $remainingQouta + $quotas[$i]->remainingQuota;
                    }

                    info('$maxAdvanceLeave->fixValue', [$maxAdvanceLeave->fixValue]);
                    info('count($request->detail)', [count($request->detail)]);
                    info('$remainingQouta * -1', [$remainingQouta * -1]);

                    if (($remainingQouta - count($request->detail)) * -1 > $maxAdvanceLeave->fixValue) {
                        throw new AppException(trans('messages.maxLeaveRequestLimit') . $remainingQouta);
                    }
                }
            }

            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $leaveRequest['file_reference'] = $fileUris['DOC'];
                }
            } else {
                $leaveRequest['status'] = $request->status;

                if ($request->has('worklistAnswer')) {
                    // accept (worklistAnswer === 'AD') or cancel (worklistAnswer === 'C') request
                    $worklistData = [
                        'is_active' => false,
                    ];
                    if ($request->worklistAnswer === 'AD') {
                        $worklistData['answer'] = $request->worklistAnswer;
                        $worklistData['notes'] = $request->worklistNotes;

                        // update table hr_core -> worklists
                        $this->worklistDao->updateByLovWftyAndRequestId('LEAV', $request->id, $worklistData);
                    } else if ($request->worklistAnswer === 'C') {
                        // update table hr_core -> worklists
                        $this->worklistDao->updateByLovWftyAndRequestId('LEAV', $request->id, $worklistData);
                    }
                }
            }

            $this->leaveRequestDao->update(
                $request->id,
                $leaveRequest
            );
        });

        $resp = new AppResponse($data, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }


    /**
     * Get check dayOff by employeeId
     * @param request
     */
    public function checkDayOff(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
            "dates" => "required|array",
            "employeeId" => "required"
        ]);

        $data = array();
        foreach ($request->dates as $date) {
            $isDayOff = $this->isDayOff($date, $request->employeeId);
            $isHoliday = $this->isHoliday($date);

            $newData = new \stdClass();
            $newData->date = $date;
            $newData->isDayOff = $isDayOff;
            $newData->isHoliday = $isHoliday;

            array_push($data, $newData);
        }

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }


    /**
     * Validate save leave request.
     * @param request
     */
    private
    function checkLeaveRequest(Request $request)
    {
        $this->validate($request, [
            'data' => 'required',
            'upload' => 'required|boolean'
        ]);

        if ($request->upload == true) {
            $this->validate($request, [
                'docTypes' => 'required|array|min:1',
                'fileContents' => 'required|array|min:1',
                'ref' => 'required|string|max:255'
            ]);
        }

        $reqData = (array)json_decode($request->data);
        if (null === $reqData) {
            throw new AppException(trans('messages.jsonInvalid'));
        }
        $request->merge($reqData);

        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required',
            'leaveCode' => 'required',
            'description' => 'present',
            'status' => 'required|max:1',
            'detail' => 'required'
        ]);
    }

    /**
     * Construct a leave request object (array).
     * @param request
     */
    private
    function constructLeaveRequest(Request $request)
    {
        $leaveRequest = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $request->companyId,
            'employee_id' => $request->employeeId,
            'leave_code' => $request->leaveCode,
            'description' => $request->description,
            'status' => $request->status
        ];
        return $leaveRequest;
    }

    /**
     * Get all uploaded file URIs from File service.
     * @param request , data
     */
    private function getFileUris(Request $request, array &$data)
    {
        $guzzle = new \GuzzleHttp\Client();
        try {
            $response = $guzzle->request(
                'POST',
                env('CDN_SERVICE_SAVE_API'),
                [
                    'multipart' => $this->constructPayload($request),
                    'headers' => [
                        'Authorization' => $request->headers->get('authorization'),
                        'Origin' => $request->headers->get('origin')
                    ]
                ]
            );
            $body = json_decode($response->getBody()->getContents());
            if ($body->status === 200) {
                $data['file'] = (array)$body;
                return (array)$body->data->fileUris;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $data['file'] = [];
            if ($e->hasResponse()) {
                $body = $e->getResponse()->getBody();
                $data['file'] = (array)json_decode($body->getContents());
            } else {
                $data['file']['status'] = 500;
                $data['file']['message'] = $e->getMessage();
                $data['file']['data'] = null;
            }
        }

        return [];
    }

    private function deleteFile($request, $fileUri)
    {
        $guzzle = new \GuzzleHttp\Client();
        try {
            $response = $guzzle->request('POST', env('CDN_SERVICE_DELETE_API'), [
                'form_params' => ['fileUri' => $fileUri, 'companyId' => $request->companyId],
                'headers' => [
                    'Authorization' => $request->headers->get('authorization'),
                    'Origin' => $request->headers->get('origin')
                ]
            ]);
            $body = json_decode($response->getBody()->getContents());
            return $body->status === 200;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
        // should never reach here
    }

    /**
     * Construct a multipart payload for uploading file to File service.
     * @param Request $request
     * @return array
     */
    private function constructPayload(Request $request)
    {
        $payload = array([
            'name' => 'data',
            'contents' => $request->data
        ], [
            'name' => 'ref',
            'contents' => $request->ref
        ], [
            'name' => 'companyId',
            'contents' => $request->companyId
        ]);
        foreach ($request->docTypes as $i => $docType) {
            array_push($payload, [
                'name' => "docTypes[$i]",
                'contents' => $docType
            ]);
        }
        foreach ($request->fileContents as $i => $file) {
            array_push($payload, [
                'name' => "fileContents[$i]",
                'contents' => file_get_contents($file),
                'filename' => $file->getClientOriginalName()
            ]);
        }

        return $payload;
    }

    private function saveLeaveRequestDetail(Request $request)
    {
        for ($i = 0; $i < count($request->detail); $i++) {
            $leaveDetailReq = new \Illuminate\Http\Request();
            $leaveDetail = (array)$request->detail[$i];
            $leaveDetailReq->replace([
                'weight' => $leaveDetail['weight'],
                'status' => $leaveDetail['status'],
                'date' => $leaveDetail['date']
            ]);
            $this->validate($leaveDetailReq, [
                "weight" => 'required|numeric',
                "status" => 'required',
                "date" => 'required|date'
            ]);

            $isDayOff = $this->isDayOff($leaveDetail['date'], $request->employeeId);
            $isHoliday = $this->isHoliday($leaveDetail['date']);

            if (!$isDayOff && !$isHoliday) {
                $data = [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'leave_request_id' => $request->id,
                    'weight' => $leaveDetail['weight'],
                    'status' => $leaveDetail['status'],
                    'date' => $leaveDetail['date']
                ];
                $this->leaveRequestDetailDao->save($data);
            }
        }
    }

    private function isDayOff($targetDate, $employeeId)
    {
        $logSchedule = $this->logScheduleDao->getOneByEmployeeId(
            $employeeId,
            $targetDate
        );
        if (!$logSchedule) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }

        $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
        $dateStart = Carbon::parse($logSchedule->dateStart);
        $targetDate = Carbon::parse($targetDate);

        if ($pattern->count() === 0) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }
        $patternIdx = $targetDate->diffInDays($dateStart) % $pattern->count();
        $scheduleDetail = $pattern->slice($patternIdx, 1)->first();

        return $scheduleDetail->leaveCode;
    }

    private function isHoliday($targetDate)
    {
        $dateStart = Carbon::parse($targetDate)->toDateString();
        info('$dateStart', array($dateStart));
        $event = $this->calendarDao->getOneAllHoliday($dateStart);
        info('$event', array($event));
        return count($event);
    }

    private function getLeaveRequestByEmployeeIdAndDate($request)
    {
        $dates = array();
        foreach ($request->detail as $detail) {
            array_push($dates, $detail->date);
        }
        $leaveReqOnDate = $this->leaveRequestDetailDao->getAllByEmployeeIdAndDate($request->employeeId, $dates);

        if (count($leaveReqOnDate)) {
            foreach ($leaveReqOnDate as $eachReq) {
                $eachReq->detail = $this->leaveRequestDetailDao->getAllByLeaveRequest($eachReq->id);
                $eachReq->weight = $this->leaveRequestDetailDao->getWeightLeaveRequest($eachReq->id);

                $person = $this->personDao->getOneEmployee($eachReq->employeeId);
                if (count($person)) {
                    $eachReq->person = $person;
                } else {
                    $eachReq->person = null;
                }
            }
        }

        return $leaveReqOnDate;
    }
}
