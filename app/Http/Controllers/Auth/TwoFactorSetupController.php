<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TwoFactorService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorSetupController extends Controller
{
    public function show(Request $request, TwoFactorService $service)
    {
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));

        if (! $request->session()->has('2fa_setup_secret')) {
            $request->session()->put('2fa_setup_secret', $service->generateSecret());
        }
        $secret = $request->session()->get('2fa_setup_secret');

        return view('auth.two-factor-setup', [
            'qrSvg' => $service->qrCodeSvg($user->email, $secret),
            'manualKey' => $secret,
        ]);
    }

    public function store(
        Request $request,
        TwoFactorService $service,
        AuditLogger $audit,
        TenantContext $context,
        LoginController $login,
    ): RedirectResponse {
        $data = $request->validate(['code' => 'required|string']);
        $user = User::findOrFail($request->session()->get('2fa_pending_user_id'));
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $service->verifyTotp($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code did not match. Try again.']);
        }

        $recoveryCodes = $service->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $service->hashRecoveryCodes($recoveryCodes),
        ])->save();

        Auth::login($user, (bool) $request->session()->get('2fa_remember'));
        $login->completeLogin($request, $audit, $context);
        $audit->record('auth.2fa.enrolled', $user);

        $request->session()->forget(['2fa_pending_user_id', '2fa_remember', '2fa_setup_secret']);
        $request->session()->put('2fa_recovery_codes_display', $recoveryCodes);

        return redirect('/login/2fa/recovery-codes');
    }

    public function showRecoveryCodes(Request $request)
    {
        $codes = $request->session()->pull('2fa_recovery_codes_display');
        abort_unless($codes, 404);

        return view('auth.two-factor-recovery-codes', ['codes' => $codes]);
    }
}
