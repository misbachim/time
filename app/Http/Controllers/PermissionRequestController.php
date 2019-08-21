<?php

namespace App\Http\Controllers;

use App\Business\Dao\Core\PersonDao;
use App\Business\Dao\Core\WorklistDao;
use App\Business\Dao\PermissionRequestDao;
use App\Business\Dao\TimeSheetDao;
use App\Business\Model\Requester;
use App\Business\Model\AppResponse;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionRequestController extends Controller
{
    public function __construct(
        Requester $requester,
        PermissionRequestDao $permissionRequestDao,
        PersonDao $personDao,
        WorklistDao $worklistDao,
        TimeSheetDao $timeSheetDao
    ) {
        parent::__construct();

        $this->requester = $requester;
        $this->permissionRequestDao = $permissionRequestDao;
        $this->personDao = $personDao;
        $this->worklistDao = $worklistDao;
        $this->timeSheetDao = $timeSheetDao;
    }

    /**
     * Get all Permit Request
     * @param request
     */
    public function getAll(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, ["companyId" => "required|numeric"]);

        $data = array();
        $permit = $this->permissionRequestDao->getAll($request->companyId);

        $count = count($permit);

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $data[$i] = $permit[$i];
                //                $data[$i]->person = $externalCoreController->getEmployee($permit[$i]->employeeId, $request->applicationId);
                $person = $this->personDao->getOneEmployee($permit[$i]->employeeId);
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

        $permit = $this->permissionRequestDao->getAllByEmployeeId($request->employeeId, $request->companyId);

        $resp = new AppResponse($permit, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get one permit request in one company based on permit request id
     * @param request
     */
    public function getOne(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            "id" => "required|integer",
            "companyId" => "required|integer"
        ]);

        $data = $this->permissionRequestDao->getOne(
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
        }

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Save personAsset to DB
     * @param request
     */
    public function save(Request $request)
    {
        $data = array();
        $this->checkPermissionRequest($request);

        // check if requested month already in timsheet
        $month = date('m', strtotime($request->permissionDate));
        $timeSheet= $this->timeSheetDao->getOneTimeSheet($month);
        if (count($timeSheet)>0){
            throw new AppException('messages.timeSheetOfChosenMonthHasBeenGenerated');
        }

        DB::transaction(function () use (&$request, &$data) {
            $permissionRequest = $this->constructPermissionRequest($request);

            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $permissionRequest['file_reference'] = $fileUris['DOC'];
                }
            }
            $data['id'] = $this->permissionRequestDao->save($permissionRequest);
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    /**
     * Update permissionRequest to DB
     * @param request
     */
    public function update(Request $request)
    {
        $data = array();
        $this->validate($request, ['id' => 'required|integer']);

        DB::transaction(function () use (&$request, &$data) {
            $permissionRequest = [];
            if ($request->upload == true) {
                $fileUris = $this->getFileUris($request, $data);
                if (!empty($fileUris)) {
                    $permissionRequest['file_reference'] = $fileUris['DOC'];
                }
            } else {
                $permissionRequest['status'] = $request->status;

                if ($request->has('worklistAnswer')) {
                    // accept (worklistAnswer === 'AD') or cancel (worklistAnswer === 'C') request
                    $worklistData = [
                        'is_active' => false,
                    ];
                    if ($request->worklistAnswer === 'AD') {
                        $worklistData['answer'] = $request->worklistAnswer;
                        $worklistData['notes'] = $request->worklistNotes;

                        // update table hr_core -> worklists
                        $this->worklistDao->updateByLovWftyAndRequestId('PERM', $request->id, $worklistData);
                    } else if ($request->worklistAnswer === 'C') {
                        // update table hr_core -> worklists
                        $this->worklistDao->updateByLovWftyAndRequestId('PERM', $request->id, $worklistData);
                    }
                }
            }
            $this->permissionRequestDao->update(
                $request->id,
                $permissionRequest
            );
        });

        $resp = new AppResponse($data, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }


    /**
     * Validate save permission request.
     * @param request
     */
    private function checkPermissionRequest(Request $request)
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
            'permitCode' => 'required',
            'reason' => 'required',
            'status' => 'required|max:1',
            'date' => 'required|date'
        ]);
    }

    /**
     * Construct a permission request object (array).
     * @param request
     */
    private
    function constructPermissionRequest(Request $request)
    {
        $permissionRequest = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $request->companyId,
            'employee_id' => $request->employeeId,
            'permit_code' => $request->permitCode,
            'reason' => $request->reason,
            'status' => $request->status,
            'date' => $request->date,
        ];
        return $permissionRequest;
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
}
