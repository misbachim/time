<?php

namespace App\Http\Controllers;

use App\Business\Dao\TimeAttributeDao;
use App\Business\Model\Requester;
use App\Business\Model\AppResponse;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TimeAttributeController extends Controller
{
    public function __construct(Requester $requester, TimeAttributeDao $timeAttributeDao)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->timeAttributeDao = $timeAttributeDao;
    }

    /**
     * Get all Time Attribute
     * @param request
     */
    public function getAll(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, ["companyId" => "required|numeric"]);

        $data=array();
        $attribute = $this->timeAttributeDao->getAll();

        $count=count($attribute);

        if($count > 0){
            for ($i=0;$i<$count;$i++){
                $data[$i] = $attribute[$i];
                $data[$i]->person =  $externalCoreController->getEmployee($attribute[$i]->employeeId, $request->applicationId);
            }
        }

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function getAllForEmployee(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer|exists:pgsql_core.companies,id',
            'employeeId' => 'required|string'
        ]);

        $data = $this->timeAttributeDao->getTimeGroupEmployee($request->employeeId);

        $response = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($response);
    }

    /**
     * Get one time attribute in one company based on id
     * @param request
     */
    public function getOne(Request $request, ExternalCoreController $externalCoreController)
    {
        $this->validate($request, [
            "id" => "required|integer",
            "companyId" => "required|integer"
        ]);

        $data = $this->timeAttributeDao->getOne(
            $request->id
        );

        if (count($data) > 0) {
            $data->person = $externalCoreController->getEmployee($data->employeeId, $request->applicationId);
        }

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get one time attribute in one company based on employee id
     * @param request
     */
    public function getOneByEmployeeId(Request $request)
    {
        $this->validate($request, [
            "employeeId" => "required",
            "companyId" => "required|integer"
        ]);

        $data = $this->timeAttributeDao->getOneEmployeeId(
            $request->employeeId
        );

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function getHistory(Request $request) {

        $this->validate($request, [
            "personId" => "required",
            "companyId" => "required|integer"
        ]);

        $data = $this->timeAttributeDao->getHistory($request->personId);
        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function saveChangeGroup(Request $request) {
        $data = array();
        $this->checkTimeAttribute($request);

        if ($this->timeAttributeDao->checkLastDataEmployeeTimeAttributes($request->personId, $request->employeeId, $request->timeGroupCode,
            Carbon::parse($request->effBegin)->toDateString(), Carbon::parse($request->effEnd)->toDateString())) {
            throw new AppException(trans('Sorry, you cannot change the same data'));
        }

        DB::transaction(function () use (&$request, &$data) {
            if($request->id) {
                $setEffEnd = Carbon::parse($request->effBegin)->subDay(1);
                $attribute = ['eff_end' => $setEffEnd];

                $this->timeAttributeDao->update($request->id, $attribute);
            }

            $arr = $this->constructTimeAttribute($request);
            $data['id'] = $this->timeAttributeDao->save($arr);
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    /**
     * Save time attribute to DB
     * @param request
     */
    public function save(Request $request)
    {
        $data = array();
        $this->checkTimeAttribute($request);

        DB::transaction(function () use (&$request, &$data) {
            $attribute = $this->constructTimeAttribute($request);
            $data['id'] = $this->timeAttributeDao->save($attribute);
            $request->id = $data['id'];
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    /**
     * Update time attribute to DB
     * @param request
     */
    public function update(Request $request)
    {
        $data = array();
        $this->validate($request, ['id' => 'required|integer']);
        $this->checkTimeAttribute($request);

        DB::transaction(function () use (&$request, &$data) {
            $attribute = $this->constructTimeAttribute($request);

            $this->timeAttributeDao->update(
                $request->id,
                $attribute
            );
        });

        $resp = new AppResponse($data, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    /**
     * Validate save permission request.
     * @param request
     */
    private
    function checkTimeAttribute(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required',
            'personId' => 'required',
            'effBegin' => 'required|date|before_or_equal:effEnd',
            'effEnd' => 'required|date',
            'timeGroupCode' => 'required|max:20|exists:time_groups,code'
        ]);
    }

    /**
     * Construct a permission request object (array).
     * @param request
     */
    private
    function constructTimeAttribute(Request $request)
    {
        $attribute = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $request->companyId,
            'employee_id' => $request->employeeId,
            'person_id' => $request->personId,
            'eff_begin' => $request->effBegin,
            'eff_end' => $request->effEnd,
            'time_group_code' => $request->timeGroupCode
        ];
        return $attribute;
    }
}