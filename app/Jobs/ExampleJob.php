<?php

namespace App\Jobs;

class ExampleJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }


    /**
     * Handle failures.
     *
     * @return void
     */
    public function failed(\Exception $ex)
    {
        info($ex);
    }
}
