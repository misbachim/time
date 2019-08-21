<?php

namespace App\Http\Controllers;

use App\Business\Helper\HttpClient;
use App\Business\Model\Requester;

/**
 * Controller for communication with core microservice
 * @package App\Http\Controllers
 */
class ExternalCoreController extends Controller
{
    public static $GET_PERSON_URI = '/person/getOne';
    public static $GET_EMPLOYEE_URI = '/person/getOneEmployee';
    public static $ADVANCED_SEARCH_PERSON_URI = '/person/advancedSearch';
    public static $GENERATE_WORKLIST_REQUEST_URI = '/worklist/generateWorklist';

    private $coreServiceUrl;
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->coreServiceUrl = env('CORE_SERVICE_API');
        $this->requester = $requester;
    }

    public function getPerson($personId, $applicationId)
    {
        $url = $this->coreServiceUrl . ExternalCoreController::$GET_PERSON_URI;
        $body = array(
            'id' => $personId,
            'applicationId' => $applicationId
        );

        $response = HttpClient::post($url, $body, $this->requester);

        return $response->data;
    }

    public function getEmployee($employeeId, $applicationId)
    {
        $url = $this->coreServiceUrl . ExternalCoreController::$GET_EMPLOYEE_URI;
        $body = array(
            'id' => $employeeId,
            'applicationId' => $applicationId
        );

        $response = HttpClient::post($url, $body, $this->requester);

        return $response->data;
    }

    public function advancedSearchPerson($companyId, $menuCode, $selectedFields, $criteria, $pageInfo, $applicationId)
    {
        $url = $this->coreServiceUrl . ExternalCoreController::$ADVANCED_SEARCH_PERSON_URI;
        $body = [
            'companyId' => $companyId,
            'menuCode' => $menuCode,
            'searchData' => [
                'selectedFields' => $selectedFields,
                'criteria' => $criteria
            ],
            'pageInfo' => $pageInfo,
            'applicationId' => $applicationId
        ];

        return HttpClient::post($url, $body, $this->requester)->data;
    }

    public function generateWorklist($requestRawTimesheet, $applicationId)
    {
        // info('rts', [$this->requester->getCompanyId()]);

        $url = $this->coreServiceUrl . ExternalCoreController::$GENERATE_WORKLIST_REQUEST_URI;
        $body = array(
            'applicationId' => $applicationId,
            'companyId' => $this->requester->getCompanyId(),
            'workflowType' => 'ATTD',
            'requestId' => $requestRawTimesheet->id,
            'requesterId' => $requestRawTimesheet->employeeId,
            'subType' => null,
            'description' => null,
            'projectCode' => $requestRawTimesheet->projectCode
        );

        $response = HttpClient::post($url, $body, $this->requester);

        return $response->data;
    }
}
