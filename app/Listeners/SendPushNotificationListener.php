<?php

namespace App\Listeners;

use App\Events\PushNotificationRequested;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPushNotificationListener implements ShouldQueue
{
    public function handle(PushNotificationRequested $event): void
    {
        SendPushNotificationJob::dispatch(
            $event->userId,
            $event->title,
            $event->body,
            $event->data
        );
    }
}
