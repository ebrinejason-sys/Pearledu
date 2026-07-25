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

        return redirect()->intended(route('platform.dashboard'));
    }
}
