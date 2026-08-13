<?php

namespace App\Services\Sms\Gateway;

use RuntimeException;

/**
 * Blocks non-delivering SMS drivers in production so schools never think
 * messages were sent when they were only faked/logged.
 */
class ProductionBlockedGateway implements SmsGateway
{
    public function __construct(private string $driver) {}

    public function send(string $to, string $body, ?string $senderId): array
    {
        throw new RuntimeException(
            "SMS_DRIVER={$this->driver} is not allowed in production. Set SMS_DRIVER=twilio with live credentials, or keep SMS features unused."
        );
    }
}
