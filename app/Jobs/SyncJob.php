<?php

namespace App\Jobs;

class SyncJob extends Job
{
    public $sender;
    public $receiver;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sender, $receiver, $data)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        send($this->sender, 'sync', $this->receiver, null, $this->data);
    }

    public function failed(\Exception $ex)
    {
        info($ex);
    }
}
