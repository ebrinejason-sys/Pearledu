<?php
namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Optional Cloudflare Turnstile. When TURNSTILE_SECRET is empty, verification is skipped
 * (honeypot + throttle still apply). Set both site + secret keys to enforce it on
 * contact, onboard, and public /apply.
 */
class TurnstileVerifier
{
    public function enabled(): bool
    {
        return filled(config('services.turnstile.secret'));
    }

    public function siteKey(): ?string
    {
        $key = config('services.turnstile.site_key');

        return filled($key) ? (string) $key : null;
    }

    public function assertValid(Request $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $token = (string) $request->input('cf-turnstile-response', '');
        if ($token === '') {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Please complete the security check.',
            ]);
        }

        $response = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (! $response->ok() || ! ($response->json('success') === true)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Security check failed. Please try again.',
            ]);
        }
    }
}
