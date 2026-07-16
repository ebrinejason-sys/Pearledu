<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\TwoFactorEmailCode;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TwoFactorService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));

        return view('auth.two-factor-challenge', [
            'email' => $user->email,
            'hasAuthenticator' => $user->hasTwoFactorEnabled(),
        ]);
    }

    public function sendEmailCode(Request $request, TwoFactorService $service, AuditLogger $audit): RedirectResponse
    {
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));
        $key = '2fa-email:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['code' => 'Too many code requests. Wait a minute and try again.']);
        }
        RateLimiter::hit($key, 60);

        $service->sendEmailOtp($user, $request->ip());
        $request->session()->put('2fa_email_sent', true);
        $audit->record('auth.2fa.challenge_sent', $user);

        return back()->with('status', 'We emailed a 6-digit code to '.$user->email.'.');
    }

    public function store(
        Request $request,
        TwoFactorService $service,
        AuditLogger $audit,
        TenantContext $context,
        LoginController $login,
    ): RedirectResponse {
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));
        $key = '2fa:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Try again shortly.']);
        }

        $data = $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $verifiedViaEmail = false;

        if (! empty($data['recovery_code'])) {
            if (! $this->redeemRecoveryCode($user, $data['recovery_code'])) {
                RateLimiter::hit($key, 60);
                $audit->record('auth.2fa.failed', $user);
                throw ValidationException::withMessages(['recovery_code' => 'Invalid or already-used recovery code.']);
            }
            $audit->record('auth.2fa.recovery_used', $user);
        } elseif (! empty($data['code'])) {
            $viaTotp = $service->verifyTotp($user->two_factor_secret, $data['code']);
            $viaEmail = ! $viaTotp && $this->verifyEmailCode($user, $data['code']);

            if (! $viaTotp && ! $viaEmail) {
                RateLimiter::hit($key, 60);
                $audit->record('auth.2fa.failed', $user);
                throw ValidationException::withMessages(['code' => 'That code did not match. Try again.']);
            }
            $verifiedViaEmail = $viaEmail;
        } else {
            throw ValidationException::withMessages(['code' => 'Enter a code.']);
        }

        RateLimiter::clear($key);

        // Unenrolled operators must prove email before authenticator setup (closes password-only bypass).
        if ($verifiedViaEmail && ! $user->hasTwoFactorEnabled()) {
            $request->session()->put('2fa_email_verified', true);

            return redirect('/login/2fa/setup')
                ->with('status', 'Email verified. You can enrol an authenticator, or continue with email verification only.');
        }

        return $this->finishLogin($request, $user, $audit, $context, $login);
    }

    /** Completes login after email OTP when the operator skips authenticator enrollment. */
    public function continueWithoutAuthenticator(
        Request $request,
        AuditLogger $audit,
        TenantContext $context,
        LoginController $login,
    ): RedirectResponse {
        abort_unless($request->session()->get('2fa_email_verified') === true, 403);
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));
        abort_if($user->hasTwoFactorEnabled(), 403);

        return $this->finishLogin($request, $user, $audit, $context, $login);
    }

    private function finishLogin(
        Request $request,
        User $user,
        AuditLogger $audit,
        TenantContext $context,
        LoginController $login,
    ): RedirectResponse {
        Auth::login($user, (bool) $request->session()->get('2fa_remember'));
        $login->completeLogin($request, $audit, $context);
        $audit->record('auth.2fa.success', $user);
        $request->session()->forget([
            '2fa_pending_user_id',
            '2fa_remember',
            '2fa_email_sent',
            '2fa_email_verified',
            '2fa_setup_secret',
        ]);

        return redirect(route('platform.dashboard'));
    }

    private function verifyEmailCode(User $user, string $code): bool
    {
        $candidate = TwoFactorEmailCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $candidate || ! Hash::check($code, $candidate->code_hash)) {
            return false;
        }

        $candidate->forceFill(['used_at' => now()])->save();

        return true;
    }

    private function redeemRecoveryCode(User $user, string $submitted): bool
    {
        return DB::transaction(function () use ($user, $submitted) {
            $fresh = User::lockForUpdate()->find($user->id);
            $codes = $fresh->two_factor_recovery_codes ?? [];
            $match = collect($codes)->first(fn ($hash) => Hash::check($submitted, $hash));

            if (! $match) {
                return false;
            }

            $fresh->two_factor_recovery_codes = array_values(array_diff($codes, [$match]));
            $fresh->save();

            return true;
        });
    }
}
