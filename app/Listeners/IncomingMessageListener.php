<?php

namespace App\Listeners;

use App\Events\IncomingMessageEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Business\Dao\SyncDao;

class IncomingMessageListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(SyncDao $syncDao)
    {
        $this->syncDao = $syncDao;
    }

    /**
     * Handle the event.
     *
     * @param  IncomingMessageEvent  $event
     * @return void
     */
    public function handle(IncomingMessageEvent $event)
    {
        switch ($event->msg->msgType) {
            case 'params':
                $this->returnContents($event->msg);
                break;
            case 'sync':
                $this->syncData($event->msg);
                break;
            default:
                break;
        }
    }

    public function returnContents($msg)
    {
        $data = [];
        foreach ($msg->data as $param) {
            $data[$param] = 'PLACEHOLDER';
        }
        send('core', $msg->msgType, $msg->sender, $msg->uid, $data);
    }

    public function syncData($msg)
    {
        switch ($msg->sender) {
            case 'um':
                switch ($msg->data->entity) {
                    case 'data_access':
                        $this->syncDao->updateDataAccess($msg->data);
                        break;
                    default:
                        break;
                }
                break;
            default:
                break;
        }
    }
}
