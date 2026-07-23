<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Account\InvitationService;
use App\Services\Audit\AuditLogger;
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

    public function store(Request $request, int $invitation, AuditLogger $audit, TenantContext $context)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:10|confirmed',
        ]);

        $user = $this->invitations->accept($invitation, $data['token'], $data['password']);

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.login', $user);

        if ($user->isPlatformOperator()) {
            return redirect()->route('platform.dashboard')->with('status', 'Welcome — your account is ready.');
        }

        // Pin school context on platform hosts (invite links usually open on pearledu.*).
        if ($school = $user->primarySchool()) {
            $context->forSchool((int) $school->id);
            if (! $school->activated_at) {
                $school->forceFill(['activated_at' => now()])->save();
            }

            // Prefer the school subdomain when configured; still works on pearledu.* via pin above.
            $tenantHost = parse_url($school->subdomainUrl(), PHP_URL_HOST);
            if ($tenantHost && $tenantHost !== $request->getHost()) {
                return redirect()->away(rtrim($school->subdomainUrl(), '/').'/home')
                    ->with('status', 'Welcome — your account is ready.');
            }
        }

        return redirect()->route('app.home')->with('status', 'Welcome — your account is ready.');
    }
}
