<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;


class LeaveRequestTest extends TestCase
{
    use Testable;

    /**
     * Test getAll endpoint.
     *
     * @return void
     */
    public function testGetAll()
    {

        $this->json('POST', '/leaveRequest/getAll', [
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
                        'leave',
                        'status',
                        'detail',
                        'weight',
                        'person'
                    ]
                ]
            ]);
    }

    public function testGetOne()
    {
        $this->json('POST', '/leaveRequest/getOne', [
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
                    'leave',
                    'status',
                    'detail',
                    'weight',
                    'person'
                ]
            ]);
    }

    public function testSaveWithoutPic()
    {
        DB::beginTransaction();
        $data = array(
            'data'=> json_encode([
                'companyId' => $this->getRequester()->getCompanyId(),
                'personId' => 1,
                'leaveCode' => 'AL',
                'description' => 'Description',
                'status'=>'P',
                'detail'=>[
                    [
                        'weight'=> 1,
                        'status'=> 'P',
                        'date' => '2018-03-03'
                    ],
                    [
                        'weight'=> 1,
                        'status'=> 'P',
                        'date' => '2018-03-04'
                    ],
                    [
                        'weight'=> 1,
                        'status'=> 'P',
                        'date' => '2018-03-05'
                    ]
                ]
            ]),
            'upload'=>0
        );
        $this->json('POST', '/leaveRequest/save', $data, $this->getReqHeaders())
            ->seeJson([
                "message"=>trans("messages.dataSaved"),
                "status"=>200
            ])
            ->seeJsonStructure([
                "data" => [
                    "id"
                ]
            ]);

        $this->seeInDatabase('leave_requests',
            [
                'person_id' => 1,
                'leave_code' => 'AL',
                'description' => 'Description',
                'status'=>'P',
            ]
        );

        $this->seeInDatabase('leave_request_details',
            [
                'weight'=> 1,
                'status'=> 'P',
                'date' => '2018-03-04'
            ]
        );

        $this->seeInDatabase('leave_request_details',
            [
                'weight'=> 1,
                'status'=> 'P',
                'date' => '2018-03-03'
            ]
        );

        $this->seeInDatabase('leave_request_details',
            [
                'weight'=> 1,
                'status'=> 'P',
                'date' => '2018-03-05'
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
        $this->json('POST', '/leaveRequest/update', $data, $this->getReqHeaders())
            ->seeJson([
                "message"=>trans("messages.dataUpdated"),
                "status"=>200
            ]);

        $this->seeInDatabase('leave_requests',
            [
                'id'=>1,
                'status'=>'C'
            ]
        );
        DB::rollback();
    }
}
