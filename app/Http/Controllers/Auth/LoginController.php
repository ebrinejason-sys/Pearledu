<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TwoFactorService;
use App\Services\Tenancy\TenantContext;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(
        Request $request,
        AuditLogger $audit,
        TenantContext $context,
        TwoFactorService $twoFactor,
    ): RedirectResponse {
        $data = $request->validate([
            'identifier' => 'required|string|max:190',
            'password' => 'required|string',
        ]);

        $identifier = trim($data['identifier']);
        $key = 'login:'.strtolower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['identifier' => 'Too many attempts. Try again shortly.']);
        }

        $user = $this->findByIdentifier($identifier);

        if ($user && in_array($user->status, ['invited', 'disabled'], true)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'identifier' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user || $user->status !== 'active' || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            $audit->record('auth.failed', null, ['identifier' => $identifier]);
            throw ValidationException::withMessages(['identifier' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $remember = $request->boolean('remember');

        if ($user->isPlatformOperator()) {
            $request->session()->regenerate();
            $request->session()->put('2fa_pending_user_id', $user->id);
            $request->session()->put('2fa_remember', $remember);

            $twoFactor->sendEmailOtp($user, $request->ip());
            $request->session()->put('2fa_email_sent', true);
            $audit->record('auth.2fa.challenge_sent', $user);

            return redirect('/login/2fa/challenge')
                ->with('status', 'We emailed a 6-digit code to '.$user->email.'.');
        }

        // School users must belong to an active tenant (schools.id).
        if (! $user->primarySchool()) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'identifier' => 'No active school is linked to this account. Contact PearlEdu support.',
            ]);
        }

        Auth::login($user, $remember);
        $this->completeLogin($request, $audit, $context);

        return redirect()->intended($this->home($user));
    }

    public function completeLogin(Request $request, AuditLogger $audit, TenantContext $context): void
    {
        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.login', $user);

        if ($user && ! $user->isPlatformOperator() && ($school = $user->primarySchool())) {
            session([TenantContext::SESSION_SCHOOL_ID => $school->tenantId()]);
            $context->forSchool($school->tenantId());
            if (! $school->activated_at) {
                $school->forceFill(['activated_at' => now()])->save();
            }
        } elseif (($school = $context->school()) && ! $school->activated_at) {
            $school->forceFill(['activated_at' => now()])->save();
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function findByIdentifier(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) {
            return User::whereRaw('lower(email) = lower(?)', [$identifier])->first();
        }

        $phone = PhoneNormalizer::normalize($identifier);
        if ($phone) {
            $user = User::where('phone', $phone)->first();
            if ($user) {
                return $user;
            }
        }

        return User::where('phone', $identifier)->first()
            ?? User::whereRaw('lower(email) = lower(?)', [$identifier])->first();
    }

    private function home(User $user): string
    {
        return $user->appHomeUrl();
    }
}
