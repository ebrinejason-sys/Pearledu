<?php
namespace App\Services\Sms;
use App\Models\SmsMessage;
use App\Models\SmsSetting;
use App\Services\Audit\AuditLogger;
use App\Services\Sms\Gateway\SmsGateway;
use Illuminate\Support\Facades\Auth;

class SmsSender {
    public function __construct(
        private SmsCreditService $credits,
        private SmsGateway $gateway,
        private AuditLogger $audit,
    ) {}

    /** Send one SMS within a school. Charges credit first; records the message. */
    public function send(int $schoolId, string $to, string $body, string $category = 'general'): SmsMessage {
        $settings = SmsSetting::current();
        abort_unless($settings->is_enabled, 503, 'SMS is disabled by the platform.');

        $segments = max(1, (int) ceil(mb_strlen($body) / 160));
        $cost = $segments * $settings->segment_credits;

        $msg = SmsMessage::create([
            'school_id'=>$schoolId, 'to_phone'=>$to, 'body'=>$body,
            'segments'=>$segments, 'cost_credits'=>$cost, 'category'=>$category,
            'status'=>'queued', 'created_by'=>Auth::id(),
        ]);

        // charge BEFORE dispatch (reselling model); refunds on hard failure
        $this->credits->spend($schoolId, $cost, (string) $msg->id, Auth::id());

        try {
            $res = $this->gateway->send($to, $body, $settings->sender_id);
            $msg->update(['status'=>'sent','provider_ref'=>$res['ref']]);
        } catch (\Throwable $e) {
            $msg->update(['status'=>'failed','error'=>$e->getMessage()]);
            $this->credits->topUp($msg->school, $cost, Auth::id(), 'refund:'.$msg->id);
        }
        $this->audit->record('sms.sent', $msg, ['category'=>$category,'cost'=>$cost]);
        return $msg;
    }
}
