<?php

use App\Business\Dao\LeaveRequestDao;
use App\Business\Dao\LeaveRequestDetailDao;
use App\Business\Dao\LogScheduleDao;
use App\Business\Dao\LogSchedulePatternDao;
use App\Business\Dao\OvertimeRequestDao;
use Carbon\Carbon;
use App\Business\Dao\TimeSheetDao;
use App\Business\Dao\RawTimeSheetDao;
use App\Business\Dao\TimeGroupDao;
use App\Business\Helper\StringHelper;
use Illuminate\Support\Facades\DB;

class TimeSheetTest extends TestCase {
    use Testable;

    private $timeSheetDao;
    private $rawTimeSheetDao;
    private $timeGroupDao;
    private $logScheduleDao;
    private $logSchedulePatternDao;
    private $leaveRequestDao;
    private $leaveRequestDetailDao;
    private $overtimeRequestDao;
    private $dates;
    private $timeSheets;
    private $timeSheetsT;
    private $rawTimeSheets;
    private $rawTimeSheetsT;

    public function setUp()
    {
        parent::setUp();
        $this->timeSheetDao = new TimeSheetDao($this->getRequester());
        $this->rawTimeSheetDao = new RawTimeSheetDao($this->getRequester());
        $this->timeGroupDao = new TimeGroupDao($this->getRequester());
        $this->logScheduleDao = new LogScheduleDao($this->getRequester());
        $this->logSchedulePatternDao = new LogSchedulePatternDao($this->getRequester());
        $this->leaveRequestDao = new LeaveRequestDao($this->getRequester());
        $this->leaveRequestDetailDao = new LeaveRequestDetailDao($this->getRequester());
        $this->overtimeRequestDao = new OvertimeRequestDao($this->getRequester());

        $this->dates = [];
        $lastDate = Carbon::today();
        for ($i = 0; $i < 5; $i++) {
            $lastDate = (clone $lastDate)->addDay();
            array_push($this->dates, $lastDate);
        }

        $timeSheet = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'person_id' => 1,
            'is_work_day' => true,
            'is_flexy_hour' => true,
            'time_in_deviation' => '00:00:00',
            'time_out_deviation' => '00:00:00',
            'schedule_duration' => '01:00:00',
            'duration' => '00:59:00',
            'duration_deviation' => '00:01:00',
            'attendance_code' => null,
            'leave_code' => null,
            'leave_weight' => "0.01",
            'overtime' => '00:00:00'
        ];

        $this->timeSheets = [];
        $this->timeSheetsT = [];
        for ($i = 0; $i < 5; $i++) {
            $timeSheet['date'] = $this->dates[$i]->toDateString();
            $timeSheet['schedule_time_in'] = $this->dates[$i]->toDateTimeString();
            $timeSheet['time_in'] = $this->dates[$i]->toDateTimeString();
            $timeSheet['schedule_time_out'] = $this->dates[$i]->toDateTimeString();
            $timeSheet['time_out'] = $this->dates[$i]->toDateTimeString();

            $this->timeSheetDao->save($timeSheet);
            $this->seeInDatabase('timesheets', $timeSheet);
            array_push($this->timeSheets, $timeSheet);

            $timeSheetT = $this->transform($timeSheet);
            array_push($this->timeSheetsT, $timeSheetT);
        }

        $rawTimeSheet = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'person_id' => 1,
            'is_analyzed' => true
        ];

        $this->rawTimeSheets = [];
        $this->rawTimeSheetsT = [];
        for ($i = 0; $i < 5; $i++) {
            $rawTimeSheet['date'] = $this->dates[$i]->toDateString();
            $rawTimeSheet['time_in'] = (clone $this->dates[$i])->second(28800)->toDateTimeString();
            $rawTimeSheet['time_out'] = (clone $this->dates[$i])->second(61200)->toDateTimeString();

            $this->rawTimeSheetDao->save($rawTimeSheet);
            $this->seeInDatabase('raw_timesheets', $rawTimeSheet);
            array_push($this->rawTimeSheets, $rawTimeSheet);

            $rawTimeSheetT = $this->transform($rawTimeSheet);
            array_push($this->rawTimeSheetsT, $rawTimeSheetT);
        }
    }

    public function testGetAll()
    {
        $this->timeSheetsT = $this->exclude($this->timeSheetsT, [
            'tenantId',
            'companyId',
        ]);

        $this->json('POST', '/timeSheet/getAll', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'pageInfo' => [
                'pageLimit' => 15,
                'pageNo' => 1
            ]
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.allDataRetrieved'),
            'pageInfo' => [
                'totalRows' => 5,
                'pageLimit' => 15,
                'pageNo' => 1,
                'totalPages' => 1
            ]
        ]);

        foreach ($this->timeSheetsT as $timeSheetT) {
            foreach ($timeSheetT as $field => $value) {
                $this->seeJson([$field => $value]);
            }
        }
    }

    public function testGetAllRaw()
    {
        $this->rawTimeSheetsT = $this->exclude($this->rawTimeSheetsT, [
            'tenantId',
            'companyId',
        ]);

        $this->json('POST', '/timeSheet/getAllRaw', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'pageInfo' => [
                'pageLimit' => 15,
                'pageNo' => 1
            ]
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.allDataRetrieved'),
            'pageInfo' => [
                'totalRows' => 5,
                'pageLimit' => 15,
                'pageNo' => 1,
                'totalPages' => 1
            ]
        ]);

        foreach ($this->rawTimeSheetsT as $rawTimeSheetT) {
            foreach ($rawTimeSheetT as $field => $value) {
                $this->seeJson([$field => $value]);
            }
        }
    }

    public function testGetAllByPerson()
    {
        $this->timeSheetsT = $this->exclude($this->timeSheetsT, [
            'tenantId',
            'companyId',
            'personId'
        ]);

        $this->json('POST', '/timeSheet/getAllByPerson', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'personId' => $this->timeSheets[0]['person_id']
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.allDataRetrieved')
        ]);

        foreach ($this->timeSheetsT as $timeSheetT) {
            foreach ($timeSheetT as $field => $value) {
                $this->seeJson([$field => $value]);
            }
        }
    }

    public function testGetAllRawByPerson()
    {
        $this->rawTimeSheetsT = $this->exclude($this->rawTimeSheetsT, [
            'tenantId',
            'companyId',
            'personId'
        ]);

        $this->json('POST', '/timeSheet/getAllRawByPerson', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'personId' => $this->rawTimeSheets[0]['person_id']
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.allDataRetrieved')
        ]);

        foreach ($this->rawTimeSheetsT as $rawTimeSheetT) {
            foreach ($rawTimeSheetT as $field => $value) {
                $this->seeJson([$field => $value]);
            }
        }
    }

    public function testGetOne()
    {
        $this->timeSheetsT = $this->exclude($this->timeSheetsT, [
            'tenantId',
            'companyId',
            'personId',
            'date'
        ]);

        $this->json('POST', '/timeSheet/getOne', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'personId' => $this->timeSheets[0]['person_id'],
            'date' => $this->timeSheets[0]['date']
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.dataRetrieved')
        ]);

        foreach ($this->timeSheetsT[0] as $field => $value) {
            $this->seeJson([$field => $value]);
        }
    }

    public function testGetOneRaw()
    {
        $this->rawTimeSheetsT = $this->exclude($this->rawTimeSheetsT, [
            'tenantId',
            'companyId',
            'personId',
            'date'
        ]);

        $this->json('POST', '/timeSheet/getOneRaw', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'personId' => $this->rawTimeSheets[0]['person_id'],
            'date' => $this->rawTimeSheets[0]['date']
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.dataRetrieved')
        ]);

        foreach ($this->rawTimeSheetsT[0] as $field => $value) {
            $this->seeJson([$field => $value]);
        }
    }

    public function testSaveRaw()
    {
        $rawTimeSheet = $this->newRawTimeSheet();
        $rawTimeSheetT = $this->transform($rawTimeSheet);

        $this->json('POST', '/timeSheet/saveRaw', $rawTimeSheetT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataSaved'),
                'data' => []
            ]);

        $this->seeInDatabase('raw_timesheets', $rawTimeSheet);
        $this->rawTimeSheetDao->delete($rawTimeSheet['person_id'], $rawTimeSheet['date']);
        $this->notSeeInDatabase('raw_timesheets', $rawTimeSheet);
    }

    public function testUpdateRaw()
    {
        $oldRawTimeSheet = $this->rawTimeSheets[0];
        $rawTimeSheet = $oldRawTimeSheet;
        $rawTimeSheet['time_out'] = Carbon::parse($rawTimeSheet['time_out'])->addHour()->toDateTimeString();
        $rawTimeSheet['is_analyzed'] = false;
        $rawTimeSheetT = $this->transform($rawTimeSheet);

        $this->json('POST', '/timeSheet/updateRaw', $rawTimeSheetT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataUpdated'),
                'data' => []
            ]);

        $this->notSeeInDatabase('raw_timesheets', $oldRawTimeSheet);
        $this->seeInDatabase('raw_timesheets', $rawTimeSheet);
        $this->rawTimeSheetDao->delete($rawTimeSheet['person_id'], $rawTimeSheet['date']);
        $this->notSeeInDatabase('raw_timesheets', $rawTimeSheet);
    }

    public function testDeleteRaw()
    {
        $this->seeInDatabase('raw_timesheets', $this->rawTimeSheets[0]);

        $this->json('POST', '/timeSheet/deleteRaw', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'personId' => $this->rawTimeSheets[0]['person_id'],
            'date' => $this->rawTimeSheets[0]['date']
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.dataDeleted'),
            'data' => []
        ]);

        $this->notSeeInDatabase('raw_timesheets', $this->rawTimeSheets[0]);
    }

    public function testAnalyze()
    {
        // Dates to be used for raw time sheet.
        $dates = [];
        $lastDate = Carbon::today('UTC')->addMonth();
        for ($i = 0; $i < 10; $i++) {
            $lastDate = (clone $lastDate)->addDay();
            array_push($dates, $lastDate);
        }

        // Seed raw time sheets for a person.
        $rawTimeSheet = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'person_id' => 1,
            'is_analyzed' => false
        ];
        $rawTimeSheets = [];
        for ($i = 0; $i < 10; $i++) {
            $rawTimeSheet['date'] = $dates[$i]->toDateString();
            $rawTimeSheet['time_in'] = (clone $dates[$i])->second(28800)->toDateTimeString();
            $rawTimeSheet['time_out'] = (clone $dates[$i])->second(61200)->toDateTimeString();

            $this->rawTimeSheetDao->save($rawTimeSheet);
            $this->seeInDatabase('raw_timesheets', $rawTimeSheet);
            array_push($rawTimeSheets, $rawTimeSheet);
        }

        // Seed a time group for a person.
        $timeGroup = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'code' => StringHelper::randomizeStr(20),
            'name' => StringHelper::randomizeStr(50),
            'description' => StringHelper::randomizeStr(255),
            'eff_begin' => '2018-01-01',
            'eff_end' => '2025-01-01',
            'is_ignore_holiday' => true,
            'is_flexy_hour' => true,
            'is_allow_overtime' => true,
            'is_flexy_holiday_overtime' => false,
            'is_default' => false,
            'is_absence_as_annual_leave' => false,
            'ovt_rounding' => '00:15:00',
            'late_tolerance' => '00:30:00'
        ];
        $this->timeGroupDao->save($timeGroup);
        $this->seeInDatabase('time_groups', $timeGroup);

        // Seed a time attribute for a person.
        $timeAttribute = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'person_id' => $rawTimeSheet['person_id'],
            'eff_begin' => '2018-01-01',
            'eff_end' => '2025-01-01',
            'time_group_code' => $timeGroup['code'],
            'created_by' => 1,
            'created_at' => Carbon::now()
        ];
        DB::table('time_attributes')->insert($timeAttribute);
        $this->seeInDatabase('time_attributes', $timeAttribute);

        // Seed log schedule.
        $logSchedule = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'time_group_code' => $timeGroup['code'],
            'date_start' => '2018-01-01',
            'date_end' => '2025-01-01'
        ];
        $logSchedule['id'] = $this->logScheduleDao->save($logSchedule);
        $this->seeInDatabase('log_schedules', $logSchedule);

        // Seed log schedule patterns.
        $logSchedulePattern = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'log_schedule_id' => $logSchedule['id'],
            'leave_code' => null,
            'work_start' => '08:00:00',
            'work_duration' => '09:00:00',
            'break_start' => '12:00:00',
            'break_duration' => '01:00:00'
        ];
        $logSchedulePatternDO = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'log_schedule_id' => $logSchedule['id'],
            'leave_code' => 'DO',
            'work_start' => null,
            'work_duration' => null,
            'break_start' => null,
            'break_duration' => null
        ];
        $logSchedulePatterns = [];
        $sequence = 0;
        for ($i = 0; $i < 5; $i++) {
            $logSchedulePattern['sequence'] = $sequence;
            $sequence++;
            $this->logSchedulePatternDao->save($logSchedulePattern);
            $this->seeInDatabase('log_schedule_patterns', $logSchedulePattern);
            array_push($logSchedulePatterns, $logSchedulePattern);
        }
        for ($i = 0; $i < 2; $i++) {
            $logSchedulePatternDO['sequence'] = $sequence;
            $sequence++;
            $this->logSchedulePatternDao->save($logSchedulePatternDO);
            $this->seeInDatabase('log_schedule_patterns', $logSchedulePatternDO);
            array_push($logSchedulePatterns, $logSchedulePatternDO);
        }

        // Seed leave request.
        $leaveRequest = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'person_id' => 1,
            'leave_code' => 'DO',
            'description' => StringHelper::randomizeStr(50),
            'file_reference' => null,
            'status' => 'A'
        ];
        $leaveRequest['id'] = $this->leaveRequestDao->save($leaveRequest);
        $this->seeInDatabase('leave_requests', $leaveRequest);

        // Seed leave request detail.
        $leaveRequestDetail = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'leave_request_id' => $leaveRequest['id'],
            'date' => $dates[6], // leave code = DO
            'weight' => 0.5,
            'status' => 'A'
        ];
        $this->leaveRequestDetailDao->save($leaveRequestDetail);
        $this->seeInDatabase('leave_request_details', $leaveRequestDetail);

        // Seed overtime request.
        $overtimeRequest = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'person_id' => $rawTimeSheet['person_id'],
            'schedule_date' => $dates[4], // leave code = DO
            'status' => 'A',
            'time_start' => (clone $dates[4])->second(57600)->toDateTimeString(),
            'time_end' => (clone $dates[4])->second(61200)->toDateTimeString()
        ];
        $overtimeRequest['id'] = $this->overtimeRequestDao->save($overtimeRequest);
        $this->seeInDatabase('overtime_requests', $overtimeRequest);

        // Test analyze raw time sheets.
        $this->json('POST', '/timeSheet/analyze', [
            'companyId' => $this->getRequester()->getCompanyId()
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.dataUpdated'),
            'data' => []
        ]);

        // Clean up.
        DB::table('time_attributes')->where([
            ['person_id', $timeAttribute['person_id']],
            ['time_group_code', $timeAttribute['time_group_code']]
        ])->delete();
        $this->notSeeInDatabase('time_attributes', $timeAttribute);

        foreach ($rawTimeSheets as $rawTimeSheet) {
            $this->rawTimeSheetDao->delete($rawTimeSheet['person_id'], $rawTimeSheet['date']);
            $this->notSeeInDatabase('raw_timesheets', $rawTimeSheet);
        }

        $this->timeGroupDao->delete($timeGroup['code']);
        $this->notSeeInDatabase('time_groups', $timeGroup);

        $this->logSchedulePatternDao->delete($logSchedule['id']);
        foreach ($logSchedulePatterns as $logSchedulePattern) {
            $this->notSeeInDatabase('log_schedule_patterns', $logSchedulePattern);
        }

        $this->logScheduleDao->delete($timeGroup['code'], $logSchedule['id']);
        $this->notSeeInDatabase('log_schedules', $logSchedule);

        DB::table('leave_request_details')->where('leave_request_id', $leaveRequest['id'])->delete();
        $this->notSeeInDatabase('leave_request_details', $leaveRequestDetail);

        DB::table('leave_requests')->where('id', $leaveRequest['id'])->delete();
        $this->notSeeInDatabase('leave_requests', $leaveRequest);

        DB::table('overtime_requests')->where('id', $overtimeRequest['id'])->delete();
        $this->notSeeInDatabase('overtime_requests', $overtimeRequest);
    }

    public function tearDown()
    {
        foreach ($this->timeSheets as $timeSheet) {
            DB::table('timesheets')
                ->where([
                    ['tenant_id', $this->getRequester()->getTenantId()],
                    ['company_id', $this->getRequester()->getCompanyId()],
                    ['person_id', $timeSheet['person_id']],
                    ['date', $timeSheet['date']]
                ])
                ->delete();
            $this->notSeeInDatabase('timesheets', $timeSheet);
        }

        foreach ($this->rawTimeSheets as $rawTimeSheet) {
            $this->rawTimeSheetDao->delete($rawTimeSheet['person_id'], $rawTimeSheet['date']);
            $this->notSeeInDatabase('raw_timesheets', $rawTimeSheet);
        }
    }

    private function newRawTimeSheet()
    {
        $rawTimeSheet = $this->rawTimeSheets[0];
        $i = count($this->dates)-1;
        $nextDate = (clone $this->dates[$i])->addDay();
        $rawTimeSheet['date'] = $nextDate->toDateString();
        $rawTimeSheet['time_in'] = $nextDate->toDateTimeString();
        $rawTimeSheet['time_out'] = $nextDate->toDateTimeString();
        $rawTimeSheet['is_analyzed'] = false;
        unset($rawTimeSheet['tenant_id']);
        return $rawTimeSheet;
    }
}
