<?php

namespace App\Http\Controllers;

use App\Business\Dao\CalendarDao;
use App\Business\Model\AppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB, Log;

/**
 * Class for handling Calendar data
 */
class CalendarController extends Controller
{
    private $requester;


    public function __construct(Requester $requester, CalendarDao $calendarDao)
    {
        $this->calendarDao = $calendarDao;
        $this->requester = $requester;
    }

    /**
     * Get all calendar in one company
     * @param Request $request
     * @return AppResponse
     */
    public function getAll(Request $request)
    {
        $this->validate($request, ["companyId" => "required|integer"]);
        $calendar = $this->calendarDao->getAll();
        $response = new AppResponse($calendar, trans('messages.allDataRetrieved'));

        return $this->renderResponse($response);
    }

    /**
     * Get all calendar in one company
     * @param Request $request
     * @return AppResponse
     */
    public function getAllEventWithEligibilty(Request $request)
    {
        Log::info('data', ['data', $request->all()]);
        $this->validate($request, ["companyId" => "required|integer"]);
        // $getEvents = $this->calendarDao->getAllEvent();
        $getEvents = $this->calendarDao->getAllEvents($request->eventStart, $request->eventEnd, $request->eventName);
        info('get event', array($getEvents));
        $events = [];
        foreach ($getEvents as $event) {

            $getEventEligibilites = $this->calendarDao->getEventEligibilites($event->id);

            if (count($getEventEligibilites) < 1) {
                $getData = $this->calendarDao->getOne($event->id);
                array_push($events, $getData);
            } else {
                foreach ($getEventEligibilites as $datum) {
                    if ($datum->privilege === 'A') {

                        if ($datum->lovEvel === 'JOB' && $request->has('jobCode')) {
                            if ($request->jobCode === $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                        if ($datum->lovEvel === 'POS' && $request->has('positionCode')) {
                            if ($request->positionCode === $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                        if ($datum->lovEvel === 'LOC' && $request->has('locationCode')) {
                            if ($request->locationCode === $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                        if ($datum->lovEvel === 'UNI' && $request->has('unitCode')) {
                            if ($request->unitCode === $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                    } else if ($datum->privilege === 'D') {

                        if ($datum->lovEvel === 'JOB' && $request->has('jobCode')) {
                            if ($request->jobCode !== $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                        if ($datum->lovEvel === 'POS' && $request->has('positionCode')) {
                            if ($request->positionCode !== $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                        if ($datum->lovEvel === 'LOC' && $request->has('locationCode')) {
                            if ($request->locationCode !== $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                        if ($datum->lovEvel === 'UNI' && $request->has('unitCode')) {
                            if ($request->unitCode !== $datum->value) {
                                $getData = $this->calendarDao->getOne($datum->eventId);
                                array_push($events, $getData);
                                break;
                            }
                        }
                    }
                }
            }
        }

        $response = new AppResponse($events, trans('messages.allDataRetrieved'));
        return $this->renderResponse($response);
    }

    public function getOne(Request $request)
    {
        $this->validate($request, [
            'companyId' => 'required',
            'id' => 'required'
        ]);

        $calendar = $this->calendarDao->getOne($request->id);
        $calendar->participant = $this->calendarDao->getPrivilege($request->id);
        $calendar->jobCode = $this->calendarDao->getParticipants($request->id, 'JOB');
        $calendar->locationCode = $this->calendarDao->getParticipants($request->id, 'LOC');
        $calendar->positionCode = $this->calendarDao->getParticipants($request->id, 'POS');
        $calendar->unitCode = $this->calendarDao->getParticipants($request->id, 'UNI');

        return $this->renderResponse(new AppResponse($calendar, trans('messages.dataRetrieved')));
    }

    /**
     * Save calendar
     * @param Request $request
     * @return AppResponse
     */
    public function save(Request $request)
    {
        $data = array();
        $this->checkCalendarRequest($request);

        DB::transaction(function () use (&$request, &$data) {
            $calendar = $this->constructCalendar($request);
            $calendar['id'] = $this->calendarDao->save($calendar);

            $this->constructParticipant($request, $calendar['id']);
        });

        $resp = new AppResponse($data, trans('messages.dataSaved'));
        return $this->renderResponse($resp);
    }

    public function update(Request $request)
    {
        $this->checkCalendarRequest($request);

        DB::transaction(function () use (&$request) {
            $calendar = $this->constructCalendar($request);

            $this->calendarDao->update($request->id, $calendar);

            $this->calendarDao->deleteParticipant($request->id);
            $this->constructParticipant($request, $request->id);
        });

        $resp = new AppResponse(null, trans('messages.dataUpdated'));
        return $this->renderResponse($resp);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        DB::transaction(function () use (&$request) {
            $this->calendarDao->delete($request->id);
            $this->calendarDao->deleteParticipant($request->id);
        });

        return $this->renderResponse(new AppResponse(null, trans('messages.dataDeleted')));
    }

    /**
     * Validate save/update calendar request.
     * @param Request $request
     */
    private function checkCalendarRequest(Request $request)
    {
        $this->validate($request, [
            'eventStart' => 'required|date',
            'leaveCode' => 'max:20',
            'eventColor' => 'required|max:10',
            'eventName' => 'required|max:50',
            'eventLocation' => 'present|max:255',
            'description' => 'present|max:255'
        ]);
    }

    /**
     * Construct a calendar object (array).
     * @param Request $request
     * @return array
     */
    private function constructCalendar(Request $request)
    {
        $calendar = [
            "tenant_id" => $this->requester->getTenantId(),
            "company_id" => $this->requester->getCompanyId(),
            "event_start" => $request->eventStart,
            "event_end" => $request->eventEnd,
            "color" => $request->eventColor,
            "name" => $request->eventName,
            "leave_code" => $request->leaveCode,
            "location" => $request->eventLocation,
            "description" => $request->description,
            "is_full_day" => $request->allDay
        ];
        return $calendar;
    }

    private function constructParticipant(Request $request, $eventId)
    {
        if ($request->participant != 'L') {
            $job = [];
            foreach ($request->jobCode as $jobCodes) {
                array_push($job, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'event_id' => $eventId,
                    'lov_evel' => 'JOB',
                    'value' => $jobCodes['code'],
                    'privilege' => $request->participant
                ]);
            }
            $this->calendarDao->saveParticipant($job);

            $location = [];
            foreach ($request->locationCode as $locationCodes) {
                array_push($location, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'event_id' => $eventId,
                    'lov_evel' => 'LOC',
                    'value' => $locationCodes['code'],
                    'privilege' => $request->participant
                ]);
            }
            $this->calendarDao->saveParticipant($location);

            $position = [];
            foreach ($request->positionCode as $positionCodes) {
                array_push($position, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'event_id' => $eventId,
                    'lov_evel' => 'POS',
                    'value' => $positionCodes['code'],
                    'privilege' => $request->participant
                ]);
            }
            $this->calendarDao->saveParticipant($position);

            $unit = [];
            foreach ($request->unitCode as $unitCodes) {
                array_push($unit, [
                    'tenant_id' => $this->requester->getTenantId(),
                    'company_id' => $request->companyId,
                    'event_id' => $eventId,
                    'lov_evel' => 'UNI',
                    'value' => $unitCodes['code'],
                    'privilege' => $request->participant
                ]);
            }
            $this->calendarDao->saveParticipant($unit);
        }
    }
}
