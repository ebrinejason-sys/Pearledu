<?php

namespace App\Http\Controllers;

use App\Models\StaffConversation;
use App\Services\Staff\StaffMessageService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StaffMessageController extends Controller
{
    public function index(Request $request, TenantContext $ctx, StaffMessageService $messages): View
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);

        return view('app.staff.messages-index', [
            'school' => $school,
            'conversations' => $messages->inbox($school, $request->user()),
            'directory' => $messages->staffDirectory($school)->where('id', '!=', $request->user()->id)->values(),
        ]);
    }

    public function store(Request $request, TenantContext $ctx, StaffMessageService $messages): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);

        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
            'subject' => 'nullable|string|max:160',
            'body' => 'required|string|max:4000',
        ]);

        try {
            $conversation = $messages->start(
                $school,
                $request->user(),
                $data['user_ids'],
                $data['body'],
                $data['subject'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['user_ids' => $e->getMessage()]);
        }

        return redirect()->route('app.staff.messages.show', $conversation)->with('status', 'Message sent.');
    }

    public function show(Request $request, StaffConversation $conversation, TenantContext $ctx, StaffMessageService $messages): View
    {
        $school = $ctx->school();
        abort_unless($school && (int) $conversation->school_id === (int) $school->id, 404);
        abort_unless($conversation->participants()->where('user_id', $request->user()?->id)->exists(), 403);

        $conversation->load(['participants.user', 'messages.author']);

        return view('app.staff.messages-show', [
            'school' => $school,
            'conversation' => $conversation,
        ]);
    }

    public function reply(Request $request, StaffConversation $conversation, TenantContext $ctx, StaffMessageService $messages): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && (int) $conversation->school_id === (int) $school->id, 404);

        $data = $request->validate([
            'body' => 'required|string|max:4000',
        ]);

        try {
            $messages->reply($conversation, $request->user(), $data['body']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', 'Reply sent.');
    }
}
