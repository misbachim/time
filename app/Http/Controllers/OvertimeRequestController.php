<?php

namespace App\Http\Controllers;

use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\WorklistDao;
use App\Business\Dao\OvertimeRequestDao;
use App\Business\Dao\TimeSheetDao;
use App\Business\Model\Requester;
use App\Business\Model\AppResponse;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OvertimeRequestController extends Controller
{
    public function __construct(
        Requester $requester,
        OvertimeRequestDao $overtimeRequestDao,
        PersonDao $personDao,
        WorklistDao $worklistDao,
        TimeSheetDao $timeSheetDao
    ) {
        parent::__construct();

        $this->requester = $requester;
        $this->overtimeRequestDao = $overtimeRequestDao;
        $this->personDao = $personDao;
        $this->worklistDao = $worklistDao;
        $this->timeSheetDao = $timeSheetDao;
    }

    /**
     * Get all Overtime Request
     * @param request
     */
    public
    function getAll(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, ["companyId" => "required|numeric"]);

        $data = array();
        $over = $this->overtimeRequestDao->getAll($request->companyId);

        $count = count($over);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $over[$i];
                //                $data[$i]->person = $externalCoreController->getEmployee($over[$i]->employeeId, $request->applicationId);
                $person = $this->personDao->getOneEmployee($over[$i]->employeeId);
                if ($person) {
                    $data[$i]->person = $person;
                } else {
                    $data[$i]->person = null;
                }

                $data[$i]->timeStart = Carbon::parse($over[$i]->timeStart)->format('H:i');
                $data[$i]->timeEnd = Carbon::parse($over[$i]->timeEnd)->format('H:i');

                $diffInHours = Carbon::parse($over[$i]->timeStart)->diffInHours(Carbon::parse($over[$i]->timeEnd));
                $data[$i]->totalHours = $diffInHours;
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get all Overtime Request by Employee Id
     * @param request
     */
    public
    function getAllByEmployeeId(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
            "employeeId" => "required"
        ]);

        $data = array();
        $over = $this->overtimeRequestDao->getAllByEmployeeId($request->employeeId, $request->companyId);

        $count = count($over);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $over[$i];

                $data[$i]->timeStart = Carbon::parse($over[$i]->timeStart)->format('H:i');
                $data[$i]->timeEnd = Carbon::parse($over[$i]->timeEnd)->format('H:i');

                $diffInHours = Carbon::parse($over[$i]->timeStart)->diffInHours(Carbon::parse($over[$i]->timeEnd));
                $data[$i]->totalHours = $diffInHours;
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get all Overtime Request Ordered for Employee Id
     * @param request
     */
    public
    function getAllOrderedForMe(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
            "employeeId" => "required"
        ]);

        $data = array();
        $over = $this->overtimeRequestDao->getAllOrderedForMe($request->employeeId, $request->companyId);

        $count = count($over);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $over[$i];

                $data[$i]->timeStart = Carbon::parse($over[$i]->timeStart)->format('H:i');
                $data[$i]->timeEnd = Carbon::parse($over[$i]->timeEnd)->format('H:i');

                $diffInHours = Carbon::parse($over[$i]->timeStart)->diffInHours(Carbon::parse($over[$i]->timeEnd));
                $data[$i]->totalHours = $diffInHours;
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get all Overtime Request Ordered by Employee Id
     * @param request
     */
    public
    function getAllOrderedByMe(Request $request)
    {
        $this->validate($request, [
            "companyId" => "required|numeric",
            "orderedBy" => "required"
        ]);

        $data = array();
        $over = $this->overtimeRequestDao->getAllOrderedByMe($request->orderedBy, $request->companyId);

        $count = count($over);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $over[$i];

                $data[$i]->timeStart = Carbon::parse($over[$i]->timeStart)->format('H:i');
                $data[$i]->timeEnd = Carbon::parse($over[$i]->timeEnd)->format('H:i');

                $diffInHours = Carbon::parse($over[$i]->timeStart)->diffInHours(Carbon::parse($over[$i]->timeEnd));
                $data[$i]->totalHours = $diffInHours;
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public
    function getAccumulationOvertime(Request $request) {

        $this->validate($request, [
            "companyId" => "required|numeric",
            "employeeId" => "required"
        ]);

        $data = array();
        $over = $this->overtimeRequestDao->getAccumulationOvertime($request->employeeId, $request->companyId);

        $count = count($over);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $over[$i];

                $data[$i]->timeStart = Carbon::parse($over[$i]->timeStart)->format('H:i');
                $data[$i]->timeEnd = Carbon::parse($over[$i]->timeEnd)->format('H:i');

                $diffInHours = Carbon::parse($over[$i]->timeStart)->diffInHours(Carbon::parse($over[$i]->timeEnd));
                $data[$i]->totalHours = $diffInHours;
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get one overtime req in one company based on overtime req id
     * @param request
     */
    public
    function getOne(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            "id" => "required|integer",
            "companyId" => "required|integer"
        ]);

        $data = $this->overtimeRequestDao->getOne(
            $request->id,
            $request->companyId
        );

        if (count($data) > 0) {
            //            $data->person = $externalCoreController->getEmployee($data->employeeId, $request->applicationId);
            $person = $this->personDao->getOneEmployee($data->employeeId);
            if ($person) {
                $data->person = $person;
            } else {
                $data->person = null;
            }
            $data->timeStart = Carbon::parse($data->timeStart)->format('H:i');
            $data->timeEnd = Carbon::parse($data->timeEnd)->format('H:i');

            $diffInHours = Carbon::parse($data->timeStart)->diffInHours(Carbon::parse($data->timeEnd));
            $data->totalHours = $diffInHours;
        }

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Save overtime req to DB
     * @param request
     */
    public
    function save(Request $request)
    {
        $data = array();
        $this->checkOvertimeRequest($request);

        // check if requested month already in timsheet
        $month = date('m', strtotime($request->scheduleDate));
        $timeSheet= $this->timeSheetDao->getOneTimeSheet($month);
        if (count($timeSheet)>0){
            throw new AppException('messages.timeSheetOfChosenMonthHasBeenGenerated');
        }


        DB::transaction(function () use (&$request, &$data) {
            $overtimeRequest = $this->constructOvertimeRequest($request);
            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $overtimeRequest['file_reference'] = $fileUris['DOC'];
                }
            }
            $data['id'] = $this->overtimeRequestDao->save($overtimeRequest);
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    /**
     * Update overtime Request to DB
     * @param request
     */
    public
    function update(Request $request)
    {
        $data = array();
        $this->validate($request, ['id' => 'required|integer']);

        DB::transaction(function () use (&$request, &$data) {
            $overtimeRequest = [];
            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $overtimeRequest['file_reference'] = $fileUris['DOC'];
                }
            } else {
                $overtimeRequest['status'] = $request->status;

                if ($request->has('worklistAnswer')) {
                    // accept (worklistAnswer === 'AD') or cancel (worklistAnswer === 'C') request
                    $worklistData = [
                        'is_active' => false,
                    ];
                    if ($request->worklistAnswer === 'AD') {
                        $worklistData['answer'] = $request->worklistAnswer;
                        $worklistData['notes'] = $request->worklistNotes;

                        // update table hr_core -> worklists
                        $this->worklistDao->updateByLovWftyAndRequestId('OVER', $request->id, $worklistData);
                    } else if ($request->worklistAnswer === 'C') {
                        // update table hr_core -> worklists
                        $this->worklistDao->updateByLovWftyAndRequestId('OVER', $request->id, $worklistData);
                    }
                }
            }
            $this->overtimeRequestDao->update(
                $request->id,
                $overtimeRequest
            );
        });

        $resp = new AppResponse($data, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    /**
     * Validate save overtime request.
     * @param request
     */
    private
    function checkOvertimeRequest(Request $request)
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
            'timeStart' => 'required|date',
            'timeEnd' => 'required|date',
            'description' => 'present',
            'status' => 'required|max:1',
            'scheduleDate' => 'required|date'
        ]);
    }

    /**
     * Construct a overtime request object (array).
     * @param request
     */
    private
    function constructOvertimeRequest(Request $request)
    {
        $overtimeRequest = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $request->companyId,
            'employee_id' => $request->employeeId,
            'ordered_by' => $request->orderedBy,
            'time_start' => $request->timeStart,
            'time_end' => $request->timeEnd,
            'description' => $request->description,
            'status' => $request->status,
            'schedule_date' => $request->scheduleDate,
        ];
        return $overtimeRequest;
    }

    /**
     * Get all uploaded file URIs from File service.
     * @param request , data
     */
    private
    function getFileUris(Request $request, array &$data)
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

    private
    function deleteFile($request, $fileUri)
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
    private
    function constructPayload(Request $request)
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
}
