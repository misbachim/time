<?php

namespace App\Http\Controllers;

use App\Business\Dao\LogScheduleDao;
use App\Business\Dao\LogSchedulePatternDao;
use App\Business\Dao\TimeGroupDao;
use App\Business\Dao\ScheduleExceptionDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeGroupController extends Controller
{
    private $requester;
    private $timeGroupDao;
    private $logScheduleDao;
    private $logSchedulePatternDao;

    public function __construct(
        Requester $requester,
        TimeGroupDao $timeGroupDao,
        LogScheduleDao $logScheduleDao,
        LogSchedulePatternDao $logSchedulePatternDao
    )
    {
        $this->requester = $requester;
        $this->timeGroupDao = $timeGroupDao;
        $this->logScheduleDao = $logScheduleDao;
        $this->logSchedulePatternDao = $logSchedulePatternDao;
    }

    public function getAll(Request $request)
    {
        $this->validate($request, ['companyId' => 'required']);

        $timeGroups = $this->timeGroupDao->getAll();
        foreach ($timeGroups as &$timeGroup) {
            $timeGroup->scheduleGenerated = $this->logScheduleDao->getAll($timeGroup->code)->count() > 0;
        }

        return $this->renderResponse(new AppResponse($timeGroups, trans('messages.allDataRetrieved')));
    }

    public function getOne(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'code' => 'required'
        ]);

        $timeGroup = $this->timeGroupDao->getOne($request->code);

        return $this->renderResponse(new AppResponse($timeGroup, trans('messages.dataRetrieved')));
    }

    public function getOneDefault(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required'
        ]);

        $timeGroup = $this->timeGroupDao->getOneDefault();

        return $this->renderResponse(new AppResponse($timeGroup, trans('messages.dataRetrieved')));
    }

    /**
     * Get all time group in one company
     * @param request
     */
    public function getLov(Request $request)
    {
        $this->validate($request, ["companyId" => "required"]);

        $lov = $this->timeGroupDao->getLov();
        $resp = new AppResponse($lov, trans('messages.allDataRetrieved'));
        return $this->renderResponse($resp);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|alpha_num|max:20|unique:time_groups,code'
        ]);
        $this->checkTimeGroupRequest($request);
        $timeGroup = $this->constructTimeGroup($request);

        DB::transaction(function () use (&$request, &$timeGroup) {
            if ($timeGroup['is_default']) {
                $this->timeGroupDao->setAllNotDefault();
            }
            $this->timeGroupDao->save($timeGroup);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataSaved')));
    }

    public function update(Request $request)
    {
        $this->checkTimeGroupRequest($request);
        $timeGroup = $this->constructTimeGroup($request);
        unset($timeGroup['code']);

        DB::transaction(function () use (&$request, &$timeGroup) {
            if ($timeGroup['is_default']) {
                $this->timeGroupDao->setAllNotDefault();
            }
            $this->timeGroupDao->update($request->code, $timeGroup);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataUpdated')));
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'code' => 'required'
        ]);

        DB::transaction(function () use (&$request) {
            $logSchedules = $this->logScheduleDao->getAll($request->code);
            foreach ($logSchedules as $logSchedule) {
                $this->logSchedulePatternDao->delete($logSchedule->id);
            }
            $this->logScheduleDao->deleteAll($request->code);
            $this->timeGroupDao->delete($request->code);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataDeleted')));
    }

    public function getAllSchedules(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'timeGroupCode' => 'required'
        ]);

        $logSchedules = $this->logScheduleDao->getAll($request->timeGroupCode);

        return $this->renderResponse(new AppResponse($logSchedules, trans('messages.allDataRetrieved')));
    }

    public function getOneSchedule(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'timeGroupCode' => 'required',
            'id' => 'required'
        ]);

        $logSchedule = (array)$this->logScheduleDao->getOne($request->timeGroupCode, $request->id);
        $logSchedule['pattern'] = $this->logSchedulePatternDao->getAll($request->id);

        return $this->renderResponse(new AppResponse($logSchedule, trans('messages.dataRetrieved')));
    }

    public function getOneScheduleWithDate(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'timeGroupCode' => 'required|exists:time_groups,code',
            'targetDate' => 'required|date'
        ]);

        $logSchedule = (array)$this->logScheduleDao->getOneByTargetDate(
            $request->timeGroupCode,
            $request->targetDate
        );

        $log = $this->logScheduleDao->getOneByTargetDate($request->timeGroupCode, $request->targetDate);
        if ($log) {
            $logSchedule['pattern'] = $this->logSchedulePatternDao->getAll($log->id);
        }
        return $this->renderResponse(new AppResponse($logSchedule, trans('messages.dataRetrieved')));
    }

    public function saveSchedule(Request $request)
    {
        $this->checkScheduleRequest($request);
        $data = [];

        DB::transaction(function () use (&$request, &$data) {
            $lastLogSchedule = $this->logScheduleDao->getOneLast($request->dateStart, $request->timeGroupCode);
            $updateLogSchedule = [
                'date_end' => $request->dateStart
            ];
            if ($lastLogSchedule) {
                $this->logScheduleDao->update($request->timeGroupCode, $lastLogSchedule->id, $updateLogSchedule);
            }

            $logSchedule = $this->constructLogSchedule($request);
            $logScheduleId = $this->logScheduleDao->save($logSchedule);
            $logSchedulePattern = $this->constructLogSchedulePattern($request, $logScheduleId);
            $this->logSchedulePatternDao->save($logSchedulePattern);

            $data['id'] = $logScheduleId;
        });

        return $this->renderResponse(new AppResponse($data, trans('messages.dataSaved')));
    }

    public function updateSchedule(Request $request)
    {
        $this->checkScheduleRequest($request);
        $this->validate($request, [
            'id' => 'required|integer|exists:log_schedules,id'
        ]);

        DB::transaction(function () use (&$request) {
            $logSchedule = $this->constructLogSchedule($request);
            $this->logScheduleDao->update($request->timeGroupCode, $request->id, $logSchedule);

            $logSchedulePattern = $this->constructLogSchedulePattern($request, $request->id);
            $this->logSchedulePatternDao->delete($request->id);
            $this->logSchedulePatternDao->save($logSchedulePattern);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataUpdated')));
    }

    public function getScheduleForDate(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'timeGroupCode' => 'required|exists:time_groups,code',
            'targetDate' => 'required|date',
        ]);

        $logSchedule = $this->logScheduleDao->getOneByTargetDate(
            $request->timeGroupCode,
            $request->targetDate
        );
        if (!$logSchedule) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }

        $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
        $dateStart = Carbon::parse($logSchedule->dateStart);
        $targetDate = Carbon::parse($request->targetDate);

        if ($pattern->count() === 0) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }
        $patternIdx = $targetDate->diffInDays($dateStart) % $pattern->count();
        $scheduleDetail = $pattern->slice($patternIdx, 1)->first();
        $scheduleForDate = self::constructScheduleForDate($targetDate, $scheduleDetail);

        return $this->renderResponse(new AppResponse($scheduleForDate, trans('messages.dataRetrieved')));
    }

    public function getScheduleForDates(Request $request)
    {
        $data = $this->logScheduleDao->getTimeGroupByPerson(
            $request->employeeId
        );

        if (!$data || count($data) === 0) {
            throw new AppException(trans('messages.timeGroupNotExists'));
        }

        $this->validate($request, [
            'companyId' => 'required',
//            'timeGroupCode' => 'required|exists:time_groups,code',
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date',
        ]);

        $logSchedules = $this->logScheduleDao->getAllByTargetDates(
            $data->timeGroupCode,
            $request->startDate,
            $request->endDate
        );
        if (!$logSchedules || count($logSchedules) === 0) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }

        $scheduleForDates = [];
        foreach ($logSchedules as $logSchedule) {
            $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
            $scheduleDateStart = Carbon::parse($logSchedule->dateStart);
            $scheduleDateEnd = Carbon::parse($logSchedule->dateEnd);

            $startDate = Carbon::parse($request->startDate);
            $endDate = Carbon::parse($request->endDate);
            $targetDate = clone $startDate;
            while ($targetDate->lessThanOrEqualTo($endDate)) {
                if ($targetDate->lessThan($scheduleDateStart) || $targetDate->greaterThan($scheduleDateEnd)) {
                    continue;
                }
                if ($pattern->count() === 0) {
                    throw new AppException(trans('messages.scheduleNotExists'));
                }
                $patternIdx = $targetDate->diffInDays($scheduleDateStart) % $pattern->count();
                $scheduleDetail = $pattern->slice($patternIdx, 1)->first();
                $scheduleForDate = self::constructScheduleForDate($targetDate, $scheduleDetail);

                if (count(array_filter($scheduleForDates, function ($schedule) use (&$scheduleForDate) {
                        return $schedule['date'] === $scheduleForDate['date'];
                    })) === 0) {
                    array_push($scheduleForDates, $scheduleForDate);
                }
                $targetDate = (clone $targetDate)->addDay();
            }
        }

        return $this->renderResponse(new AppResponse($scheduleForDates, trans('messages.dataRetrieved')));
    }

    public function getScheduleByPerson(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'targetDate' => 'required|date'
        ]);

        $employeeId = '-';

        if ($request->subEmployeeId) {
            $employeeId = $request->subEmployeeId;
        } else if ($request->employeeId) {
            $employeeId = $request->employeeId;
        }

        $logSchedule = $this->logScheduleDao->getOneByEmployeeId(
            $employeeId,
            $request->targetDate
        );
        if (!$logSchedule) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }

        $pattern = $this->logSchedulePatternDao->getAll($logSchedule->id);
        $dateStart = Carbon::parse($logSchedule->dateStart);
        $targetDate = Carbon::parse($request->targetDate);

        if ($pattern->count() === 0) {
            throw new AppException(trans('messages.scheduleNotExists'));
        }
        $patternIdx = $targetDate->diffInDays($dateStart) % $pattern->count();
        $scheduleDetail = $pattern->slice($patternIdx, 1)->first();
        $scheduleForDate = self::constructScheduleForDate($targetDate, $scheduleDetail);

        return $this->renderResponse(new AppResponse($scheduleForDate, trans('messages.dataRetrieved')));
    }

    public static function constructScheduleForDate($targetDate, $scheduleDetail)
    {
        if ($scheduleDetail->leaveCode) {
            return [
                'date' => $targetDate->toDateString(),
                'leaveCode' => $scheduleDetail->leaveCode,
                'timeIn' => null,
                'timeOut' => null,
                'breakStart' => null,
                'breakEnd' => null,
                'durationInSeconds' => null
            ];
        }

        $workStartSinceMidnight = Carbon::parse($scheduleDetail->workStart, 'UTC')->secondsSinceMidnight();
        $workDurationInSeconds = Carbon::parse($scheduleDetail->workDuration, 'UTC')->secondsSinceMidnight();
        $workEndSinceMidnight = $workStartSinceMidnight + $workDurationInSeconds;

        $breakStartSinceMidnight = Carbon::parse($scheduleDetail->breakStart, 'UTC')->secondsSinceMidnight();
        $breakDurationInSeconds = Carbon::parse($scheduleDetail->breakDuration, 'UTC')->secondsSinceMidnight();
        $breakEndSinceMidnight = $breakStartSinceMidnight + $breakDurationInSeconds;

        return [
            'date' => $targetDate->toDateString(),
            'leaveCode' => $scheduleDetail->leaveCode,
            'timeIn' => (clone $targetDate)->second($workStartSinceMidnight)->toDateTimeString(),
            'timeOut' => (clone $targetDate)->second($workEndSinceMidnight)->toDateTimeString(),
            'breakStart' => (clone $targetDate)->second($breakStartSinceMidnight)->toDateTimeString(),
            'breakEnd' => (clone $targetDate)->second($breakEndSinceMidnight)->toDateTimeString(),
            'durationInSeconds' => $workDurationInSeconds
        ];
    }

//    private function generateTimeGroupSchedule($timeGroupCode)
//    {
//        $logSchedules = $this->logScheduleDao->getAll($timeGroupCode); // assume that schedules have been sorted by start date
//        $scheduleDetails = [];
//        for ($i = 0; $i < count($logSchedules); $i++) {
//            $dateStart = Carbon::parse($logSchedules[$i]->dateStart);
//            $dateEnd = Carbon::parse($logSchedules[$i]->dateEnd);
//            $currentDate = $dateStart;
//            $pattern = $this->logSchedulePatternDao->getAll($logSchedules[$i]->id); // assume that the pattern has been sorted by sequence
//
//            while ($currentDate->toDateString() !== $dateEnd->addDay()->toDateString()) {
//                if (array_key_exists($i + 1, $logSchedules)) {
//                    $nextDateStart = Carbon::parse($logSchedules[$i + 1]->dateStart);
//                    if ($currentDate->toDateString() === $nextDateStart->toDateString()) {
//                        break;
//                    }
//                }
//
//                $sequence = $currentDate->diffInDays($dateStart) % count($pattern);
//                $selectedPattern = $pattern[$sequence];
//
//                $workStartSinceMidnight = Carbon::parse($selectedPattern->workStart)->secondsSinceMidnight();
//                $workDurationInSeconds = Carbon::parse($selectedPattern->workDuration)->secondsSinceMidnight();
//                $breakStartSinceMidnight = Carbon::parse($selectedPattern->breakStart)->secondsSinceMidnight();
//                $breakDurationInSeconds = Carbon::parse($selectedPattern->breakDuration)->secondsSinceMidnight();
//
//                $currentDate->second = $workStartSinceMidnight;
//                $workStartTimestamp = $currentDate->timestamp;
//                $currentDate->addSeconds($workDurationInSeconds);
//                $workEndTimestamp = $currentDate->timestamp;
//
//                $currentDate->second = $breakStartSinceMidnight;
//                $breakStartTimestamp = $currentDate->timestamp;
//                $currentDate->addSeconds($breakDurationInSeconds);
//                $breakEndTimestamp = $currentDate->timestamp;
//
//                array_push($scheduleDetails, [
//                    'tenant_id' => $this->requester->getTenantId(),
//                    'company_id' => $this->requester->getCompanyId(),
//                    'time_group_code' => $timeGroupCode,
//                    'date' => $currentDate->toDateString(),
//                    'leave_code' => $selectedPattern->leaveCode,
//                    'time_in' => $workStartTimestamp,
//                    'time_out' => $workEndTimestamp,
//                    'break_start' => $breakStartTimestamp,
//                    'break_end' => $breakEndTimestamp
//                ]);
//            }
//        }
//        return $scheduleDetails;
//    }

    private function checkTimeGroupRequest(Request $request)
    {
        $this->validate($request, [
            // TODO: companyId should exist in companies table
            'companyId' => 'required|integer',
            'name' => 'required|max:50',
            'description' => 'present|max:255',
            'effBegin' => 'required|date|before_or_equal:effEnd',
            'effEnd' => 'required|date',
            'isIgnoreHoliday' => 'required|boolean',
            'isFlexyHour' => 'required|boolean',
            'isAllowOvertime' => 'required|boolean',
            'isFlexyHolidayOvertime' => 'required|boolean',
            'isDefault' => 'required|boolean',
            'isAbsenceAsAnnualLeave' => 'required|boolean',
            'ovtRounding' => 'required|string',
            'lateTolerance' => 'required|string'
        ]);
    }

    private function constructTimeGroup(Request $request)
    {
        $timeGroup = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'eff_begin' => $request->effBegin,
            'eff_end' => $request->effEnd,
            'is_ignore_holiday' => $request->isIgnoreHoliday,
            'is_flexy_hour' => $request->isFlexyHour,
            'is_allow_overtime' => $request->isAllowOvertime,
            'is_flexy_holiday_overtime' => $request->isFlexyHolidayOvertime,
            'is_default' => $request->isDefault,
            'is_absence_as_annual_leave' => $request->isAbsenceAsAnnualLeave,
            'ovt_rounding' => $request->ovtRounding,
            'late_tolerance' => $request->lateTolerance
        ];
        return $timeGroup;
    }

    private function checkScheduleRequest(Request $request)
    {
        $this->validate($request, [
            // TODO: companyId should exist in companies table
            'companyId' => 'required|integer',
            'timeGroupCode' => 'required|alpha_num|exists:time_groups,code',
            'dateStart' => 'required|date|before_or_equal:dateEnd',
            'dateEnd' => 'required|date',
            'pattern' => 'required|array|min:1',
            'pattern.*.sequence' => 'required|integer|min:0',
            'pattern.*.leaveCode' => 'required_without:pattern.*.workStart|nullable|alpha_num|exists:leaves,code',
            'pattern.*.workStart' => 'required_without:pattern.*.leaveCode|nullable|string',
            'pattern.*.workDuration' => 'required_with:pattern.*.workStart|nullable|string',
            'pattern.*.breakStart' => 'required_with:pattern.*.workStart|nullable|string',
            'pattern.*.breakDuration' => 'required_with:pattern.*.breakStart|nullable|string'
        ]);
        $this->checkSchedulePatternSequence($request);
    }

    private function checkSchedulePatternSequence(Request $request)
    {
        $prevSequence = $request->pattern[0]['sequence'];
        if ($prevSequence !== 0) {
            throw new AppException(trans('messages.schedulePatternHasBadSequence'));
        }
        foreach (array_slice($request->pattern, 1) as $item) {
            if ($item['sequence'] !== $prevSequence + 1) {
                throw new AppException(trans('messages.schedulePatternHasBadSequence'));
            }
            $prevSequence = $item['sequence'];
        }
    }

    private function constructLogSchedule(Request $request)
    {
        $logSchedule = [
            'tenant_id' => $this->requester->getTenantId(),
            'company_id' => $this->requester->getCompanyId(),
            'time_group_code' => $request->timeGroupCode,
            'date_start' => $request->dateStart,
            'date_end' => $request->dateEnd
        ];
        return $logSchedule;
    }

    private function constructLogSchedulePattern(Request $request, $logScheduleId)
    {
        $logSchedulePattern = [];
        foreach ($request->pattern as $detail) {
            array_push($logSchedulePattern, [
                'tenant_id' => $this->requester->getTenantId(),
                'company_id' => $this->requester->getCompanyId(),
                'log_schedule_id' => $logScheduleId,
                'sequence' => $detail['sequence'],
                'leave_code' => $detail['leaveCode'],
                'work_start' => $detail['workStart'],
                'work_duration' => $detail['workDuration'],
                'break_start' => $detail['breakStart'],
                'break_duration' => $detail['breakDuration']
            ]);
        }
        return $logSchedulePattern;
    }
}
