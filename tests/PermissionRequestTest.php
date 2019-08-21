<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;


class PermissionRequestTest extends TestCase
{
    use Testable;

    /**
     * Test getAll endpoint.
     *
     * @return void
     */
    public function testGetAll()
    {

        $this->json('POST', '/permissionRequest/getAll', [
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
                        'permissionDate',
                        'personId',
                        'permitCode',
                        'requestDate',
                        'reason',
                        'status'
                    ]
                ]
            ]);
    }

    public function testGetOne()
    {
        $this->json('POST', '/permissionRequest/getOne', [
            'companyId'=>$this->getRequester()->getCompanyId(),
            'id'=>1
        ])
            ->seeJson([
                "status" => 200,
                "message" => trans('messages.dataRetrieved')
            ])
            ->seeJsonStructure([
                "data" => [
                    'permissionDate',
                    'personId',
                    'permitCode',
                    'requestDate',
                    'fileReferrence',
                    'reason',
                    'status'
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
                'permitCode' => 'PEAO',
                'reason' => 'No Matter',
                'date' => '2018-11-21',
                'status'=>'P'
            ]),
            'upload'=>0
        );
        $this->json('POST', '/permissionRequest/save', $data, $this->getReqHeaders())
            ->seeJson([
                "message"=>trans("messages.dataSaved"),
                "status"=>200
            ])
            ->seeJsonStructure([
                "data" => [
                    "id"
                ]
            ]);

        $this->seeInDatabase('permit_requests',
            [
                'permit_code' => 'PEAO',
                'reason' => 'No Matter',
                'date' => '2018-11-21',
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
        $this->json('POST', '/permissionRequest/update', $data, $this->getReqHeaders())
            ->seeJson([
                "message"=>trans("messages.dataUpdated"),
                "status"=>200
            ]);

        $this->seeInDatabase('permit_requests',
            [
                'id'=>1,
                'status'=>'C'
            ]
        );

        DB::rollback();

    }
}
