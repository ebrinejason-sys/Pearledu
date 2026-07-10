<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller {
    public function show() { return view('auth.login'); }

    public function store(Request $request, AuditLogger $audit, TenantContext $context): RedirectResponse {
        $data = $request->validate(['email'=>'required|email','password'=>'required|string']);
        $key = 'login:'.strtolower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email'=>'Too many attempts. Try again shortly.']);
        }

        $user = User::whereRaw('lower(email) = lower(?)', [$data['email']])->first();
        if ($user && $user->status === 'invited') {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'Your account is not activated yet. Open the invitation email we sent you to set your password.',
            ]);
        }
        if ($user && $user->status === 'disabled') {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'This account has been disabled. Contact support for help.']);
        }

        if (! Auth::attempt(['email'=>$data['email'],'password'=>$data['password'],'status'=>'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $audit->record('auth.failed', null, ['email'=>$data['email']]);
            throw ValidationException::withMessages(['email'=>'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['last_login_at'=>now()])->save();
        $audit->record('auth.login', $user);

        if (($school = $context->school()) && ! $school->activated_at) {
            $school->forceFill(['activated_at' => now()])->save();
        }

        return redirect()->intended($this->home($user));
    }

    public function destroy(Request $request): RedirectResponse {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function home($user): string {
        if ($user->isPlatformOperator()) return route('platform.dashboard');
        return route('app.home');
    }
}
