<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PushNotificationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body = '',
        public array $data = []
    ) {}
}
