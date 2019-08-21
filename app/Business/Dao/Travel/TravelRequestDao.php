<?php

namespace App\Business\Dao\Travel;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TravelRequestDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_travel';
        $this->requester = $requester;
    }

    /**
     * Get one Travel Request based on employee id and date
     * @param  $employeeId,$date
     */
    public function getOneTravel($employeeId,$date)
    {
        return
            DB::connection($this->connection)
                ->table('travel_requests')
                ->select(
                    'travel_requests.id',
                    'travel_requests.code',
                    'travel_requests.employee_id as employeeId',
                    'travel_requests.depart_date as departDate',
                    'travel_requests.return_date as returnDate',
                    'travel_requests.status'
                )
                ->where([
                    ['travel_requests.tenant_id', $this->requester->getTenantId()],
                    ['travel_requests.company_id', $this->requester->getCompanyId()],
                    ['travel_requests.employee_id', $employeeId],
                    ['travel_requests.status', 'A'],
                    ['travel_requests.depart_date', '<=', $date],
                    ['travel_requests.return_date', '>=', $date]
                ])
                ->first();
    }

}
