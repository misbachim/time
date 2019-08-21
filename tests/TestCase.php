<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    protected $baseUrl = 'http://172.19.0.2:8001';

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
