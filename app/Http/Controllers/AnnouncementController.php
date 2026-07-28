<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Services\Announcements\AnnouncementAudience;
use App\Services\Sms\SmsSender;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementAudience $audiences) {}

    public function index(TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $announcements = Announcement::where('school_id', $school->id)->orderByDesc('id')->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('name')->get();

        return view('app.announcements.index', compact('school', 'announcements', 'classes'));
    }

    public function store(Request $request, TenantContext $ctx, SmsSender $sms)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $data = $request->validate([
            'title' => 'required|string|max:160',
            'body' => 'required|string',
            'audience' => ['required', Rule::in(['all', 'school', 'class', 'parents', 'guardians', 'students', 'role'])],
            'class_id' => 'nullable|integer|exists:school_classes,id',
            'role_key' => 'nullable|string|max:60',
            'send_sms' => 'sometimes|boolean',
        ]);

        $scoped = $this->audiences->validatedPayload($data);

        if ($scoped['class_id']) {
            abort_unless(
                SchoolClass::query()->where('school_id', $school->id)->whereKey($scoped['class_id'])->exists(),
                404
            );
        }

        $ann = Announcement::create([
            'school_id' => $school->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $scoped['audience'],
            'class_id' => $scoped['class_id'],
            'role_key' => $scoped['role_key'],
            'send_sms' => (bool) ($data['send_sms'] ?? false),
            'created_by' => $request->user()->id,
        ]);

        if ($ann->send_sms) {
            $phones = $this->audiences->recipientPhones(
                $school->id,
                $ann->audience,
                $ann->class_id,
                $ann->role_key,
            );
            foreach ($phones as $phone) {
                try {
                    $sms->send($school->id, $phone, $ann->title.': '.mb_substr($ann->body, 0, 120), 'announcement');
                } catch (\Throwable) {
                    // Delivery failures must not undo the announcement.
                }
            }
        }

        return back()->with('status', 'Announcement published.');
    }
}
