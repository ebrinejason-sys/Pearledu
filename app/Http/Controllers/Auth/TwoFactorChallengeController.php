<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\TwoFactorEmailCode;
use App\Models\User;
use App\Mail\Auth\TwoFactorEmailCodeMail;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TwoFactorService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.two-factor-challenge');
    }

    public function sendEmailCode(Request $request, TwoFactorService $service, AuditLogger $audit): RedirectResponse
    {
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));
        $key = '2fa-email:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['code' => 'Too many code requests. Wait a minute and try again.']);
        }
        RateLimiter::hit($key, 60);

        $code = $service->generateEmailOtp();
        TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
        ]);

        Mail::to($user->email)->send(new TwoFactorEmailCodeMail($user, $code));
        $audit->record('auth.2fa.challenge_sent', $user);

        return back()->with('status', 'We emailed you a 6-digit code.');
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

        $verified = false;

        if (! empty($data['recovery_code'])) {
            $verified = $this->redeemRecoveryCode($user, $data['recovery_code']);
            if (! $verified) {
                RateLimiter::hit($key, 60);
                $audit->record('auth.2fa.failed', $user);
                throw ValidationException::withMessages(['recovery_code' => 'Invalid or already-used recovery code.']);
            }
            $audit->record('auth.2fa.recovery_used', $user);
        } elseif (! empty($data['code'])) {
            $verified = $service->verifyTotp($user->two_factor_secret, $data['code'])
                || $this->verifyEmailCode($user, $data['code']);

            if (! $verified) {
                RateLimiter::hit($key, 60);
                $audit->record('auth.2fa.failed', $user);
                throw ValidationException::withMessages(['code' => 'That code did not match. Try again.']);
            }
        } else {
            throw ValidationException::withMessages(['code' => 'Enter a code.']);
        }

        RateLimiter::clear($key);
        Auth::login($user, (bool) $request->session()->get('2fa_remember'));
        $login->completeLogin($request, $audit, $context);
        $audit->record('auth.2fa.success', $user);
        $request->session()->forget(['2fa_pending_user_id', '2fa_remember']);

        return redirect(route('platform.dashboard'));
    }

    private function verifyEmailCode(User $user, string $code): bool
    {
        $candidate = TwoFactorEmailCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
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
