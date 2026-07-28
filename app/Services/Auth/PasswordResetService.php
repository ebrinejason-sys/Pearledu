<?php

namespace App\Services\Auth;

use App\Mail\Auth\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class PasswordResetService
{
    /** Send a password-reset email. Always returns success to avoid email enumeration. */
    public function sendResetLink(string $email): void
    {
        $user = User::whereRaw('lower(email) = lower(?)', [$email])->first();
        if (! $user || ! $user->email || $user->status === 'disabled') {
            return;
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = URL::route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        Mail::to($user->email)->send(new ResetPasswordMail(
            $user,
            $resetUrl,
            (int) config('auth.passwords.users.expire', 60),
        ));
    }

    /** @return string Password broker status constant */
    public function reset(string $email, string $token, string $password): string
    {
        $user = User::whereRaw('lower(email) = lower(?)', [$email])->first();
        if ($user && $user->status === 'disabled') {
            return Password::INVALID_USER;
        }

        return Password::broker()->reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user, string $password) {
                if ($user->status === 'disabled') {
                    return;
                }

                $user->forceFill([
                    'password' => $password,
                    'status' => 'active',
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }
        );
    }

    /** Invalidate outstanding reset tokens (call when disabling an account). */
    public function revokeTokens(User $user): void
    {
        if (! $user->email) {
            return;
        }

        Password::broker()->deleteToken($user);
    }
}
