<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Account\InvitationService;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TwoFactorService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function show(Request $request, int $invitation)
    {
        $token = (string) $request->query('token', '');
        try {
            $this->invitations->verify($invitation, $token);
        } catch (\Throwable $e) {
            abort(403, $e->getMessage());
        }

        return view('auth.accept-invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function store(
        Request $request,
        int $invitation,
        AuditLogger $audit,
        TenantContext $context,
        TwoFactorService $twoFactor,
    ) {
        $data = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:10|confirmed',
        ]);

        $user = $this->invitations->accept($invitation, $data['token'], $data['password']);

        // Platform operators must complete the same 2FA / email-OTP pipeline as password login.
        if ($user->isPlatformOperator()) {
            $request->session()->regenerate();
            $request->session()->put('2fa_pending_user_id', $user->id);
            $request->session()->put('2fa_remember', false);

            $twoFactor->sendEmailOtp($user, $request->ip());
            $request->session()->put('2fa_email_sent', true);
            $audit->record('auth.2fa.challenge_sent', $user);
            $audit->record('invitation.accepted.login_pending_2fa', $user);

            return redirect('/login/2fa/challenge')
                ->with('status', 'Account ready. Enter the 6-digit code emailed to '.$user->email.'.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill([
            'last_login_at' => now(),
            'last_seen_at' => now(),
        ])->save();
        $audit->record('auth.login', $user);

        // Same host for every school — pin tenant id from membership (no subdomain required).
        if ($school = $user->primarySchool()) {
            session([TenantContext::SESSION_SCHOOL_ID => $school->tenantId()]);
            $context->forSchool($school->tenantId());
            if (! $school->activated_at) {
                $school->forceFill(['activated_at' => now()])->save();
            }
        }

        return redirect()->route('app.home')->with('status', 'Welcome — your account is ready.');
    }
}
