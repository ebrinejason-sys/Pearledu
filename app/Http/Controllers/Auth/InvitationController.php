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

        if (($school = $context->school()) && ! $school->activated_at) {
            $school->forceFill(['activated_at' => now()])->save();
        }

        if ($user->isPlatformOperator()) {
            return redirect()->route('platform.dashboard')->with('status', 'Welcome — your account is ready.');
        }

        return redirect()->route('app.home')->with('status', 'Welcome — your account is ready.');
    }
}
