<?php

namespace App\Jobs;

class NotifyJob extends Job
{
    public $requester;
    public $sender;
    public $heading;
    public $content;
    public $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requester, $sender, $heading, $content, $url = '')
    {
        $this->requester = $requester;
        $this->sender = $sender;
        $this->heading = $heading;
        $this->content = $content;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        send($this->sender, 'notif', 'cdn', null, [
            'tenantId' => $this->requester->getTenantId(),
            'userId' => $this->requester->getUserId(),
            'locale' => config('app.locale'),
            'heading' => $this->heading,
            'content' => $this->content,
            'url' => $this->url
        ]);
    }

    public function failed(\Exception $ex)
    {
        info($ex);
    }
}
