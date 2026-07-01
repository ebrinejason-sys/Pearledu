<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SmsSetting;
use App\Services\Sms\SmsCreditService;
use Illuminate\Http\Request;

/** Platform resells SMS: only here can a school's credit be topped up or settings changed. */
class SmsCreditController extends Controller {
    public function __construct(private SmsCreditService $credits) {}

    public function index() {
        $schools = School::orderBy('name')->get()->map(fn($s) => [
            'school' => $s, 'balance' => $this->credits->balance($s->id),
        ]);
        return view('platform.sms.index', ['rows'=>$schools, 'settings'=>SmsSetting::current()]);
    }

    public function topUp(Request $request, School $school) {
        $data = $request->validate(['credits'=>'required|integer|min:1|max:1000000','reference'=>'nullable|string|max:120']);
        $this->credits->topUp($school, $data['credits'], $request->user()->id, $data['reference'] ?? null);
        return back()->with('status', "Added {$data['credits']} credits to {$school->name}.");
    }

    public function updateSettings(Request $request) {
        $data = $request->validate([
            'provider'=>'required|string|max:60',
            'sender_id'=>'nullable|string|max:20',
            'segment_credits'=>'required|integer|min:1|max:100',
            'is_enabled'=>'boolean',
        ]);
        SmsSetting::current()->update($data + ['is_enabled'=>$request->boolean('is_enabled')]);
        return back()->with('status','SMS settings updated.');
    }
}
