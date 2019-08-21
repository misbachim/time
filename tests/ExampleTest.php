<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    use Testable;

    public function setUp()
    {
        parent::setUp();
    }

    public function test()
    {
        $this->assertEquals(1, 1);
    }

    public function tearDown()
    {
    }
}
