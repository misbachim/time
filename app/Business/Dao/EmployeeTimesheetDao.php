<?php
namespace App\Business\Dao;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeTimesheetDao 
{
	private $requester;

	/**
	 * [__construct description]
	 * @param Requester $requester [description]
	 */
	public function __construct(
				Requester $requester) {
        $this->requester = $requester;
    }


    /**
     * [advancedSearch]
     * @param  [type] $employeeIds    [description]
     * @param  [type] $offset         [description]
     * @param  [type] $limit          [description]
     * @param  [type] $order          [description]
     * @param  [type] $orderDirection [description]
     * @return [type]                 [description]
     */
    public function advancedSearch($employeeId, $startDate, $endDate, $booisflexy, $offset, $limit, $order, $orderDirection)
    {
    	$now = Carbon::now();

    	$query = DB::table('timesheets')
    			->select(
    				'tenant_id as tenantId',
				    'company_id as companyId',
					'employee_id as employeeId',
				    'date as date',
				    'schedule_time_in as scheduleTimeIn',
				    'time_in as timeIn',
				    'time_in_deviation as deviationTimeIn',
				    'schedule_time_out as scheduleTimeOut',
				    'time_out as timeOut',
				    'time_out_deviation as deviationTimeOut',
				    'schedule_duration as scheduleDuration',
				    'duration as duration',
				    'duration_deviation as deviationDuration',
					'attendance_code as attendanceCode',
					'leave_code as leaveCode',
					'leave_weight as leaveWeight',
					'overtime as overtime',
					'process_code as processCode',
					'is_work_day as isWorkDay',
					'is_flexy_hour as isFlexyHour'
    			)
    			->where([
    				['timesheets.tenant_id', $this->requester->getTenantId()],
                	['timesheets.company_id', $this->requester->getCompanyId()],
                	['timesheets.employee_id', $employeeId],
                	['timesheets.is_flexy_hour', $booisflexy],
                	['timesheets.date', '>=', $startDate],
                	['timesheets.date', '<=', $endDate],
    			]);

        if ($order && $orderDirection) {
            $query->orderBy($order, $orderDirection);
        }

        $totalRows = (clone $query)->count();

        $results = $query->limit($limit)->offset($offset)->get();

        return [$results, $totalRows];
    }
}