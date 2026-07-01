<?php
namespace App\Services\Sms\Gateway;
interface SmsGateway {
    /** @return array{ref:string} */
    public function send(string $to, string $body, ?string $senderId): array;
}
