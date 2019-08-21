<?php

namespace App\Http\Controllers;

use App\Business\Dao\EmployeeAnnualLeaveDao;
use App\Business\Dao\LeaveDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Class for handling Leave process
 */
class EmployeeAnnualLeaveController extends Controller
{
    public function __construct(
        Requester $requester, 
        EmployeeAnnualLeaveDao $employeeAnnualLeaveDao,
        LeaveDao $leaveDao)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->employeeLeaveDao = $employeeAnnualLeaveDao;
        $this->leaveDao = $leaveDao;
    }

    /**
     * Get all Leave
     * @param request
     */
    public function getAll(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required|string'
        ]);

        $data = (object)[];
        $data->annualLeave = $this->leaveDao->getAnnualLeave();
        $data->listLeaveQuota = $this->employeeLeaveDao->getListEmployeeLeave($request->employeeId);

        $response = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($response);
    }

    public function getLeaveEmployee(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required|integer',
            'employeeId' => 'required|string'
        ]);

        $data = $this->employeeLeaveDao->getLeaveEmployee($request->employeeId);

        $response = new AppResponse($data, trans('messages.allDataRetrieved'));
        return $this->renderResponse($response);
    }


}
