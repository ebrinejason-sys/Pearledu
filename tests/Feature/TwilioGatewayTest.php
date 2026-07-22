<?php
namespace Tests\Feature;

use App\Services\Sms\Gateway\TwilioGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TwilioGatewayTest extends TestCase {
    public function test_send_returns_the_message_sid_as_ref(): void {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        $gateway = new TwilioGateway('ACxxx', 'secret', '+15005550006');
        $result = $gateway->send('+15558675310', 'hello', null);

        $this->assertSame('SM123', $result['ref']);
        Http::assertSent(fn ($request) => $request['From'] === '+15005550006'
            && $request['To'] === '+15558675310'
            && $request['Body'] === 'hello');
    }

    public function test_send_prefers_the_school_sender_id_over_the_default_from(): void {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM456'], 201)]);

        $gateway = new TwilioGateway('ACxxx', 'secret', '+15005550006');
        $gateway->send('+15558675310', 'hello', 'SCHOOLID');

        Http::assertSent(fn ($request) => $request['From'] === 'SCHOOLID');
    }

    public function test_send_throws_when_twilio_returns_an_error(): void {
        Http::fake([
            'api.twilio.com/*' => Http::response(['message' => 'The number is unverified'], 400),
        ]);

        $gateway = new TwilioGateway('ACxxx', 'secret', '+15005550006');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The number is unverified');
        $gateway->send('+15558675310', 'hello', null);
    }

    public function test_send_throws_when_no_sender_is_configured(): void {
        $gateway = new TwilioGateway('ACxxx', 'secret', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no sender number/ID configured');
        $gateway->send('+15558675310', 'hello', null);
    }
}
