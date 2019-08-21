<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RequestRawTimeSheetDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getAll($offset, $limit)
    {
        return
            DB::table('request_raw_timesheets')
                ->select(
                    'id',
                    'date',
                    'employee_id as employeeId',
                    'time_out as timeOut',
                    'time_in as timeIn',
                    'project_code as projectCode',
                    'time_out_lat as timeOutLat',
                    'time_out_long as timeOutLong',
                    'time_in_lat as timeInLat',
                    'time_in_long as timeInLong',
                    'value_1 as value1',
                    'value_2 as value2',
                    'activity_code as activityCode',
                    'description',
                    'description_2 as description2',
                    'status'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->orderBy('date', 'asc')
                ->orderBy('time_in', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();
    }

    public function getAllByEmployeeId($offset, $limit, $employeeId)
    {
        return
            DB::table('request_raw_timesheets')
                ->select(
                    'id',
                    'date',
                    'employee_id as employeeId',
                    'time_out as timeOut',
                    'time_in as timeIn',
                    'project_code as projectCode',
                    'time_out_lat as timeOutLat',
                    'time_out_long as timeOutLong',
                    'time_in_lat as timeInLat',
                    'time_in_long as timeInLong',
                    'value_1 as value1',
                    'value_2 as value2',
                    'activity_code as activityCode',
                    'description',
                    'description_2 as description2',
                    'status'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['employee_id', $employeeId],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
    }

    public function getLatestFive($employeeId)
    {
        return
            DB::table('request_raw_timesheets')
                ->select(
                    'id',
                    'date',
                    'employee_id as employeeId',
                    'time_out as timeOut',
                    'time_in as timeIn',
                    'project_code as projectCode',
                    'time_out_lat as timeOutLat',
                    'time_out_long as timeOutLong',
                    'time_in_lat as timeInLat',
                    'time_in_long as timeInLong',
                    'value_1 as value1',
                    'value_2 as value2',
                    'activity_code as activityCode',
                    'description',
                    'description_2 as description2',
                    'status'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['employee_id', $employeeId]
                ])
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'desc')
                ->limit(5)
                ->get();
    }

    /**
     * search attendance requests in ONE company
     * @param
     */
    public function search($companyId, $offset, $limit, $query, $status, $dateStart, $dateEnd)
    {
        $tenantId = $this->requester->getTenantId();
        if ($query) {
            $search = strtolower("%$query%");
        } else {
            $search = "%";
        }

        $querySQL =
            DB::table('request_raw_timesheets')
                ->select(
                    'request_raw_timesheets.id',
                    'request_raw_timesheets.date',
                    'request_raw_timesheets.employee_id as employeeId',
                    'request_raw_timesheets.time_out as timeOut',
                    'request_raw_timesheets.time_in as timeIn',
                    'request_raw_timesheets.project_code as projectCode',
                    'request_raw_timesheets.time_out_lat as timeOutLat',
                    'request_raw_timesheets.time_out_long as timeOutLong',
                    'request_raw_timesheets.time_in_lat as timeInLat',
                    'request_raw_timesheets.time_in_long as timeInLong',
                    'request_raw_timesheets.value_1 as value1',
                    'request_raw_timesheets.value_2 as value2',
                    'request_raw_timesheets.activity_code as activityCode',
                    'worksheet_activities.name as activityName',
                    'worksheet_activities.description as activityDescription',
                    'request_raw_timesheets.description',
                    'request_raw_timesheets.description_2 as description2',
                    'request_raw_timesheets.status'
                )
                ->leftJoin('worksheet_activities', function ($join) use ($companyId, $tenantId) {
                    $join->on('worksheet_activities.code', '=', 'request_raw_timesheets.activity_code')
                        ->where([
                            ['worksheet_activities.tenant_id', $tenantId],
                            ['worksheet_activities.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['request_raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['request_raw_timesheets.company_id', $companyId]
                ])
                ->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(request_raw_timesheets.project_code) like ?', [$search]);
                    $query->orWhereRaw('LOWER(request_raw_timesheets.employee_id) like ?', [$search]);
                    $query->orWhereRaw('LOWER(request_raw_timesheets.activity_code) like ?', [$search]);
                    $query->orWhereRaw('LOWER(worksheet_activities.name) like ?', [$search]);
                })
                ->orderBy('request_raw_timesheets.date', 'asc')
                ->orderBy('request_raw_timesheets.time_in', 'asc')
                ->offset($offset)
                ->limit($limit);

        if ($status) {
            $querySQL->where('request_raw_timesheets.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('request_raw_timesheets.date', '>=', $dateStart);
            $querySQL->where('request_raw_timesheets.date', '<=', $dateEnd);
        }

        return $querySQL->get();

    }

    /**
     * search attendance requests in ONE company
     * @param
     */
    public function searchEmpIds($companyId, $offset, $limit, $empIds, $status, $dateStart, $dateEnd)
    {
        $tenantId = $this->requester->getTenantId();

        $querySQL =
            DB::table('request_raw_timesheets')
                ->select(
                    'request_raw_timesheets.id',
                    'request_raw_timesheets.date',
                    'request_raw_timesheets.employee_id as employeeId',
                    'request_raw_timesheets.time_out as timeOut',
                    'request_raw_timesheets.time_in as timeIn',
                    'request_raw_timesheets.project_code as projectCode',
                    'request_raw_timesheets.time_out_lat as timeOutLat',
                    'request_raw_timesheets.time_out_long as timeOutLong',
                    'request_raw_timesheets.time_in_lat as timeInLat',
                    'request_raw_timesheets.time_in_long as timeInLong',
                    'request_raw_timesheets.value_1 as value1',
                    'request_raw_timesheets.value_2 as value2',
                    'request_raw_timesheets.activity_code as activityCode',
                    'worksheet_activities.name as activityName',
                    'worksheet_activities.description as activityDescription',
                    'request_raw_timesheets.description',
                    'request_raw_timesheets.description_2 as description2',
                    'request_raw_timesheets.status'
                )
                ->leftJoin('worksheet_activities', function ($join) use ($companyId, $tenantId) {
                    $join->on('worksheet_activities.code', '=', 'request_raw_timesheets.activity_code')
                        ->where([
                            ['worksheet_activities.tenant_id', $tenantId],
                            ['worksheet_activities.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['request_raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['request_raw_timesheets.company_id', $companyId]
                ])
                ->whereIn('request_raw_timesheets.employee_id', $empIds)
                ->orderBy('request_raw_timesheets.date', 'asc')
                ->orderBy('request_raw_timesheets.time_in', 'asc')
                ->offset($offset)
                ->limit($limit);

        if ($status) {
            $querySQL->where('request_raw_timesheets.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('request_raw_timesheets.date', '>=', $dateStart);
            $querySQL->where('request_raw_timesheets.date', '<=', $dateEnd);
        }

        return $querySQL->get();

    }

    public function getOne($id)
    {
        return
            DB::table('request_raw_timesheets')
                ->select(
                    'id',
                    'date',
                    'employee_id as employeeId',
                    'time_out as timeOut',
                    'time_in as timeIn',
                    'project_code as projectCode',
                    'time_out_lat as timeOutLat',
                    'time_out_long as timeOutLong',
                    'time_in_lat as timeInLat',
                    'time_in_long as timeInLong',
                    'value_1 as value1',
                    'value_2 as value2',
                    'activity_code as activityCode',
                    'description',
                    'description_2 as description2',
                    'status'
                )->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()],
                    ['id', $id]
                ])->first();
    }

    public function save($obj)
    {
        return DB::table('request_raw_timesheets')->insertGetId($obj);
    }

    public function update($id, $obj)
    {
        DB::table('request_raw_timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->update($obj);
    }

    public function delete($id)
    {
        DB::table('request_raw_timesheets')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $id]
            ])
            ->delete();
    }

    /**
     * Get all attendance requests in ONE company
     * @param
     */
    public function getCount($companyId)
    {
        return
            DB::table('request_raw_timesheets')
                ->select(
                    'id'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $companyId]
                ])
                ->get();
    }

    /**
     * Count all attendance requests by employee id in ONE company
     * @param
     */
    public function getCountEmployeeId($companyId, $employeeId, $status, $date)
    {
        $querySQL =
            DB::table('request_raw_timesheets')
                ->select(
                    'id'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['employee_id', $employeeId],
                    ['company_id', $companyId]
                ]);

        if ($date) {
            $querySQL->where('request_raw_timesheets.date', '=', $date);
        }

        if ($status) {
            $querySQL->where('request_raw_timesheets.status', 'like', $status);
        }

        return $querySQL->get();
    }

    /**
     * Count all attendance requests by project code in ONE company
     * @param
     */
    public function getCountProject($companyId, $projectCode, $status, $dateStart, $dateEnd)
    {
        $querySQL =
            DB::table('request_raw_timesheets')
                ->select(
                    'id'
                )
                ->where([
                    ['tenant_id', $this->requester->getTenantId()],
                    ['project_code', $projectCode],
                    ['company_id', $companyId]
                ]);
        if ($dateStart && $dateEnd) {
            $querySQL->where('request_raw_timesheets.date', '>=', $dateStart);
            $querySQL->where('request_raw_timesheets.date', '<=', $dateEnd);
        }

        if ($status) {
            $querySQL->where('request_raw_timesheets.status', 'like', $status);
        }

        return $querySQL->get();
    }

    /**
     * Count all attendance requests by activity in ONE company
     * @param
     */
    public function getCountSearch($companyId, $search, $status, $dateStart, $dateEnd)
    {
        if ($search) {
            $search = strtolower("%$search%");
        } else {
            $search = "%";
        }

        $tenantId = $this->requester->getTenantId();

        $querySQL =
            DB::table('request_raw_timesheets')
                ->select(
                    'request_raw_timesheets.id'
                )
                ->leftJoin('worksheet_activities', function ($join) use ($companyId, $tenantId) {
                    $join->on('worksheet_activities.code', '=', 'request_raw_timesheets.activity_code')
                        ->where([
                            ['worksheet_activities.tenant_id', $tenantId],
                            ['worksheet_activities.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['request_raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['request_raw_timesheets.company_id', $companyId]
                ])
                ->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(request_raw_timesheets.activity_code) like ?', [$search]);
                    $query->orWhereRaw('LOWER(worksheet_activities.name) like ?', [$search]);
                });

        if ($status) {
            $querySQL->where('request_raw_timesheets.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('request_raw_timesheets.date', '>=', $dateStart);
            $querySQL->where('request_raw_timesheets.date', '<=', $dateEnd);
        }

        return $querySQL->get();
    }

    /**
     * Count all attendance requests by activity in ONE company
     * @param
     */
    public function getCountSearchEmpIds($companyId, $empIds, $status, $dateStart, $dateEnd)
    {
        $tenantId = $this->requester->getTenantId();

        $querySQL =
            DB::table('request_raw_timesheets')
                ->select(
                    'request_raw_timesheets.id'
                )
                ->leftJoin('worksheet_activities', function ($join) use ($companyId, $tenantId) {
                    $join->on('worksheet_activities.code', '=', 'request_raw_timesheets.activity_code')
                        ->where([
                            ['worksheet_activities.tenant_id', $tenantId],
                            ['worksheet_activities.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['request_raw_timesheets.tenant_id', $this->requester->getTenantId()],
                    ['request_raw_timesheets.company_id', $companyId]
                ])
                ->whereIn('request_raw_timesheets.employee_id', $empIds);

        if ($status) {
            $querySQL->where('request_raw_timesheets.status', 'like', $status);
        }

        if ($dateStart && $dateEnd) {
            $querySQL->where('request_raw_timesheets.date', '>=', $dateStart);
            $querySQL->where('request_raw_timesheets.date', '<=', $dateEnd);
        }

        return $querySQL->get();
    }
}
