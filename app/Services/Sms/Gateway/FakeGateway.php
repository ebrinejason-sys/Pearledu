<?php
namespace App\Services\Sms\Gateway;
use Illuminate\Support\Str;
class FakeGateway implements SmsGateway {
    public function send(string $to, string $body, ?string $senderId): array {
        return ['ref' => 'fake_'.Str::random(16)];   // no real delivery
    }
}
