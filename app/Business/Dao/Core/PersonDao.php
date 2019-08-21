<?php

namespace App\Business\Dao\Core;

use App\Business\Model\Requester;
use App\Business\Helper\SearchQueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property string connection
 * @property Requester requester
 * @property mixed fieldMap
 */
class PersonDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_core';
        $this->requester = $requester;
        $this->fieldMap = config('constant.fieldMap');
    }

    /**
     * Get person based on employee id
     * @param employeeId
     * @return mixed
     */
    public function getOneEmployee($employeeId)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        return
            DB::connection($this->connection)
                ->table('persons')
                ->selectRaw(
                    'persons.id,' .
                    'persons.first_name as "firstName",' .
                    'persons.last_name as "lastName",' .
                    'persons.mobile,' .
                    'persons.email,' .
                    'persons.eff_begin as "effBegin",' .
                    'age(persons.eff_begin) as "workLength",' .
                    'person_types.val_data as "personType",' .
                    'persons.lov_ptyp as "lovPtyp",' .
                    'persons.lov_rlgn as "lovRlgn",' .
                    'persons.lov_mars as "lovMars",' .
                    'persons.file_photo as "filePhoto",' .
                    'supervisors.id as "supervisorId",' .
                    'supervisors.first_name as "supervisorFirstName",' .
                    'supervisors.last_name as "supervisorLastName",' .
                    'supervisors.file_photo as "supervisorPhoto",' .
                    'supervisor_positions.name as "supervisorPosition",' .
                    'assignments.employee_id as "employeeId",' .
                    'assignments.eff_begin as "assignBegin",' .
                    'assignments.eff_end as "assignEnd",' .
                    'assignments.lov_acty as "lovActy",' .
                    'assignments.employee_id as "employeeId",' .
                    'assignments.grade_code as "gradeCode",' .
                    'employee_statuses.code as "employeeStatusCode",' .
                    'employee_statuses.name as "employeeStatusName",' .
                    'employee_statuses.working_month as "workingMonth",' .
                    'positions.code as "positionCode",' .
                    'positions.name as "positionName",' .
                    'units.code as "unitCode",' .
                    'units.name as "unitName",' .
                    'jobs.code as "jobCode",' .
                    'jobs.name as "jobName",' .
                    'locations.code as "locationCode",' .
                    'locations.name as "locationName",' .
                    'countries.code as "countryCode"'
                )
                ->leftJoin('lovs as person_types', function ($join) use ($companyId, $tenantId) {
                    $join->on('person_types.key_data', '=', 'persons.lov_ptyp')
                        ->where([
                            ['person_types.lov_type_code', 'PTYP'],
                            ['person_types.tenant_id', $tenantId],
                            ['person_types.company_id', $companyId]
                        ]);
                })
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('assignments.person_id', '=', 'persons.id')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->leftJoin('persons as supervisors', function ($join) use ($companyId, $tenantId) {
                    $join->on('supervisors.id', '=', 'assignments.supervisor_id')
                        ->where([
                            ['supervisors.tenant_id', $tenantId]
                        ])
                        ->orderBy('persons.eff_end', 'desc');
                })
                ->leftjoin('assignments as supervisor_assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('supervisor_assignments.person_id', '=', 'supervisors.id')
                        ->where([
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['supervisor_assignments.tenant_id', $tenantId],
                            ['supervisor_assignments.company_id', $companyId]
                        ]);
                })
                ->leftjoin('positions as supervisor_positions', function ($join) use ($companyId, $tenantId) {
                    $join->on('supervisor_positions.code', '=', 'supervisor_assignments.position_code')
                        ->where([
                            ['supervisor_positions.tenant_id', $tenantId],
                            ['supervisor_positions.company_id', $companyId]
                        ]);
                })
                ->leftJoin('employee_statuses', function ($join) use ($companyId, $tenantId) {
                    $join->on('employee_statuses.code', '=', 'assignments.employee_status_code')
                        ->where([
                            ['employee_statuses.tenant_id', $tenantId],
                            ['employee_statuses.company_id', $companyId]
                        ]);
                })
                ->leftjoin('positions', function ($join) use ($companyId, $tenantId) {
                    $join->on('positions.code', '=', 'assignments.position_code')
                        ->where([
                            ['positions.tenant_id', $tenantId],
                            ['positions.company_id', $companyId]
                        ]);
                })
                ->leftjoin('locations', function ($join) use ($companyId, $tenantId) {
                    $join->on('locations.code', '=', 'assignments.location_code')
                        ->where([
                            ['locations.tenant_id', $tenantId],
                            ['locations.company_id', $companyId]
                        ]);
                })
                ->leftjoin('units', function ($join) use ($companyId, $tenantId) {
                    $join->on('units.code', '=', 'assignments.unit_code')
                        ->where([
                            ['units.tenant_id', $tenantId],
                            ['units.company_id', $companyId]
                        ]);
                })
                ->leftjoin('jobs', function ($join) use ($companyId, $tenantId) {
                    $join->on('jobs.code', 'assignments.job_code')
                        ->where([
                            ['jobs.tenant_id', $tenantId],
                            ['jobs.company_id', $companyId]
                        ]);
                })
                ->leftjoin('countries', function ($join) use ($companyId, $tenantId) {
                    $join->on('countries.code', '=', 'persons.country_code')
                        ->where([
                            ['countries.tenant_id', $tenantId],
                            ['countries.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['persons.tenant_id', $tenantId],
                    ['assignments.employee_id', $employeeId]
                ])
                ->orderBy('persons.eff_end', 'desc')
                ->first();

    }

    /**
     * Get person based on search
     * @param employeeId
     * @return mixed
     */
    public function searchEmployee($query)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        $search = strtolower("%$query%");

        return
            DB::connection($this->connection)
                ->table('persons')
                ->selectRaw(
                    'persons.first_name as "firstName",' .
                    'persons.last_name as "lastName",' .
                    'assignments.employee_id as "employeeId",' .
                    'positions.name as "positionName"'
                )
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('assignments.person_id', '=', 'persons.id')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->leftjoin('positions', function ($join) use ($companyId, $tenantId) {
                    $join->on('positions.code', '=', 'assignments.position_code')
                        ->where([
                            ['positions.tenant_id', $tenantId],
                            ['positions.company_id', $companyId]
                        ]);
                })
                ->leftjoin('units', function ($join) use ($companyId, $tenantId) {
                    $join->on('units.code', '=', 'assignments.unit_code')
                        ->where([
                            ['units.tenant_id', $tenantId],
                            ['units.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['persons.tenant_id', $tenantId],
                ])
                ->whereRaw('LOWER(persons.first_name) like ?', [$search])
                ->orWhereRaw('LOWER(persons.last_name) like ?', [$search])
                ->orWhereRaw('LOWER(assignments.employee_id) like ?', [$search])

                ->orderBy('persons.eff_end', 'desc')
                ->first();
    }


    /**
     * Get person based on search
     * @param $query
     * @return mixed
     */
    public function searchEmployees($query)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        $search = strtolower("%$query%");

        return
            DB::connection($this->connection)
                ->table('persons')
                ->selectRaw(
                    'persons.first_name as "firstName",' .
                    'persons.last_name as "lastName",' .
                    'assignments.employee_id as "employeeId",' .
                    'positions.name as "positionName"'
                )
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('assignments.person_id', '=', 'persons.id')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->leftjoin('positions', function ($join) use ($companyId, $tenantId) {
                    $join->on('positions.code', '=', 'assignments.position_code')
                        ->where([
                            ['positions.tenant_id', $tenantId],
                            ['positions.company_id', $companyId]
                        ]);
                })
                ->leftjoin('units', function ($join) use ($companyId, $tenantId) {
                    $join->on('units.code', '=', 'assignments.unit_code')
                        ->where([
                            ['units.tenant_id', $tenantId],
                            ['units.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['persons.tenant_id', $tenantId],
                ])
                ->whereRaw('LOWER(persons.first_name) like ?', [$search])
                ->orWhereRaw('LOWER(persons.last_name) like ?', [$search])
                ->orWhereRaw('LOWER(assignments.employee_id) like ?', [$search])

                ->orderBy('persons.eff_end', 'desc')
                ->get();
    }

    /**
     * Get all employee
     * @param 
     * @return mixed
     */
    public function getAllEmployeeByCompany($companyId)
    {
        $now = Carbon::now();
        return
            DB::connection($this->connection)
                ->table('assignments')
                ->select(
                    'assignments.employee_id as employeeId',
                    'assignments.company_id as companyId',
                    'assignments.tenant_id as tenantId'
                )
                ->join('person', function ($join) use ($companyId) {
                    $join->on('person.id', '=', 'assignments.person_id')
                        ->where([
                            ['person.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['assignments.company_id', '=', $companyId],
                    ['assignments.eff_begin', '<=', $now],
                    ['assignments.eff_end', '>=', $now],
                    ['assignments.lov_acty', '!=', 'TERM'],
                ])
                ->distinct()
                ->get();

    }

    public function getWorkingMonth($personId)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();

        return
            DB::connection($this->connection)
            ->select( DB::raw(
                "SELECT (
                    (DATE_PART('year', now()) - DATE_PART('year', eff_begin)) * 12 + 
                    (DATE_PART('month', now()) - DATE_PART('month', eff_begin))
                    ) as wkmon
                FROM (	SELECT eff_begin 
                        FROM assignments
                        WHERE tenant_id=:tenantId 
                            AND company_id=:companyId
                            AND person_id=:personId
                            AND lov_acty='HIRE'
                            AND is_primary=true
                    ) as firstAs;"
            ), array(
                'tenantId' => $tenantId,
                'companyId' => $companyId,
                'personId' => $personId, ));
    }


    /**
     * Get person based on employee id
     * @param employeeId
     * @return mixed
     */
    public function getEmployeeLookupData($employeeId)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        return
            DB::connection($this->connection)
                ->table('persons')
                ->selectRaw(
                    'persons.id as "ID",' .
                    'locations.code as "LOC",' .
                    'units.code as "UNI",' .
                    'jobs.code as "JOB",' .
                    'employee_statuses.code as "EMSTAT",' .
                    'positions.code as "POS",' .
                    'assignments.grade_code as "GRD"'
                )
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now, $employeeId) {
                    $join->on('assignments.person_id', '=', 'persons.id')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.employee_id', $employeeId],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->leftJoin('employee_statuses', function ($join) use ($companyId, $tenantId) {
                    $join->on('employee_statuses.code', '=', 'assignments.employee_status_code')
                        ->where([
                            ['employee_statuses.tenant_id', $tenantId],
                            ['employee_statuses.company_id', $companyId]
                        ]);
                })
                ->leftjoin('positions', function ($join) use ($companyId, $tenantId) {
                    $join->on('positions.code', '=', 'assignments.position_code')
                        ->where([
                            ['positions.tenant_id', $tenantId],
                            ['positions.company_id', $companyId]
                        ]);
                })
                ->leftjoin('locations', function ($join) use ($companyId, $tenantId) {
                    $join->on('locations.code', '=', 'assignments.location_code')
                        ->where([
                            ['locations.tenant_id', $tenantId],
                            ['locations.company_id', $companyId]
                        ]);
                })
                ->leftjoin('units', function ($join) use ($companyId, $tenantId) {
                    $join->on('units.code', '=', 'assignments.unit_code')
                        ->where([
                            ['units.tenant_id', $tenantId],
                            ['units.company_id', $companyId]
                        ]);
                })
                ->leftjoin('jobs', function ($join) use ($companyId, $tenantId) {
                    $join->on('jobs.code', 'assignments.job_code')
                        ->where([
                            ['jobs.tenant_id', $tenantId],
                            ['jobs.company_id', $companyId]
                        ]);
                })
                ->where('persons.tenant_id', $tenantId)
                ->orderBy('persons.eff_end', 'desc')
                ->first();

    }

    /**
     * Get person based on employee ids
     * @param employeeIds
     * @return mixed
     */
    public function getManyEmployee($employeeIds)
    {
        $companyId = $this->requester->getCompanyId();
        $tenantId = $this->requester->getTenantId();
        $now = Carbon::now();
        return
            DB::connection($this->connection)
                ->table('persons')
                ->selectRaw(
                    'persons.id,' .
                    'persons.first_name as "firstName",' .
                    'assignments.employee_id as "employeeId",' .
                    'persons.last_name as "lastName"'
                )
                ->leftJoin('lovs as person_types', function ($join) use ($companyId, $tenantId) {
                    $join->on('person_types.key_data', '=', 'persons.lov_ptyp')
                        ->where([
                            ['person_types.lov_type_code', 'PTYP'],
                            ['person_types.tenant_id', $tenantId],
                            ['person_types.company_id', $companyId]
                        ]);
                })
                ->leftJoin('assignments', function ($join) use ($companyId, $tenantId, $now) {
                    $join->on('assignments.person_id', '=', 'persons.id')
                        ->where([
                            ['assignments.is_primary', true],
                            ['assignments.eff_begin', '<=', $now],
                            ['assignments.eff_end', '>=', $now],
                            ['assignments.tenant_id', $tenantId],
                            ['assignments.company_id', $companyId]
                        ]);
                })
                ->where([
                    ['persons.tenant_id', $tenantId],
                ])
                ->orderBy('persons.eff_end', 'desc')
                ->whereIn('assignments.employee_id', $employeeIds)
                ->get();

    }


    public function advancedSearch($menuCode, $searchData, $offset, $limit, $order, $orderDirection)
    {
        $companyId = $this->requester->getCompanyId();

        $builder = new SearchQueryBuilder($searchData, $this->fieldMap);
        $builder = $builder->table('persons', $this->connection)
            ->distinctOn('persons.id')
            ->select('persons.id', 'assignments.employee_id as "employeeId"', 'persons.eff_begin')
            ->join('v_person_lovs', 'v_person_lovs.person_id', '=', 'persons.id')
            ->join('assignments', 'assignments.person_id', '=', 'persons.id')
            ->leftJoin('person_families', 'person_families.person_id', '=', 'persons.id')
            ->join('countries', 'countries.id', '=', 'persons.country_code')
            ->leftJoin('person_languages', 'person_languages.person_id', '=', 'persons.id')
            ->leftJoin('lovs as languages', function ($join) {
                $join->on('languages.key_data', '=', 'person_languages.lov_lang')
                    ->where('languages.lov_type_code', 'LANG');
            })
            ->leftJoin('lovs as blood_types', function ($join) {
                $join->on('blood_types.key_data', '=', 'persons.lov_blod')
                    ->where('blood_types.lov_type_code', 'BLOD');
            })
            ->join('lovs as genders', 'genders.key_data', '=', 'persons.lov_gndr')
            ->join('lovs as religions', 'religions.key_data', '=', 'persons.lov_rlgn')
            ->join('lovs as marital_statuses', 'marital_statuses.key_data', '=', 'persons.lov_mars')
            ->join('lovs as assignment_statuses', 'assignment_statuses.key_data', '=', 'assignments.lov_asta')
            ->join('locations', 'assignments.location_code', '=', 'locations.code')
            ->join('units', 'assignments.unit_code', '=', 'units.code')
            ->join('jobs', 'assignments.job_code', '=', 'jobs.code')
            ->join('positions', 'assignments.position_code', '=', 'positions.code')
            ->leftJoin('employee_statuses', 'assignments.employee_status_code', '=', 'employee_statuses.code')
            ->leftJoin('grades', 'assignments.grade_code', '=', 'grades.code')
            ->leftJoin('cost_centers', 'assignments.cost_center_code', '=', 'cost_centers.code')
            ->leftJoin('persons as supervisors', 'supervisors.id', '=', 'assignments.supervisor_id')
            ->where(function ($query) use ($companyId) {
                $query->where('v_person_lovs.company_id', $companyId)
                    ->orWhereNull('v_person_lovs.company_id');
            })
            ->combineWhere([
                ['persons.tenant_id', $this->requester->getTenantId()],
                ['v_person_lovs.user_id', $this->requester->getUserId()],
                ['v_person_lovs.menu_code', $menuCode],
                ['genders.lov_type_code', 'GNDR'],
                ['religions.lov_type_code', 'RLGN'],
                ['marital_statuses.lov_type_code', 'MARS'],
                ['assignment_statuses.lov_type_code', 'ASTA']
            ]);

        $count = $builder->count();

        if ($order && $orderDirection) {
            $result = $builder->orderBy($order, $orderDirection)
                ->limit($limit)->offset($offset)->hist('persons.id', 'persons.eff_begin');
        } else {
            $result = $builder->limit($limit)->offset($offset)->hist('persons.id', 'persons.eff_begin');
        }

        return [$result, $count];
    }
}
