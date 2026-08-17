<?php

namespace App\Services\Auth;

use App\Mail\Auth\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PasswordResetService
{
    /** Send a password-reset email. Always returns success to avoid email enumeration. */
    public function sendResetLink(string $email): void
    {
        $user = User::whereRaw('lower(email) = lower(?)', [$email])->first();
        if (! $user || ! $user->email || $user->status === 'disabled') {
            return;
        }

        $this->dispatchResetMail($user);
    }

    /**
     * Admin-initiated reset for a known account. Fails closed if the account cannot receive mail.
     *
     * @throws RuntimeException
     */
    public function sendResetLinkTo(User $user): void
    {
        if (! $user->email) {
            throw new RuntimeException('This account has no email address.');
        }
        if ($user->status === 'disabled') {
            throw new RuntimeException('Cannot send a password reset to a disabled account.');
        }

        $this->dispatchResetMail($user);
    }

    private function dispatchResetMail(User $user): void
    {
        $token = Password::broker()->createToken($user);
        $resetUrl = $this->publicResetUrl($token, $user->email);

        Mail::to($user->email)->send(new ResetPasswordMail(
            $user,
            $resetUrl,
            (int) config('auth.passwords.users.expire', 60),
        ));
    }

    private function publicResetUrl(string $token, string $email): string
    {
        $url = URL::route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);

        $host = (string) config('tenancy.pearledu_landing_host');
        if ($host === '') {
            return $url;
        }

        $parts = parse_url($url);
        $path = ($parts['path'] ?? '').(isset($parts['query']) ? '?'.$parts['query'] : '');

        return 'https://'.$host.$path;
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
