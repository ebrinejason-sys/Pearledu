<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\SchoolInvitation;
use App\Services\Auth\InvitationMailer;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use RuntimeException;

class InvitationController extends Controller
{
    public function index(Request $request, TenantContext $context)
    {
        $enteredId = $request->session()->get('platform.entered_school_id');
        // Cross-tenant invite desk always needs platform RLS.
        $context->forPlatform();

        $filter = $request->query('filter', 'open');

        $invitations = SchoolInvitation::query()
            ->with(['school', 'user'])
            ->when($filter === 'open', fn ($q) => $q->whereNull('accepted_at')->where('expires_at', '>', now()))
            ->when($filter === 'expired', fn ($q) => $q->whereNull('accepted_at')->where('expires_at', '<=', now()))
            ->when($filter === 'accepted', fn ($q) => $q->whereNotNull('accepted_at'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        if ($enteredId) {
            $context->forPlatformInSchool((int) $enteredId);
        }

        return view('platform.invitations.index', compact('invitations', 'filter'));
    }

    public function resend(Request $request, SchoolInvitation $invitation, InvitationMailer $mailer)
    {
        $invitation->load('school');
        abort_unless($invitation->school, 404);

        try {
            $mailer->resend($invitation, $invitation->school, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['invitation' => $e->getMessage()]);
        }

        return back()->with('status', 'Invitation resent to '.($invitation->email ?: 'the invitee').'.');
    }
}
