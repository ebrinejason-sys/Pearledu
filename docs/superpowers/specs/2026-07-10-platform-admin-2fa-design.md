# Platform Admin 2FA (TOTP + email OTP fallback)

## Problem

`LoginController::store` authenticates with `Auth::attempt(['email', 'password', 'status' => 'active'])` and logs the user straight in. There is no second factor anywhere in the app — `users.two_factor_secret` / `two_factor_confirmed_at` exist as columns but nothing reads or writes them, and `User::hasTwoFactorEnabled()` is unused dead code.

Platform-operator accounts (`is_platform = true`) need a mandatory second factor: an authenticator app (TOTP) as the primary method, with email OTP as a fallback when the admin doesn't have their phone. This does not apply to school staff, teachers, guardians, or students — those accounts keep logging in exactly as they do today.

Separately, there are uncommitted, unwired files implementing a passwordless magic-link sign-in and a working password-reset flow. Password-reset is standard and gets wired in as part of this work. Magic-link is a different auth philosophy nobody asked for and is being removed rather than shipped half-finished.

## Non-goals

- 2FA for non-platform users. `User::isPlatformOperator()` is the only gate; nothing else changes.
- SMS-based OTP — email only, since Resend is already the app's mail transport.
- "Remember this device" / trusted-device skip. Every platform-operator login clears the challenge.
- Magic-link sign-in — removed, not deferred. If passwordless login is wanted later, it gets its own spec and its own security review.

## Data model

Two additions, no removals:

- `users.two_factor_recovery_codes` — nullable, `encrypted` cast, JSON array of `Hash::make()`'d single-use recovery codes. Populated once at enrollment confirmation, mutated (codes removed) on redemption.
- New table `two_factor_email_codes`: `id`, `user_id` (FK), `code_hash`, `expires_at`, `used_at` (nullable), `ip_address`, timestamps. One row per email-OTP send; a code is valid only while `used_at IS NULL AND expires_at > now()`.

`users.two_factor_secret` (already `encrypted` cast) and `two_factor_confirmed_at` are reused as-is — enrollment writes them, nothing else changes about them.

Removed: `app/Models/AuthLoginToken.php` and its migration (`2026_07_03_000002_create_auth_login_tokens_table.php`) — this table existed only to back magic-link tokens.

## Login flow — the state machine

`LoginController::store` changes from `Auth::attempt()` (which logs in as a side effect) to `Auth::validate()` (checks credentials, no session side effect), so a platform operator can be credential-checked without being logged in yet:

```php
if (! Auth::validate(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'])) {
    // existing failure handling: rate limit, audit, ValidationException
}

$user = User::whereRaw('lower(email) = lower(?)', [$data['email']])->firstOrFail();

if (! $user->isPlatformOperator()) {
    Auth::login($user, $request->boolean('remember'));
    // existing post-login logic: session regenerate, last_login_at, audit, activated_at, redirect
    return redirect()->intended($this->home($user));
}

// platform operator: do not log in yet
$request->session()->put('2fa_pending_user_id', $user->id);
$request->session()->put('2fa_remember', $request->boolean('remember'));
return redirect($user->hasTwoFactorEnabled() ? '/login/2fa/challenge' : '/login/2fa/setup');
```

This is the only behavioral change for non-platform users, and it's a no-op for them — they hit `Auth::login()` on the same request as before, just via `validate()` + explicit `login()` instead of `attempt()`'s combined form.

A new middleware, `EnsureTwoFactorPending`, guards `/login/2fa/*`: no `2fa_pending_user_id` in session → redirect to `/login`. This stops someone from hitting the challenge/setup routes cold without having passed password auth first.

### Enrollment — `/login/2fa/setup` (mandatory, first login only)

1. `GET` generates a `Google2FA` secret, stores it un-confirmed in session (not yet on the user row — an abandoned enrollment shouldn't leave a live secret on the account), renders it as an inline SVG QR (via `bacon/bacon-qr-code`, no external network call) plus the manual-entry key as text.
2. `POST` verifies the submitted 6-digit code against the session-held secret. On success: write `two_factor_secret` + `two_factor_confirmed_at` to the user row, generate 10 recovery codes (`Str::random(10)` formatted `xxxx-xxxx`), hash each into `two_factor_recovery_codes`, and render them once on a "save these now" page — this response is the only place the plaintext codes ever exist.
3. `Auth::login($user, session('2fa_remember'))`, clear the `2fa_pending_user_id`/`2fa_remember`/secret-in-session, `AuditLogger->record('auth.2fa.enrolled', $user)`, continue into the same post-login logic (`redirect()->intended($this->home($user))`).

### Challenge — `/login/2fa/challenge` (already-enrolled admins)

One form, three ways to clear it — all behind the existing `RateLimiter` pattern from `LoginController` (5 attempts per `email|ip` key, then a 60s throttle), keyed as `2fa:{user_id}|{ip}`:

- **TOTP code** (default field) — `Google2FA::verifyKey($user->two_factor_secret, $code)`.
- **Email OTP** — a "Send a code to my email instead" button hits `POST /login/2fa/email`, which generates a random 6-digit code, stores `Hash::make($code)` in `two_factor_email_codes` (10-minute expiry), and mails it via the existing Resend transport. `AuditLogger->record('auth.2fa.challenge_sent', $user)`. The same form's code field then also accepts this code — checked against `two_factor_email_codes` by looking up the user's most recent unexpired, unused row and `Hash::check()`.
- **Recovery code** — "Use a recovery code instead" reveals a separate field. Verified and redeemed like this, to close the race the design review flagged (two near-simultaneous submissions of the same code must not both succeed):

  ```php
  DB::transaction(function () use ($user, $submitted) {
      $fresh = User::lockForUpdate()->find($user->id);
      $codes = $fresh->two_factor_recovery_codes ?? [];
      $match = collect($codes)->first(fn ($hash) => Hash::check($submitted, $hash));
      abort_unless($match, 422, 'Invalid recovery code.');
      $fresh->two_factor_recovery_codes = array_values(array_diff($codes, [$match]));
      $fresh->save();
  });
  ```

  The `lockForUpdate()` inside the transaction is what makes this safe — the second concurrent request blocks until the first commits, then sees the code already removed.

Any of the three succeeding: `Auth::login($user, session('2fa_remember'))`, clear pending-session keys, `AuditLogger->record('auth.2fa.success', $user)`, continue into the existing post-login logic. Any failing: `RateLimiter::hit()`, `AuditLogger->record('auth.2fa.failed', $user)`, redisplay with an error — never reveal *which* of the three methods was wrong beyond "invalid code."

## Password-reset wiring

No new logic. `ForgotPasswordController`, `ResetPasswordController`, and `PasswordResetService` already use Laravel's standard `Password` broker correctly (confirmed by reading them — `Password::broker()`, standard token flow). This spec just adds the routes:

```php
Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
```

## Magic-link removal

Delete: `app/Http/Controllers/Auth/MagicLinkController.php`, `app/Services/Auth/MagicLinkService.php`, `app/Mail/Auth/MagicLinkMail.php`, `app/Models/AuthLoginToken.php`, migration `2026_07_03_000002_create_auth_login_tokens_table.php`, and any `resources/views/emails/auth/magic-link*.blade.php`. Remove `test_magic_link_request_sends_email` and `test_magic_link_signs_user_in` from `AuthEmailTest.php`. None of this is routed today, so removal has zero runtime impact.

## Audit logging

Five new event names, following the existing `auth.login` / `auth.failed` convention in `AuditLogger`:

| Event | When |
|---|---|
| `auth.2fa.enrolled` | TOTP setup confirmed |
| `auth.2fa.challenge_sent` | Email OTP mailed |
| `auth.2fa.success` | Any challenge method clears |
| `auth.2fa.failed` | Any challenge method rejected |
| `auth.2fa.recovery_used` | Specifically a recovery-code redemption (in addition to `auth.2fa.success`, since burning a recovery code is worth flagging on its own) |

## Testing

Extend `tests/Feature/AuthEmailTest.php` (after removing the two magic-link tests) and add a new `tests/Feature/PlatformTwoFactorTest.php`:

- Enrollment: correct code confirms and logs in; wrong code re-prompts without confirming.
- Challenge: correct TOTP logs in; wrong TOTP fails + rate-limits after 5; correct email OTP logs in; expired/used email OTP rejected.
- Recovery: valid code logs in and is removed from the stored array; reused code rejected; the concurrent-redemption case (two requests, same code, only one succeeds) using two DB connections or a manual lock-contention simulation.
- Non-platform user: password login still completes in one request, never touches `/login/2fa/*`, `two_factor_*` columns untouched.
- `php artisan db:verify-security` and `php artisan test --filter=TenantIsolationTest` still pass — this touches `User` and login but not tenant-context/RLS, so this is a sanity check rather than an expected-to-fail gate.

## Composer additions

Already added and verified against PHP 8.4 locally (`vendor/` populated, `composer.lock` updated, no conflicts):

- `pragmarx/google2fa` — TOTP secret generation/verification.
- `bacon/bacon-qr-code` — inline SVG QR rendering, no external network call.
- `resend/resend-php` — unrelated fix from the same session, required by Laravel's built-in `resend` mail transport which was configured but silently non-functional without it.
