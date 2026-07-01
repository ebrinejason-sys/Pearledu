<?php
namespace App\Http\Controllers;
use App\Services\Sms\SmsCreditService;
use App\Services\Sms\SmsSender;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

/** School staff send SMS, spending the school's platform-allocated credit. */
class SmsController extends Controller {
    public function __construct(
        private SmsSender $sender,
        private SmsCreditService $credits,
        private TenantContext $context,
    ) {}

    public function index() {
        $schoolId = $this->context->schoolId();
        return view('app.sms', ['balance'=>$this->credits->balance($schoolId)]);
    }

    public function send(Request $request) {
        $data = $request->validate([
            'to' => 'required|string|max:20',
            'body' => 'required|string|max:1000',
            'category' => 'nullable|in:auth,fees,results,attendance,general',
        ]);
        $msg = $this->sender->send($this->context->schoolId(), $data['to'], $data['body'], $data['category'] ?? 'general');
        return back()->with('status', "SMS {$msg->status}. Cost {$msg->cost_credits} credit(s).");
    }
}
