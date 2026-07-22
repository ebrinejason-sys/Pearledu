<?php
namespace App\Http\Controllers;
use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Services\Sms\SmsSender;
use App\Services\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\Request;

class AnnouncementController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $announcements = Announcement::where('school_id',$school->id)->orderByDesc('id')->get();
        $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
        return view('app.announcements.index', compact('school','announcements','classes'));
    }
    public function store(Request $request, TenantContext $ctx, SmsSender $sms) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'title'=>'required|string|max:160','body'=>'required|string','audience'=>'required|in:school,class,role,guardians',
            'class_id'=>'nullable|integer','role_key'=>'nullable|string','send_sms'=>'sometimes|boolean',
        ]);
        $ann = Announcement::create($data + ['school_id'=>$school->id,'created_by'=>$request->user()->id,'send_sms'=>(bool)($data['send_sms'] ?? false)]);
        if ($ann->send_sms) {
            $phones = User::whereHas('roleAssignments', fn($q)=>$q->where('school_id',$school->id)->where('is_active',true))
                ->whereNotNull('phone')->pluck('phone');
            foreach ($phones as $phone) {
                try { $sms->send($school->id, $phone, $ann->title.': '.mb_substr($ann->body,0,120), 'announcement'); } catch (\Throwable) {}
            }
        }
        return back()->with('status','Announcement published.');
    }
}
