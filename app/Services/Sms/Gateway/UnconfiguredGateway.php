<?php
namespace App\Services\Sms\Gateway;

use RuntimeException;

/** Fail closed when SMS_DRIVER points at a provider that is not implemented yet. */
class UnconfiguredGateway implements SmsGateway
{
    public function __construct(private string $driver) {}

    public function send(string $to, string $body, ?string $senderId): array
    {
        throw new RuntimeException(
            "SMS driver [{$this->driver}] is not wired yet. Use SMS_DRIVER=fake or SMS_DRIVER=log."
        );
    }
}
