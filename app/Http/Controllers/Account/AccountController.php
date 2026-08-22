<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use App\Services\Platform\ImpersonationService;
use App\Support\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function show()
    {
        return view('account.settings', [
            'themes' => config('themes.themes', []),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if (app(ImpersonationService::class)->isActive()) {
            throw ValidationException::withMessages(['full_name' => 'End imitation before editing this account.']);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => [
                'nullable', 'email', 'max:160', 'required_without:phone',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'nullable', 'string', 'max:20', 'required_without:email',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'preferred_theme' => ['nullable', 'string', Rule::in(array_merge([''], array_keys(config('themes.themes', []))))],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'gender' => [$user->requiresNationalId() ? 'required' : 'nullable', Rule::in(Gender::keys())],
            'nin' => [$user->requiresNationalId() && ! $user->hasNationalIdOnFile() ? 'required' : 'nullable', 'string', 'min:10', 'max:20'],
        ]);

        if (! empty($data['remove_avatar']) && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        }

        $theme = $data['preferred_theme'] ?? null;
        if ($theme === '') {
            $theme = null;
        }

        $user->fill([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'preferred_theme' => $theme,
        ]);
        if (filled($data['nin'] ?? null)) {
            $user->nin = $data['nin'];
        }
        $user->save();

        return back()->with('status', 'Profile saved.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if (app(ImpersonationService::class)->isActive()) {
            throw ValidationException::withMessages(['current_password' => 'End imitation before changing a password.']);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password ?? '')) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }

        $user->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'Password updated.');
    }

    public function destroy(Request $request, AccountDeletionService $deletion)
    {
        if (app(ImpersonationService::class)->isActive()) {
            throw ValidationException::withMessages(['password' => 'End imitation before deleting an account.']);
        }
        $request->validate(['confirm' => 'required|in:DELETE', 'password' => 'required|string']);
        $user = Auth::user();
        if (! Hash::check($request->input('password'), $user->password ?? '')) {
            throw ValidationException::withMessages(['password' => 'Password is incorrect.']);
        }
        Auth::logout();
        $deletion->erase($user, 'self');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account and personal data have been erased.');
    }
}
