<?php

namespace App\Http\Controllers;

use App\Business\Dao\LeaveDao;
use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\Core\EmployeeStatusesDao;
use App\Business\Dao\Core\LocationDao;
use App\Business\Dao\Core\PositionDao;
use App\Business\Dao\WorkSheetActivityDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\QuotaGeneratorController;

/**
 * Class for handling Worksheet Activity process
 */
class WorkSheetActivityController extends Controller
{
    public function __construct(
        Requester $requester,
        WorkSheetActivityDao $workSheetActivityDao
    )
    {
        parent::__construct();

        $this->requester = $requester;
        $this->workSheetActivityDao = $workSheetActivityDao;
    }

    /**
     * Get all Worksheet Activity
     * @param request
     */
    public function getAll(Request $request)
    {
        $this->validate($request, ["companyId" => "required|numeric"]);
        $data = $this->workSheetActivityDao->getAll();

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }


    /**
     * Get one Worksheet Activity in one company based on id
     * @param request
     */
    public function getOne(Request $request)
    {
        $this->validate($request, [
            "id" => "required|integer",
            "companyId" => "required|integer"
        ]);

        $data = $this->workSheetActivityDao->getOne($request->id);

        $resp = new AppResponse($data, trans('messages.dataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function getLov(Request $request)
    {
        $this->validate($request, ['companyId' => 'required']);

        $data = $this->workSheetActivityDao->getLov();

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Get all active worksheet activity
     * @param Request $request
     * @return AppResponse
     */
    public function search(Request $request)
    {
        $this->validate($request, ["searchQuery" => "required"]);

        $data = $this->workSheetActivityDao->search($request->searchQuery);

        $resp = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    /**
     * Save Worksheet Activity to DB
     * @param request
     */
    public function save(Request $request)
    {
        $data = array();
        $this->checkWorksheetActivity($request);
        if ($this->workSheetActivityDao->isCodeDuplicate($request->code)) {
            throw new AppException(trans('messages.duplicateCode'));
        }

        DB::transaction(function () use (&$request, &$data) {
            $data = $this->constructWorksheetActivity($request);
            $data['id'] = $this->workSheetActivityDao->save($data);
            $request->id = $data['id'];
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    /**
     * Update Worksheet Activity to DB
     * @param request
     */
    public function update(Request $request)
    {
        $data = array();
        $this->validate($request, ['id' => 'required|integer']);
        $this->checkWorksheetActivity($request);

        DB::transaction(function () use (&$request, &$data) {
            $data = $this->constructWorksheetActivity($request);
            unset($data['code']);

            $this->workSheetActivityDao->update(
                $request->id,
                $data
            );
        });

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    /**
     * Delete a  Worksheet Activity.
     */
    public function delete(Request $request)
    {
        $this->validate($request, [
            "id" => "required",
            "companyId" => "required"
        ]);

        DB::transaction(function () use (&$request) {
            $data = [
                "eff_end" => Carbon::now()
            ];
            $this->workSheetActivityDao->update($request->id, $data);
        });

        $resp = new AppResponse(null, trans('messages.dataDeleted'));
        return $this->renderResponse($resp);
    }

    /**
     * Validate save Worksheet Activity.
     * @param request
     */
    private
    function checkWorksheetActivity(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'code' => 'required',
            'name' => 'required',
            'effBegin' => 'required|date|before_or_equal:effEnd',
            'effEnd' => 'required|date',
            'description' => 'required'
        ]);
    }

    /**
     * Construct a Worksheet Activity object (array).
     * @param request
     */
    private
    function constructWorksheetActivity(Request $request)
    {
        $attribute = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $request->companyId,
            'code' => $request->code,
            'name' => $request->name,
            'eff_begin' => $request->effBegin,
            'eff_end' => $request->effEnd,
            'description' => $request->description
        ];
        return $attribute;
    }
}
