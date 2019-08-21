<?php

namespace App\Business\Dao;

use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB, Log;

/**
 * Calendar related dao
 * @package App\Business\Dao
 */
class CalendarDao
{
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * Get all calendar of company
     * @return query result
     */
    public function getAll()
    {
        return
            DB::table('events')
            ->select(
                'id',
                'leave_code as leaveCode',
                'is_full_day as isFullDay',
                'name as eventName',
                'description',
                'event_start as eventStart',
                'event_end as eventEnd',
                'name as holidayName',
                'event_start as date',
                'color as eventColor'
            )
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()]
            ])
            ->get();
    }

    /**
     * Get all event of company
     * @return query result
     */
    public function getAllEvent()
    {
        return
            DB::table('events')
            ->select(
                'id',
                'leave_code as leaveCode',
                'is_full_day as isFullDay',
                'name as eventName',
                'description',
                'event_start as eventStart',
                'event_end as eventEnd',
                'location as eventLocation',
                'name as holidayName',
                'event_start as date',
                'color as eventColor'
            )
            ->where([
                ['leave_code', null],
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()]
            ])
            ->orWhere('leave_code', '=', '')
            ->get();
    }


    /**
     * Get all event of company
     * @return query result
     */
    public function getAllEvents($eventStart, $eventEnd, $eventName)
    {
        // Log::info('data', ['data', $eventEnd]);
        Log::info('data', ['data', Carbon::parse($eventStart)]);
        Log::info('data', ['data', Carbon::parse($eventEnd)]);

        // $searchString = strtolower("%$query%");
        // $query = '%' . $eventName . '%';
        $query = strtolower("%$eventName%");
        Log::info('data', ['query', $query]);
        return
            DB::table('events')
            ->select(
                'id',
                'leave_code as leaveCode',
                'is_full_day as isFullDay',
                'name as eventName',
                'description',
                'event_start as eventStart',
                'event_end as eventEnd',
                'location as eventLocation',
                'name as holidayName',
                'event_start as date',
                'color as eventColor'
            )
            ->where(function ($w) use ($eventEnd, $eventStart, $query) {
                $w->where([
                    ['leave_code', null],
                    ['tenant_id', $this->requester->getTenantId()],
                    ['company_id', $this->requester->getCompanyId()]
                ])
                    ->whereRaw('LOWER(name) like ?', [$query])
                    ->whereDate('event_start', '>=', Carbon::parse($eventStart))
                    ->whereDate('event_end', '<=', Carbon::parse($eventEnd));
            })

            ->orWhere('leave_code', '=', '')
            ->get();
    }

    /**
     * Get one holiday of company
     * @return query result
     */
    public function getOneHoliday($date)
    {
        return
            DB::table('events')
            ->select(
                'leaves.code as leaveCode',
                'events.is_full_day as allDay',
                'events.event_start as eventStart',
                'events.name as holidayName',
                'events.color as eventColor',
                'events.id'
            )
            ->distinct()
            ->join('leaves', function ($join) {
                $join->on('leaves.type', 'events.leave_code')
                    ->on('leaves.tenant_id', 'events.tenant_id')
                    ->on('leaves.company_id', 'events.company_id');
            })
            ->where([
                ['events.tenant_id', '=', $this->requester->getTenantId()],
                ['events.company_id', '=', $this->requester->getCompanyId()],
                ['events.event_start', '=', $date]
            ])
            ->whereNotNull('events.leave_code')
            ->first();
    }

    /**
     * Get one  all holiday of company
     * @return query result
     */
    public function getOneAllHoliday($date)
    {
        return
            DB::table('events')
                ->select(
                    'events.is_full_day as allDay',
                    'events.event_start as eventStart',
                    'events.name as holidayName',
                    'events.color as eventColor',
                    'events.id'
                )
                ->where([
                    ['events.tenant_id', '=', $this->requester->getTenantId()],
                    ['events.company_id', '=', $this->requester->getCompanyId()],
                    ['events.event_start', '=', $date]
                ])
                ->whereNotNull('events.leave_code')
                ->first();
    }

    public function getOne($calendar_id)
    {
        return
            DB::table('events')
            ->select(
                'leave_code as leaveCode',
                'is_full_day as allDay',
                'name as eventName',
                'description',
                'event_start as eventStart',
                'event_end as eventEnd',
                'name as holidayName',
                'event_start as date',
                'color as eventColor',
                'location as eventLocation',
                'id'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['id', '=', $calendar_id]
            ])
            ->first();
    }

    public function getEventEligibilites($eventId)
    {
        return
            DB::table('event_eligibilities')
            ->select(
                'event_id as eventId',
                'lov_evel as lovEvel',
                'value',
                'privilege'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['event_id', '=', $eventId]
            ])
            ->get();
    }

    public function getParticipants($eventId, $lovEvel)
    {
        return
            DB::table('event_eligibilities')
            ->select(
                'lov_evel as lovEvel',
                'value',
                'privilege'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['event_id', '=', $eventId],
                ['lov_evel', '=', $lovEvel]
            ])
            ->get();
    }

    public function getPrivilege($eventId)
    {
        return
            DB::table('event_eligibilities')
            ->select(
                'privilege'
            )
            ->where([
                ['tenant_id', '=', $this->requester->getTenantId()],
                ['company_id', '=', $this->requester->getCompanyId()],
                ['event_id', '=', $eventId]
            ])
            ->first();
    }

    public function save($obj)
    {
        $obj['created_by'] = $this->requester->getUserId();
        $obj['created_at'] = Carbon::now();

        return DB::table('events')->insertGetId($obj);
    }

    public function saveParticipant($obj)
    {
        return DB::table('event_eligibilities')->insert($obj);
    }

    public function update($calendarId, $obj)
    {
        $obj['updated_by'] = $this->requester->getUserId();
        $obj['updated_at'] = Carbon::now();

        DB::table('events')
            ->where([
                ['id', $calendarId]
            ])
            ->update($obj);
    }

    public function delete($calendarId)
    {
        DB::table('events')
            ->where([
                ['tenant_id', $this->requester->getTenantId()],
                ['company_id', $this->requester->getCompanyId()],
                ['id', $calendarId]
            ])
            ->delete();
    }

    /**
     * Delete data location group detail from DB
     * @param $locationGroupId
     */
    public function deleteParticipant($calendarId)
    {
        DB::table('event_eligibilities')
            ->where('event_id', $calendarId)
            ->delete();
    }
}
