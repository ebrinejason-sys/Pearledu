<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireRecentPlatformAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmPlatformAuthController extends Controller
{
    public function show(): View
    {
        return view('platform.auth.confirm');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.',
            ]);
        }

        RequireRecentPlatformAuth::markConfirmed($request);

        if ($request->session()->has(RequireRecentPlatformAuth::PENDING_KEY)) {
            return redirect()->route('platform.auth.confirm.resume');
        }

        return redirect()->intended(route('platform.dashboard'));
    }

    /** Auto-resubmit a stashed sensitive POST/PUT/PATCH/DELETE after password confirm. */
    public function resume(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->pull(RequireRecentPlatformAuth::PENDING_KEY);
        if (! is_array($pending) || empty($pending['uri']) || empty($pending['method'])) {
            return redirect()->intended(route('platform.dashboard'));
        }

        $uri = (string) $pending['uri'];
        if (! str_starts_with($uri, '/admin/')
            && ! str_starts_with($uri, '/platform/')
            && ! str_starts_with($uri, '/console/')) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.resume-action', [
            'uri' => $uri,
            'method' => strtoupper((string) $pending['method']),
            'input' => is_array($pending['input'] ?? null) ? $pending['input'] : [],
        ]);
    }
}
