<?php
namespace App\Services\Sms\Gateway;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Real delivery via Twilio's Messages API. */
class TwilioGateway implements SmsGateway
{
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private ?string $defaultFrom,
    ) {}

    public function send(string $to, string $body, ?string $senderId): array
    {
        $from = $senderId ?: $this->defaultFrom;
        if (! $from) {
            throw new RuntimeException('Twilio: no sender number/ID configured (TWILIO_FROM_NUMBER or sms_settings.sender_id).');
        }

        $response = Http::asForm()
            ->withBasicAuth($this->accountSid, $this->authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                'To' => $to,
                'From' => $from,
                'Body' => $body,
            ]);

        if ($response->failed()) {
            $message = $response->json('message') ?? $response->body();
            throw new RuntimeException("Twilio send failed: {$message}");
        }

        $sid = $response->json('sid');
        if (! $sid) {
            throw new RuntimeException('Twilio send failed: response did not include a message SID.');
        }

        return ['ref' => $sid];
    }
}
