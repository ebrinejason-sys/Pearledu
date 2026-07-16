<?php
namespace App\Services\Sms\Gateway;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Staging helper: logs the message; does not deliver. */
class LogGateway implements SmsGateway
{
    public function send(string $to, string $body, ?string $senderId): array
    {
        $ref = 'log_'.Str::random(16);
        Log::info('sms.outbound', [
            'to' => $to,
            'sender_id' => $senderId,
            'body' => $body,
            'ref' => $ref,
        ]);

        return ['ref' => $ref];
    }
}
