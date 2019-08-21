<?php

use Carbon\Carbon;
use App\Business\Dao\TimeGroupDao;
use App\Business\Dao\LogScheduleDao;
use App\Business\Dao\LogSchedulePatternDao;
use App\Business\Helper\StringHelper;

class TimeGroupTest extends TestCase
{
    use Testable;

    private $timeGroupDao;
    private $timeGroups;
    private $timeGroupsT;
    private $logScheduleDao;
    private $logSchedules;
    private $logSchedulesT;
    private $logSchedulePatternDao;
    private $logSchedulePatterns;
    private $logSchedulePatternsT;

    public function setUp()
    {
        parent::setUp();
        $this->timeGroupDao = new TimeGroupDao($this->getRequester());
        $this->logScheduleDao = new LogScheduleDao($this->getRequester());
        $this->logSchedulePatternDao = new LogSchedulePatternDao($this->getRequester());

        $timeGroup = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'eff_begin' => '2018-01-01',
            'eff_end' => '2019-01-01',
            'is_ignore_holiday' => true,
            'is_flexy_hour' => true,
            'is_allow_overtime' => true,
            'is_flexy_holiday_overtime' => false,
            'is_default' => false,
            'is_absence_as_annual_leave' => false,
            'ovt_rounding' => '00:15:00',
            'late_tolerance' => '00:30:00'
        ];

        $this->timeGroups = [];
        $this->timeGroupsT = [];
        for ($i = 0; $i < 10; $i++) {
            $timeGroup['code'] = StringHelper::randomizeStr(20);
            $timeGroup['name'] = StringHelper::randomizeStr(50);
            $timeGroup['description'] = StringHelper::randomizeStr(255);

            $this->timeGroupDao->save($timeGroup);
            $this->seeInDatabase('time_groups', $timeGroup);
            array_push($this->timeGroups, $timeGroup);

            $timeGroupT = $this->transform($timeGroup);
            array_push($this->timeGroupsT, $timeGroupT);
        }

        $logSchedule = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'time_group_code' => $this->timeGroups[0]['code']
        ];

        $this->logSchedules = [];
        $this->logSchedulesT = [];
        $lastDateEnd = Carbon::yesterday();
        for ($i = 0; $i < 10; $i++) {
            $lastDateStart = (clone $lastDateEnd)->addDay();
            $lastDateEnd = (clone $lastDateStart)->addMonth();

            $logSchedule['date_start'] = $lastDateStart->toDateString();
            $logSchedule['date_end'] = $lastDateEnd->toDateString();


            $logSchedule['id'] = $this->logScheduleDao->save($logSchedule);
            $this->seeInDatabase('log_schedules', $logSchedule);
            array_push($this->logSchedules, $logSchedule);

            $logScheduleT = $this->transform($logSchedule);
            array_push($this->logSchedulesT, $logScheduleT);

            unset($logSchedule['id']);
        }

        $logSchedulePattern = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'log_schedule_id' => $this->logSchedules[0]['id'],
            'leave_code' => null,
            'work_start' => '08:00:00',
            'work_duration' => '09:00:00',
            'break_start' => '12:00:00',
            'break_duration' => '01:00:00'
        ];
        $logSchedulePatternDO = [
            'tenant_id' => $this->getRequester()->getTenantId(),
            'company_id' => $this->getRequester()->getCompanyId(),
            'log_schedule_id' => $this->logSchedules[0]['id'],
            'leave_code' => 'DO',
            'work_start' => null,
            'work_duration' => null,
            'break_start' => null,
            'break_duration' => null
        ];

        $this->logSchedulePatterns = [];
        $this->logSchedulePatternsT = [];
        $sequence = 0;
        for ($i = 0; $i < 5; $i++) {
            $logSchedulePattern['sequence'] = $sequence;
            $sequence++;
            $this->logSchedulePatternDao->save($logSchedulePattern);
            $this->seeInDatabase('log_schedule_patterns', $logSchedulePattern);
            array_push($this->logSchedulePatterns, $logSchedulePattern);

            $logSchedulePatternT = $this->transform($logSchedulePattern);
            array_push($this->logSchedulePatternsT, $logSchedulePatternT);
        }

        for ($i = 0; $i < 2; $i++) {
            $logSchedulePatternDO['sequence'] = $sequence;
            $sequence++;
            $this->logSchedulePatternDao->save($logSchedulePatternDO);
            $this->seeInDatabase('log_schedule_patterns', $logSchedulePatternDO);
            array_push($this->logSchedulePatterns, $logSchedulePatternDO);

            $logSchedulePatternT = $this->transform($logSchedulePatternDO);
            array_push($this->logSchedulePatternsT, $logSchedulePatternT);
        }
    }

    public function testGetAll()
    {
        $this->timeGroupsT = $this->exclude($this->timeGroupsT, [
            'tenantId',
            'companyId',
            'effEnd',
            'isIgnoreHoliday',
            'isFlexyHour',
            'isAllowOvertime',
            'isFlexyHolidayOvertime',
            'isDefault',
            'isAbsenceAsAnnualLeave',
            'ovtRounding',
            'lateTolerance'
        ]);

        $this->json('POST', '/timeGroup/getAll', [
            'companyId' => $this->getRequester()->getCompanyId()
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.allDataRetrieved')
        ]);

        foreach ($this->timeGroupsT as $timeGroupT) {
            foreach ($timeGroupT as $field => $value) {
                $this->seeJson([$field => $value]);
            }
        }
    }

    public function testGetOne()
    {
        $this->timeGroupsT = $this->exclude($this->timeGroupsT, [
            'tenantId',
            'companyId'
        ]);

        $this->json('POST', '/timeGroup/getOne', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'code' => $this->timeGroupsT[0]['code']
        ])->seeJson([
            'status' => 200,
            'message' => trans('messages.dataRetrieved')
        ]);

        foreach ($this->timeGroupsT[0] as $field => $value) {
            $this->seeJson([$field => $value]);
        }
    }

    public function testSave()
    {
        $timeGroup = $this->newTimeGroup();
        $timeGroupT = $this->transform($timeGroup);

        $this->json('POST', '/timeGroup/save', $timeGroupT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataSaved'),
                'data' => []
            ]);

        $this->seeInDatabase('time_groups', $timeGroup);
        $this->timeGroupDao->delete($timeGroup['code']);
        $this->notSeeInDatabase('time_groups', $timeGroup);
    }

    public function testSaveDefault()
    {
        $oldTimeGroup = $this->newTimeGroup();
        $oldTimeGroup['is_default'] = true;
        $oldTimeGroupT = $this->transform($oldTimeGroup);
        $this->json('POST', '/timeGroup/save', $oldTimeGroupT);
        $this->seeInDatabase('time_groups', $oldTimeGroup);

        $timeGroup = $this->newTimeGroup();
        $timeGroup['is_default'] = true;
        $timeGroupT = $this->transform($timeGroup);

        $this->json('POST', '/timeGroup/save', $timeGroupT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataSaved'),
                'data' => []
            ]);

        $savedOldTimeGroup = $this->timeGroupDao->getOne($oldTimeGroup['code']);
        $this->assertNotTrue($savedOldTimeGroup->isDefault);
        $this->seeInDatabase('time_groups', $timeGroup);

        $this->timeGroupDao->delete($oldTimeGroup['code']);
        $oldTimeGroup['is_default'] = false;
        $this->notSeeInDatabase('time_groups', $oldTimeGroup);
        $this->timeGroupDao->delete($timeGroup['code']);
        $this->notSeeInDatabase('time_groups', $timeGroup);
    }

    public function testUpdate()
    {
        $oldTimeGroup = $this->timeGroups[0];
        $timeGroup = $oldTimeGroup;
        $timeGroup['name'] = StringHelper::randomizeStr(50);
        $timeGroupT = $this->transform($timeGroup);

        $this->json('POST', '/timeGroup/update', $timeGroupT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataUpdated'),
                'data' => []
            ]);

        $this->notSeeInDatabase('time_groups', $oldTimeGroup);
        $this->seeInDatabase('time_groups', $timeGroup);
        $this->timeGroupDao->delete($timeGroup['code']);
        $this->notSeeInDatabase('time_groups', $timeGroup);
    }

    public function testUpdateDefault()
    {
        $defaultTimeGroup = $this->newTimeGroup();
        $defaultTimeGroup['is_default'] = true;
        $defaultTimeGroupT = $this->transform($defaultTimeGroup);
        $this->json('POST', '/timeGroup/save', $defaultTimeGroupT);
        $this->seeInDatabase('time_groups', $defaultTimeGroup);

        $oldTimeGroup = $this->timeGroups[0];
        $timeGroup = $oldTimeGroup;
        $timeGroup['name'] = StringHelper::randomizeStr(50);
        $timeGroup['is_default'] = true;
        $timeGroupT = $this->transform($timeGroup);

        $this->json('POST', '/timeGroup/update', $timeGroupT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataUpdated'),
                'data' => []
            ]);

        $savedDefaultTimeGroup = $this->timeGroupDao->getOne($defaultTimeGroup['code']);
        $this->assertNotTrue($savedDefaultTimeGroup->isDefault);

        $this->timeGroupDao->delete($defaultTimeGroup['code']);
        $defaultTimeGroup['is_default'] = false;
        $this->notSeeInDatabase('time_groups', $defaultTimeGroup);

        $this->notSeeInDatabase('time_groups', $oldTimeGroup);
        $this->seeInDatabase('time_groups', $timeGroup);
        $this->timeGroupDao->delete($timeGroup['code']);
        $this->notSeeInDatabase('time_groups', $timeGroup);

    }

    public function testDelete()
    {
        $this->json('POST', '/timeGroup/delete', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'code' => $this->timeGroups[0]['code']
        ])
        ->seeJson([
            'status' => 200,
            'message' => trans('messages.dataDeleted'),
            'data' => []
        ]);

        $this->notSeeInDatabase('time_groups', $this->timeGroups[0]);
    }

    public function testGetAllSchedules()
    {
        $this->json('POST', '/timeGroup/getAllSchedules', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'timeGroupCode' => $this->timeGroups[0]['code']
        ])
        ->seeJson([
            'status' => 200,
            'message' => trans('messages.allDataRetrieved')
        ]);

        $this->logSchedulesT = $this->exclude($this->logSchedulesT, [
            'tenantId',
            'companyId',
            'timeGroupCode'
        ]);
        foreach ($this->logSchedulesT as $logScheduleT) {
            foreach ($logScheduleT as $field => $value) {
                $this->seeJson([$field => $value]);
            }
        }
    }

    public function testGetOneSchedule()
    {
        $this->json('POST', '/timeGroup/getOneSchedule', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'timeGroupCode' => $this->timeGroups[0]['code'],
            'id' => $this->logSchedules[0]['id']
        ])
        ->seeJson([
            'status' => 200,
            'message' => trans('messages.dataRetrieved')
        ]);

        $this->logSchedulesT = $this->exclude($this->logSchedulesT, [
            'tenantId',
            'companyId',
            'timeGroupCode',
            'id'
        ]);
        foreach ($this->logSchedulesT[0] as $field => $value) {
            $this->seeJson([$field => $value]);
        }

        $data = (array) json_decode($this->response->getContent())->data;
        $this->assertArrayHasKey('pattern', $data, 'Schedule should have pattern');
    }

    public function testSaveSchedule()
    {
        $logSchedule = $this->newLogSchedule();
        $logScheduleT = $this->transform($logSchedule);
        $logSchedulePatterns = $this->newLogSchedulePatterns();
        $logSchedulePatternsT = array_map(function ($pattern) {
            return $this->transform($pattern);
        }, $logSchedulePatterns);

        $logScheduleT['pattern'] = $logSchedulePatternsT;

        $this->json('POST', '/timeGroup/saveSchedule', $logScheduleT)
        ->seeJson([
            'status' => 200,
            'message' => trans('messages.dataSaved')
        ]);
        $this->seeJsonStructure([
            'data' => [
                'id'
            ]
        ]);

        $data = json_decode($this->response->getContent())->data;

        $this->seeInDatabase('log_schedules', $logSchedule);
        $this->logScheduleDao->delete($logSchedule['time_group_code'], $data->id);
        $this->notSeeInDatabase('log_schedules', $logSchedule);
        $this->logSchedulePatternDao->delete($data->id);
    }

    public function testUpdateSchedule()
    {
        $oldLogSchedule = $this->logSchedules[0];
        $logSchedule = $oldLogSchedule;
        $logSchedule['date_end'] = Carbon::parse($logSchedule['date_end'])->addMonth()->toDateString();
        $logScheduleT = $this->transform($logSchedule);
        $logScheduleT['pattern'] = $this->exclude($this->logSchedulePatternsT, [
            'tenantId',
            'companyId',
            'logScheduleId'
        ]);

        $this->json('POST', '/timeGroup/updateSchedule', $logScheduleT)
            ->seeJson([
                'status' => 200,
                'message' => trans('messages.dataUpdated'),
                'data' => []
            ]);

        $this->notSeeInDatabase('log_schedules', $oldLogSchedule);
        $this->seeInDatabase('log_schedules', $logSchedule);
        $this->logScheduleDao->delete($logSchedule['time_group_code'], $logSchedule['id']);
        $this->notSeeInDatabase('log_schedules', $logSchedule);
    }

    public function testGetScheduleForDate()
    {
        $this->json('POST', '/timeGroup/getScheduleForDate', [
            'companyId' => $this->getRequester()->getCompanyId(),
            'timeGroupCode' => $this->timeGroups[0]['code'],
            'targetDate' => Carbon::parse($this->logSchedules[0]['date_start'])->addDays(3)->toDateString()
        ])
        ->seeJson([
            'status' => 200,
            'message' => trans('messages.dataRetrieved')
        ])
        ->seeJsonStructure([
            'data' => [
                'date',
                'leaveCode',
                'timeIn',
                'timeOut',
                'breakStart',
                'breakEnd'
            ]
        ]);

//        echo json_encode(json_decode($this->response->getContent())->data);
    }

    public function tearDown()
    {
        foreach ($this->logSchedules as $logSchedule) {
            $this->logScheduleDao->delete($this->timeGroups[0]['code'], $logSchedule['id']);
            $this->notSeeInDatabase('log_schedules', $logSchedule);
        }

        $this->logSchedulePatternDao->delete($this->logSchedules[0]['id']);
        foreach ($this->logSchedulePatterns as $logSchedulePattern) {
            $this->notSeeInDatabase('log_schedule_patterns', $logSchedulePattern);
        }

        foreach ($this->timeGroups as $timeGroup) {
            $this->timeGroupDao->delete($timeGroup['code']);
            $this->notSeeInDatabase('time_groups', $timeGroup);
        }
    }

    private function newTimeGroup()
    {
        $timeGroup = $this->timeGroups[0];
        $timeGroup['code'] = StringHelper::randomizeStr(20);
        $timeGroup['name'] = StringHelper::randomizeStr(50);
        $timeGroup['description'] = StringHelper::randomizeStr(255);
        unset($timeGroup['tenant_id']);
        return $timeGroup;
    }

    private function newLogSchedule()
    {
        $logSchedule = $this->logSchedules[count($this->logSchedules)-1];

        $dateStart = Carbon::parse($logSchedule['date_start']);
        $dateEnd = Carbon::parse($logSchedule['date_end']);
        $logSchedule['date_start'] = $dateStart->addMonth()->toDateString();
        $logSchedule['date_end'] = $dateEnd->addMonth()->toDateString();

        unset($logSchedule['tenant_id']);
        unset($logSchedule['id']);
        return $logSchedule;
    }

    private function newLogSchedulePatterns()
    {
        return $this->exclude($this->logSchedulePatterns, [
            'tenant_id',
            'company_id',
            'log_schedule_id'
        ]);
    }
}
