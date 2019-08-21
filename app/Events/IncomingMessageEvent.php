<?php

namespace App\Events;

class IncomingMessageEvent extends Event
{
    public $msg;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($msg)
    {
        $this->msg = $msg;
    }
}
