<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;


class OvertimeRequestTest extends TestCase
{
    use Testable;

    /**
     * Test getAll endpoint.
     *
     * @return void
     */
    public function testGetAll()
    {

        $this->json('POST', '/overtimeRequest/getAll', [
            'companyId'=>$this->getRequester()->getCompanyId(),
        ])
            ->seeJson([
                "status" => 200,
                "message" => trans('messages.allDataRetrieved')
            ])
            ->seeJsonStructure([
                "data" => [
                    [
                        'id',
                        'description',
                        'personId',
                        'scheduleDate',
                        'requestDate',
                        'timeStart',
                        'timeEnd',
                        'status'
                    ]
                ]
            ]);
    }

    public function testGetOne()
    {
        $this->json('POST', '/overtimeRequest/getOne', [
            'companyId'=>$this->getRequester()->getCompanyId(),
            'id'=>1
        ])
            ->seeJson([
                "status" => 200,
                "message" => trans('messages.dataRetrieved')
            ])
            ->seeJsonStructure([
                "data" => [
                    'description',
                    'personId',
                    'scheduleDate',
                    'requestDate',
                    'timeStart',
                    'timeEnd',
                    'status'
                ]
            ]);

    }

    public function testSave()
    {
        DB::beginTransaction();
        $data = array(
                'companyId' => $this->getRequester()->getCompanyId(),
                'personId' => 1,
                'timeStart' => '2018-11-22 18:00:00',
                'timeEnd' => '2018-11-22 19:00:00',
                'scheduleDate' => '2018-11-22',
                'description' => 'Client Support',
                'status'=>'P'
        );
        $this->json('POST', '/overtimeRequest/save', $data, $this->getReqHeaders())
            ->seeJson([
                "message"=>trans("messages.dataSaved"),
                "status"=>200
            ])
            ->seeJsonStructure([
                "data" => [
                    "id"
                ]
            ]);

        $this->seeInDatabase('overtime_requests',
            [
                'person_id' => '1',
                'time_start' => '2018-11-22 18:00:00',
                'time_end' => '2018-11-22 19:00:00',
                'schedule_date' => '2018-11-22',
                'description' => 'Client Support',
                'status'=>'P'
            ]
        );
        DB::rollback();
    }

    public function testCancel()
    {
        DB::beginTransaction();
        $data = array(
            'companyId' => $this->getRequester()->getCompanyId(),
            'status'=>'C',
            'id'=>1
        );
        $this->json('POST', '/overtimeRequest/update', $data, $this->getReqHeaders())
            ->seeJson([
                "message"=>trans("messages.dataUpdated"),
                "status"=>200
            ]);

        $this->seeInDatabase('overtime_requests',
            [
                'id'=>1,
                'status'=>'C'
            ]
        );

        DB::rollback();

    }
}
