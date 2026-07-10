# Platform Admin 2FA Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Require platform-operator accounts (`is_platform = true`) to authenticate with a second factor — authenticator app (TOTP) primary, email OTP fallback, recovery codes for last resort — while leaving every non-platform login path untouched. Also wire the already-written password-reset flow into routes, and delete the unwired magic-link scaffolding.

**Architecture:** `LoginController::store` switches from `Auth::attempt()` to `Auth::validate()` so platform operators can be credential-checked without an established session, then branch into a session-flagged "pending 2FA" state that a dedicated middleware guards. A `TwoFactorService` centralizes all TOTP/recovery-code/email-OTP logic so the two new controllers (`TwoFactorSetupController`, `TwoFactorChallengeController`) stay thin.

**Tech Stack:** Laravel 13, PostgreSQL, `pragmarx/google2fa` (TOTP), `bacon/bacon-qr-code` (inline SVG QR, no external network call), Resend mail transport (already fixed this session via `resend/resend-php`).

## Global Constraints

- Non-platform users (`is_platform = false`) must be completely unaffected — same single-request login as today, never redirected to `/login/2fa/*`, `two_factor_*` columns never touched.
- No plaintext recovery code or TOTP secret is ever persisted — secrets use the existing `encrypted` cast, recovery codes are `Hash::make()`'d individually before storage.
- Recovery-code redemption must be race-safe: two concurrent requests submitting the same valid code must not both succeed (`lockForUpdate()` inside a `DB::transaction`).
- Reuse the existing `RateLimiter` pattern from `LoginController` (5 attempts, 60s throttle) for every new attempt surface (TOTP challenge, email-OTP challenge, recovery-code challenge, email-OTP send).
- Reuse the existing `AuditLogger` pattern (`app(AuditLogger::class)->record(string $action, ?Model $entity, array $metadata)`) for every new auth event.
- Views extend `layouts.auth` and reuse the existing `.vx-auth-*` CSS classes already used by `resources/views/auth/login.blade.php` — no new CSS.
- `php artisan db:verify-security` and `php artisan test --filter=TenantIsolationTest` must still pass after this work (final task).

---

### Task 1: `TwoFactorService` — TOTP, QR, recovery codes, email OTP

**Files:**
- Create: `app/Services/Auth/TwoFactorService.php`
- Test: `tests/Unit/TwoFactorServiceTest.php`

**Interfaces:**
- Consumes: nothing from other tasks (pure service, first task).
- Produces (used by Tasks 6, 7):
  - `generateSecret(): string`
  - `qrCodeSvg(string $email, string $secret): string`
  - `verifyTotp(string $secret, string $code): bool`
  - `generateRecoveryCodes(): array` — returns 10 plaintext codes like `"a1b2-c3d4"`
  - `hashRecoveryCodes(array $plaintextCodes): array` — returns array of `Hash::make()` values
  - `generateEmailOtp(): string` — returns a 6-digit numeric string, e.g. `"481093"`

- [ ] **Step 1: Write the failing unit test**

```php
<?php

namespace Tests\Unit;

use App\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    public function test_generate_secret_is_valid_base32(): void
    {
        $service = new TwoFactorService();
        $secret = $service->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16,}$/', $secret);
    }

    public function test_verify_totp_accepts_current_code(): void
    {
        $service = new TwoFactorService();
        $secret = $service->generateSecret();
        $currentCode = (new Google2FA())->getCurrentOtp($secret);

        $this->assertTrue($service->verifyTotp($secret, $currentCode));
    }

    public function test_verify_totp_rejects_wrong_code(): void
    {
        $service = new TwoFactorService();
        $secret = $service->generateSecret();

        $this->assertFalse($service->verifyTotp($secret, '000000'));
    }

    public function test_qr_code_svg_contains_svg_markup(): void
    {
        $service = new TwoFactorService();
        $svg = $service->qrCodeSvg('admin@voxsign.co.ug', $service->generateSecret());

        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_generate_recovery_codes_returns_ten_unique_codes(): void
    {
        $service = new TwoFactorService();
        $codes = $service->generateRecoveryCodes();

        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]{4}-[a-z0-9]{4}$/', $code);
        }
    }

    public function test_hash_recovery_codes_produces_verifiable_hashes(): void
    {
        $service = new TwoFactorService();
        $plain = $service->generateRecoveryCodes();
        $hashed = $service->hashRecoveryCodes($plain);

        $this->assertCount(10, $hashed);
        $this->assertTrue(Hash::check($plain[0], $hashed[0]));
    }

    public function test_generate_email_otp_is_six_digits(): void
    {
        $service = new TwoFactorService();
        $otp = $service->generateEmailOtp();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `.php84/php.exe vendor/bin/phpunit tests/Unit/TwoFactorServiceTest.php`
Expected: FAIL with "Class \"App\Services\Auth\TwoFactorService\" not found"

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace App\Services\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(string $email, string $secret): string
    {
        $issuer = config('app.name');
        $otpauthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
        );

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(): array
    {
        return array_map(
            fn () => Str::lower(Str::random(4)).'-'.Str::lower(Str::random(4)),
            range(1, 10),
        );
    }

    public function hashRecoveryCodes(array $plaintextCodes): array
    {
        return array_map(fn (string $code) => Hash::make($code), $plaintextCodes);
    }

    public function generateEmailOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `.php84/php.exe vendor/bin/phpunit tests/Unit/TwoFactorServiceTest.php`
Expected: PASS (7 tests)

- [ ] **Step 5: Commit — including the pending composer changes from this session**

`composer.json` and `composer.lock` already have uncommitted changes from earlier in this session (`resend/resend-php`, added while fixing the mail transport; `pragmarx/google2fa` and `bacon/bacon-qr-code`, added while verifying this plan). This is the first task that uses any of them, so it's the right place to land all three in one commit:

```bash
git add app/Services/Auth/TwoFactorService.php tests/Unit/TwoFactorServiceTest.php composer.json composer.lock
git commit -m "Add TwoFactorService; add resend-php, google2fa, bacon-qr-code dependencies"
```

---

### Task 2: Data model — migrations + `User` model

**Files:**
- Create: `database/migrations/2026_07_10_000002_add_two_factor_recovery_codes_to_users_table.php`
- Create: `database/migrations/2026_07_10_000003_create_two_factor_email_codes_table.php`
- Modify: `app/Models/User.php:15-22`

**Interfaces:**
- Consumes: nothing.
- Produces (used by Tasks 3, 5, 6, 7): `users.two_factor_recovery_codes` (encrypted JSON array cast), `two_factor_email_codes` table (`user_id`, `code_hash`, `expires_at`, `used_at`, `ip_address`).

- [ ] **Step 1: Write the migrations**

```php
<?php
// database/migrations/2026_07_10_000002_add_two_factor_recovery_codes_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('two_factor_recovery_codes');
        });
    }
};
```

```php
<?php
// database/migrations/2026_07_10_000003_create_two_factor_email_codes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('two_factor_email_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('code_hash');
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
            $t->index(['user_id', 'used_at', 'expires_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('two_factor_email_codes');
    }
};
```

- [ ] **Step 2: Run the migrations**

Run: `.php84/php.exe artisan migrate` (start the portable Postgres first if not running: `./.pgsql/pgsql/bin/pg_ctl.exe -D "$(pwd)/.pgsql/data-local" -l "$(pwd)/.pgsql/data-local/server.log" start`)
Expected: both migrations show `DONE`

- [ ] **Step 3: Update `User` model casts and fillable**

In `app/Models/User.php`, change lines 15-22 from:

```php
    protected $fillable = ['full_name','email','phone','password','status','is_platform','preferred_theme','last_login_at'];
    protected $hidden = ['password','remember_token','two_factor_secret'];

    protected function casts(): array {
        return ['password' => 'hashed', 'is_platform' => 'boolean',
                'two_factor_secret' => 'encrypted', 'two_factor_confirmed_at' => 'datetime',
                'last_login_at' => 'datetime'];
    }
```

to:

```php
    protected $fillable = ['full_name','email','phone','password','status','is_platform','preferred_theme','last_login_at'];
    protected $hidden = ['password','remember_token','two_factor_secret','two_factor_recovery_codes'];

    protected function casts(): array {
        return ['password' => 'hashed', 'is_platform' => 'boolean',
                'two_factor_secret' => 'encrypted', 'two_factor_confirmed_at' => 'datetime',
                'two_factor_recovery_codes' => 'encrypted:array',
                'last_login_at' => 'datetime'];
    }
```

- [ ] **Step 4: Add a `platform()` factory state for tests**

In `database/factories/UserFactory.php`, add after the `unverified()` method (before the closing `}`):

```php
    public function platform(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_platform' => true,
        ]);
    }
```

- [ ] **Step 5: Verify with tinker**

Run: `.php84/php.exe artisan tinker --execute="dd(\Illuminate\Support\Facades\Schema::hasColumn('users', 'two_factor_recovery_codes'), \Illuminate\Support\Facades\Schema::hasTable('two_factor_email_codes'));"`
Expected: `true` and `true`

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_10_000002_add_two_factor_recovery_codes_to_users_table.php database/migrations/2026_07_10_000003_create_two_factor_email_codes_table.php app/Models/User.php database/factories/UserFactory.php
git commit -m "Add two-factor recovery codes column and email-OTP table"
```

---

### Task 3: `TwoFactorEmailCode` model + mail

**Files:**
- Create: `app/Models/TwoFactorEmailCode.php`
- Create: `app/Mail/Auth/TwoFactorEmailCodeMail.php`
- Create: `resources/views/emails/auth/two-factor-code.blade.php`
- Test: `tests/Unit/TwoFactorEmailCodeTest.php`

**Interfaces:**
- Consumes: `two_factor_email_codes` table (Task 2).
- Produces (used by Task 7): `TwoFactorEmailCode::isValid(): bool`, `TwoFactorEmailCode` model with `user_id`, `code_hash`, `expires_at`, `used_at`, `ip_address`; `TwoFactorEmailCodeMail(User $user, string $code)` mailable.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\TwoFactorEmailCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorEmailCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_valid_when_unused_and_unexpired(): void
    {
        $user = User::factory()->create();
        $code = TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => bcrypt('481093'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($code->isValid());
    }

    public function test_is_invalid_when_expired(): void
    {
        $user = User::factory()->create();
        $code = TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => bcrypt('481093'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse($code->isValid());
    }

    public function test_is_invalid_when_used(): void
    {
        $user = User::factory()->create();
        $code = TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => bcrypt('481093'),
            'expires_at' => now()->addMinutes(10),
            'used_at' => now(),
        ]);

        $this->assertFalse($code->isValid());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `.php84/php.exe vendor/bin/phpunit tests/Unit/TwoFactorEmailCodeTest.php`
Expected: FAIL with "Class \"App\Models\TwoFactorEmailCode\" not found"

- [ ] **Step 3: Write the model**

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorEmailCode extends Model
{
    protected $fillable = ['user_id', 'code_hash', 'expires_at', 'used_at', 'ip_address'];

    protected function casts(): array {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isValid(): bool {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `.php84/php.exe vendor/bin/phpunit tests/Unit/TwoFactorEmailCodeTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Write the mailable**

```php
<?php
namespace App\Mail\Auth;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorEmailCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public int $expiresMinutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Your '.config('app.name').' sign-in code',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.two-factor-code');
    }
}
```

- [ ] **Step 6: Write the email view**

```blade
{{-- resources/views/emails/auth/two-factor-code.blade.php --}}
<p>Hi {{ $user->full_name }},</p>
<p>Your {{ config('app.name') }} sign-in code is:</p>
<p style="font-size:28px;font-weight:700;letter-spacing:4px;">{{ $code }}</p>
<p>This code expires in {{ $expiresMinutes }} minutes. If you didn't request this, you can ignore this email.</p>
```

- [ ] **Step 7: Commit**

```bash
git add app/Models/TwoFactorEmailCode.php app/Mail/Auth/TwoFactorEmailCodeMail.php resources/views/emails/auth/two-factor-code.blade.php tests/Unit/TwoFactorEmailCodeTest.php
git commit -m "Add TwoFactorEmailCode model and delivery mail"
```

---

### Task 4: `EnsureTwoFactorPending` middleware

**Files:**
- Create: `app/Http/Middleware/EnsureTwoFactorPending.php`
- Modify: `bootstrap/app.php:22-25`
- Test: `tests/Feature/EnsureTwoFactorPendingTest.php`

**Interfaces:**
- Consumes: `session('2fa_pending_user_id')` (set by Task 5).
- Produces (used by Task 7's routes): middleware alias `'2fa.pending'`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnsureTwoFactorPendingTest extends TestCase
{
    public function test_challenge_route_redirects_to_login_without_pending_session(): void
    {
        $response = $this->get('/login/2fa/challenge');

        $response->assertRedirect('/login');
    }
}
```

Note: this test targets the `/login/2fa/challenge` route added in Task 7 — it will fail with a 404 until that route exists too. That's expected; re-run it again at the end of Task 7 to confirm it passes for the real reason.

- [ ] **Step 2: Run test to verify it fails**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/EnsureTwoFactorPendingTest.php`
Expected: FAIL (404, route doesn't exist yet)

- [ ] **Step 3: Write the middleware**

```php
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorPending
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('2fa_pending_user_id')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the middleware alias**

In `bootstrap/app.php`, change:

```php
use App\Http\Middleware\EnsurePlatformOperator;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\ResolveTenant;
```

to:

```php
use App\Http\Middleware\EnsurePlatformOperator;
use App\Http\Middleware\EnsureTwoFactorPending;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\ResolveTenant;
```

and change:

```php
        $middleware->alias([
            'platform'   => EnsurePlatformOperator::class,
            'permission' => RequirePermission::class,
        ]);
```

to:

```php
        $middleware->alias([
            'platform'    => EnsurePlatformOperator::class,
            'permission'  => RequirePermission::class,
            '2fa.pending' => EnsureTwoFactorPending::class,
        ]);
```

- [ ] **Step 5: Leave the test failing for now**

This test will pass once Task 7 adds the `/login/2fa/challenge` route with this middleware attached — don't chase it further in this task.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/EnsureTwoFactorPending.php bootstrap/app.php tests/Feature/EnsureTwoFactorPendingTest.php
git commit -m "Add EnsureTwoFactorPending middleware"
```

---

### Task 5: `LoginController` — branch platform operators into pending-2FA state

**Files:**
- Modify: `app/Http/Controllers/Auth/LoginController.php:16-53`
- Test: `tests/Feature/AuthEmailTest.php` (add a case, don't remove any yet — that's Task 9)

**Interfaces:**
- Consumes: `TwoFactorService` not needed here (only `User::isPlatformOperator()`, `User::hasTwoFactorEnabled()` — both already exist).
- Produces (used by Task 6, 7): `session('2fa_pending_user_id')`, `session('2fa_remember')` — the contract every later task relies on.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/AuthEmailTest.php`, at the end of the class — after `test_platform_onboarding_sends_invitation_email` and before the closing `}` (needs `use App\Models\User;`, already present at the top of the file). Appending here, rather than inserting mid-file, keeps the magic-link tests' line numbers (used in Task 9's removal step) stable:

```php
    public function test_non_platform_login_is_unaffected_by_2fa(): void
    {
        $user = User::factory()->create([
            'email' => 'teacher@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $response = $this->post('/login', [
            'email' => 'teacher@test.local',
            'password' => 'password1234',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertSessionMissing('2fa_pending_user_id');
    }

    public function test_platform_login_is_redirected_to_2fa_setup_when_unenrolled(): void
    {
        $user = User::factory()->platform()->create([
            'email' => 'newadmin@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
        ]);

        $response = $this->post('/login', [
            'email' => 'newadmin@test.local',
            'password' => 'password1234',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login/2fa/setup');
        $response->assertSessionHas('2fa_pending_user_id', $user->id);
    }

    public function test_platform_login_is_redirected_to_2fa_challenge_when_enrolled(): void
    {
        $user = User::factory()->platform()->create([
            'email' => 'enrolledadmin@test.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password1234'),
            'two_factor_secret' => 'ADUMMYSECRETKEYFORTESTS',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'enrolledadmin@test.local',
            'password' => 'password1234',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login/2fa/challenge');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `.php84/php.exe vendor/bin/phpunit --filter=test_platform_login_is_redirected tests/Feature/AuthEmailTest.php`
Expected: FAIL — currently every successful password login goes straight to `redirect()->intended()`, never to `/login/2fa/*`.

- [ ] **Step 3: Rewrite `LoginController::store`**

Replace `app/Http/Controllers/Auth/LoginController.php:16-53` with:

```php
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

        if (! Auth::validate(['email'=>$data['email'],'password'=>$data['password'],'status'=>'active'])) {
            RateLimiter::hit($key, 60);
            $audit->record('auth.failed', null, ['email'=>$data['email']]);
            throw ValidationException::withMessages(['email'=>'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $remember = $request->boolean('remember');

        if ($user->isPlatformOperator()) {
            $request->session()->put('2fa_pending_user_id', $user->id);
            $request->session()->put('2fa_remember', $remember);

            return redirect($user->hasTwoFactorEnabled() ? '/login/2fa/challenge' : '/login/2fa/setup');
        }

        Auth::login($user, $remember);
        $this->completeLogin($request, $audit, $context);

        return redirect()->intended($this->home($user));
    }

    public function completeLogin(Request $request, AuditLogger $audit, TenantContext $context): void {
        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['last_login_at'=>now()])->save();
        $audit->record('auth.login', $user);

        if (($school = $context->school()) && ! $school->activated_at) {
            $school->forceFill(['activated_at' => now()])->save();
        }
    }
```

Note: `completeLogin()` is now `public` (not `private`) because Tasks 6 and 7 call it from `TwoFactorSetupController` and `TwoFactorChallengeController` after a successful enrollment/challenge. `home()` stays `private` — Tasks 6/7 call `$this->home($user)` isn't available to them since it's a different controller; those controllers redirect via `route('platform.dashboard')` directly (platform operators always land on the platform dashboard, never `app.home` — see `LoginController::home()`'s own logic, which only reaches `app.home` for non-platform users who never go through 2FA).

- [ ] **Step 4: Run tests to verify they pass**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/AuthEmailTest.php`
Expected: PASS for the 3 new tests. `test_magic_link_signs_user_in` and `test_magic_link_request_sends_email` still pass unchanged (untouched by this task — Task 9 removes them). `test_invited_user_gets_helpful_login_error` still passes (that path returns before reaching the platform branch).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/LoginController.php tests/Feature/AuthEmailTest.php
git commit -m "Branch platform-operator logins into pending-2FA session state"
```

---

### Task 6: `TwoFactorSetupController` — mandatory enrollment

**Files:**
- Create: `app/Http/Controllers/Auth/TwoFactorSetupController.php`
- Create: `resources/views/auth/two-factor-setup.blade.php`
- Create: `resources/views/auth/two-factor-recovery-codes.blade.php`
- Modify: `routes/auth.php`
- Test: `tests/Feature/PlatformTwoFactorTest.php` (new file, shared with Task 7)

**Interfaces:**
- Consumes: `session('2fa_pending_user_id')`, `session('2fa_remember')` (Task 5); `TwoFactorService` (Task 1); `LoginController::completeLogin()` (Task 5, now public).
- Produces (used by Task 7's tests, via the same test file): nothing new — this is a leaf controller.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PlatformTwoFactorTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class PlatformTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function loginToPending(string $email = 'newadmin@test.local'): User
    {
        $user = User::factory()->platform()->create([
            'email' => $email,
            'password' => Hash::make('password1234'),
        ]);

        $this->post('/login', ['email' => $email, 'password' => 'password1234']);

        return $user;
    }

    public function test_setup_page_shows_qr_and_manual_key(): void
    {
        $this->loginToPending();

        $response = $this->get('/login/2fa/setup');

        $response->assertOk();
        $response->assertSee('<svg', false);
    }

    public function test_setup_confirms_with_correct_code_and_logs_in(): void
    {
        $user = $this->loginToPending();
        $secret = session('2fa_setup_secret');
        $this->assertNotNull($secret, 'setup GET must seed a secret into session before POST is tested');

        $code = (new Google2FA())->getCurrentOtp($secret);

        $response = $this->post('/login/2fa/setup', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('platform.dashboard'));
        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotEmpty($user->two_factor_recovery_codes);
    }

    public function test_setup_rejects_wrong_code(): void
    {
        $this->loginToPending();
        $this->get('/login/2fa/setup');

        $response = $this->post('/login/2fa/setup', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/PlatformTwoFactorTest.php`
Expected: FAIL (404 — routes don't exist yet)

- [ ] **Step 3: Write the controller**

```php
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
```

- [ ] **Step 4: Write the setup view**

```blade
{{-- resources/views/auth/two-factor-setup.blade.php --}}
@extends('layouts.auth')
@section('title','Set up your authenticator')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <div class="vx-auth-card">
        <h1>Set up your authenticator</h1>
        <p>Scan this QR code with Google Authenticator, Authy, or any TOTP app, then enter the 6-digit code it shows.</p>
        <div>{!! $qrSvg !!}</div>
        <p>Can't scan? Enter this key manually: <code>{{ $manualKey }}</code></p>
        <form method="post" action="/login/2fa/setup">
          @csrf
          <label>6-digit code</label>
          <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus>
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Confirm and continue</button>
        </form>
      </div>
    </div>
  </div>
@endsection
```

```blade
{{-- resources/views/auth/two-factor-recovery-codes.blade.php --}}
@extends('layouts.auth')
@section('title','Save your recovery codes')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <div class="vx-auth-card">
        <h1>Save your recovery codes</h1>
        <p>Each code works once, if you lose access to both your authenticator app and your email. Save them somewhere safe now — they will not be shown again.</p>
        <ul>
          @foreach($codes as $code)
            <li><code>{{ $code }}</code></li>
          @endforeach
        </ul>
        <a class="btn" href="{{ route('platform.dashboard') }}">I've saved these — continue</a>
      </div>
    </div>
  </div>
@endsection
```

- [ ] **Step 5: Wire the routes**

In `routes/auth.php`, add inside the existing `Route::middleware('web')->group(...)` block, right after the `/login` routes:

```php
    Route::middleware('guest')->group(function () {
        Route::get('/login/2fa/setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'show'])->middleware('2fa.pending');
        Route::post('/login/2fa/setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'store'])->middleware(['2fa.pending','throttle:10,1']);
        Route::get('/login/2fa/recovery-codes', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'showRecoveryCodes']);
    });
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/PlatformTwoFactorTest.php`
Expected: PASS (3 tests)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Auth/TwoFactorSetupController.php resources/views/auth/two-factor-setup.blade.php resources/views/auth/two-factor-recovery-codes.blade.php routes/auth.php tests/Feature/PlatformTwoFactorTest.php
git commit -m "Add mandatory TOTP enrollment flow for platform operators"
```

---

### Task 7: `TwoFactorChallengeController` — TOTP / email OTP / recovery-code login

**Files:**
- Create: `app/Http/Controllers/Auth/TwoFactorChallengeController.php`
- Create: `resources/views/auth/two-factor-challenge.blade.php`
- Modify: `routes/auth.php`
- Test: `tests/Feature/PlatformTwoFactorTest.php` (append)

**Interfaces:**
- Consumes: `TwoFactorService` (Task 1), `TwoFactorEmailCode` + `TwoFactorEmailCodeMail` (Task 3), `EnsureTwoFactorPending` (Task 4), `LoginController::completeLogin()` (Task 5).
- Produces: nothing further downstream — last controller in the chain.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PlatformTwoFactorTest.php`:

```php
    private function loginEnrolledToPending(): array
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->platform()->create([
            'email' => 'enrolled@test.local',
            'password' => Hash::make('password1234'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', ['email' => 'enrolled@test.local', 'password' => 'password1234']);

        return [$user, $secret];
    }

    public function test_challenge_succeeds_with_correct_totp(): void
    {
        [$user, $secret] = $this->loginEnrolledToPending();
        $code = (new Google2FA())->getCurrentOtp($secret);

        $response = $this->post('/login/2fa/challenge', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('platform.dashboard'));
    }

    public function test_challenge_fails_with_wrong_totp(): void
    {
        $this->loginEnrolledToPending();

        $response = $this->post('/login/2fa/challenge', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_email_otp_send_and_verify(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        [$user] = $this->loginEnrolledToPending();

        $this->post('/login/2fa/email');

        // The plaintext code only ever exists in the outgoing mail (the DB row stores a
        // hash), so capture it off the faked mailable's public property.
        $capturedCode = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Auth\TwoFactorEmailCodeMail::class, function ($mail) use ($user, &$capturedCode) {
            $capturedCode = $mail->code;
            return $mail->hasTo($user->email);
        });
        $this->assertNotNull($capturedCode);

        $response = $this->post('/login/2fa/challenge', ['code' => $capturedCode]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_recovery_code_redeems_once(): void
    {
        [$user, $secret] = $this->loginEnrolledToPending();
        $service = new \App\Services\Auth\TwoFactorService();
        $plainCodes = $service->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $service->hashRecoveryCodes($plainCodes)])->save();

        $response = $this->post('/login/2fa/challenge', ['recovery_code' => $plainCodes[0]]);
        $this->assertAuthenticatedAs($user);

        Auth::logout();
        $this->post('/login', ['email' => 'enrolled@test.local', 'password' => 'password1234']);
        $response2 = $this->post('/login/2fa/challenge', ['recovery_code' => $plainCodes[0]]);

        $this->assertGuest();
        $response2->assertSessionHasErrors('recovery_code');
    }
```

Add `use Illuminate\Support\Facades\Auth;` to the top of `PlatformTwoFactorTest.php` for the `recovery_code` test's `Auth::logout()` call.

- [ ] **Step 2: Run tests to verify they fail**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/PlatformTwoFactorTest.php`
Expected: FAIL (404 — challenge routes don't exist yet)

- [ ] **Step 3: Write the controller**

```php
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
```

- [ ] **Step 4: Write the challenge view**

```blade
{{-- resources/views/auth/two-factor-challenge.blade.php --}}
@extends('layouts.auth')
@section('title','Verify it\'s you')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <div class="vx-auth-card">
        <h1>Verify it's you</h1>
        @if(session('status'))<div class="vx-auth-status">{{ session('status') }}</div>@endif
        <form method="post" action="/login/2fa/challenge">
          @csrf
          <label>Authenticator code</label>
          <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus>
          @error('code')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Verify</button>
        </form>
        <form method="post" action="/login/2fa/email">
          @csrf
          <button class="btn-link" type="submit">Send a code to my email instead</button>
        </form>
        <details>
          <summary>Use a recovery code instead</summary>
          <form method="post" action="/login/2fa/challenge">
            @csrf
            <label>Recovery code</label>
            <input name="recovery_code" type="text" autocomplete="off">
            @error('recovery_code')<div class="err">{{ $message }}</div>@enderror
            <button class="btn" type="submit">Verify</button>
          </form>
        </details>
      </div>
    </div>
  </div>
@endsection
```

- [ ] **Step 5: Wire the routes**

In `routes/auth.php`, inside the `Route::middleware('guest')->group(...)` block added in Task 6, add:

```php
        Route::get('/login/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'show'])->middleware('2fa.pending');
        Route::post('/login/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'store'])->middleware(['2fa.pending','throttle:10,1']);
        Route::post('/login/2fa/email', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'sendEmailCode'])->middleware(['2fa.pending','throttle:5,1']);
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/PlatformTwoFactorTest.php tests/Feature/EnsureTwoFactorPendingTest.php`
Expected: PASS — all `PlatformTwoFactorTest` cases (7 total across Tasks 6+7), and `EnsureTwoFactorPendingTest` now passes for the real reason (route exists, middleware redirects).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Auth/TwoFactorChallengeController.php resources/views/auth/two-factor-challenge.blade.php routes/auth.php tests/Feature/PlatformTwoFactorTest.php
git commit -m "Add TOTP/email-OTP/recovery-code login challenge for platform operators"
```

---

### Task 8: Wire password-reset routes

**Files:**
- Modify: `routes/auth.php`

**Interfaces:**
- Consumes: `ForgotPasswordController`, `ResetPasswordController` (already exist, untouched), `PasswordResetService` (already exists, untouched).
- Produces: named routes `password.request`, `password.email`, `password.reset`, `password.update` — required by `tests/Feature/AuthEmailTest.php::test_forgot_password_sends_reset_email` and `::test_password_can_be_reset_via_email_link`, which already call `route('password.email')` / `route('password.update')` and currently fail with `RouteNotFoundException`.

- [ ] **Step 1: Confirm the tests currently fail for the expected reason**

Run: `.php84/php.exe vendor/bin/phpunit --filter=test_forgot_password_sends_reset_email tests/Feature/AuthEmailTest.php`
Expected: FAIL with `RouteNotFoundException: Route [password.email] not defined.`

- [ ] **Step 2: Add the routes**

In `routes/auth.php`, add near the top of the `Route::middleware('web')->group(...)` block, after the `/login` routes and before the `/invitations/...` routes:

```php
    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'show'])->name('password.request');
        Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'show'])->name('password.reset');
        Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
    });
```

(This is a second `guest` group alongside the one from Task 6/7 — Laravel allows multiple groups with the same middleware in one file; don't merge them, they're logically separate feature areas.)

- [ ] **Step 3: Run tests to verify they pass**

Run: `.php84/php.exe vendor/bin/phpunit --filter=test_forgot_password_sends_reset_email --filter=test_password_can_be_reset_via_email_link tests/Feature/AuthEmailTest.php`
Expected: PASS (both tests)

- [ ] **Step 4: Commit**

```bash
git add routes/auth.php
git commit -m "Wire password-reset routes"
```

---

### Task 9: Remove magic-link scaffolding

**Files:**
- Delete: `app/Http/Controllers/Auth/MagicLinkController.php`
- Delete: `app/Services/Auth/MagicLinkService.php`
- Delete: `app/Mail/Auth/MagicLinkMail.php`
- Delete: `app/Models/AuthLoginToken.php`
- Delete: `database/migrations/2026_07_03_000002_create_auth_login_tokens_table.php`
- Delete: `resources/views/emails/auth/magic-link.blade.php`
- Modify: `tests/Feature/AuthEmailTest.php` (remove 2 tests)

**Interfaces:** none — this task only removes code nothing else in this plan depends on (confirmed: no route in `routes/auth.php` ever referenced `MagicLinkController`, since Task 6/7/8 only added 2FA and password-reset routes).

- [ ] **Step 1: Check whether the auth_login_tokens migration has run locally**

Run: `.php84/php.exe artisan migrate:status | grep auth_login_tokens`

If it shows `Ran`, roll back just the table before deleting the migration file (don't use `migrate:rollback --step`, which would also roll back unrelated later migrations — drop the table directly instead):

```bash
.php84/php.exe artisan tinker --execute="\Illuminate\Support\Facades\Schema::dropIfExists('auth_login_tokens'); \Illuminate\Support\Facades\DB::table('migrations')->where('migration', '2026_07_03_000002_create_auth_login_tokens_table')->delete();"
```

If it shows nothing (migration never ran here), skip straight to Step 2.

- [ ] **Step 2: Delete the files**

```bash
git rm -f app/Http/Controllers/Auth/MagicLinkController.php app/Services/Auth/MagicLinkService.php app/Mail/Auth/MagicLinkMail.php app/Models/AuthLoginToken.php database/migrations/2026_07_03_000002_create_auth_login_tokens_table.php
rm -f resources/views/emails/auth/magic-link.blade.php
```

- [ ] **Step 3: Remove the magic-link tests**

In `tests/Feature/AuthEmailTest.php`, delete the `test_magic_link_request_sends_email` and `test_magic_link_signs_user_in` methods (lines 55-80 as of this plan's writing — confirm by searching for `magic` in the file, since line numbers may have shifted from earlier tasks' edits), and remove the now-unused `use App\Mail\Auth\MagicLinkMail;` and `use App\Models\AuthLoginToken;` imports from the top of the file.

- [ ] **Step 4: Run the full auth test suite**

Run: `.php84/php.exe vendor/bin/phpunit tests/Feature/AuthEmailTest.php tests/Feature/PlatformTwoFactorTest.php tests/Feature/EnsureTwoFactorPendingTest.php tests/Unit/TwoFactorServiceTest.php tests/Unit/TwoFactorEmailCodeTest.php`
Expected: PASS — every remaining test in `AuthEmailTest.php` (forgot-password x2, invited-user, platform-onboarding, the 3 new login-branching tests from Task 5), all `PlatformTwoFactorTest` cases, `EnsureTwoFactorPendingTest`, both unit test files.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/AuthEmailTest.php
git commit -m "Remove unwired magic-link auth scaffolding"
```

---

### Task 10: Final verification and push

**Files:** none (verification only)

- [ ] **Step 1: Run the full test suite**

Run: `.php84/php.exe artisan test`
Expected: all tests pass, no failures introduced elsewhere.

- [ ] **Step 2: Run the project's hard security gates**

Run: `.php84/php.exe artisan db:verify-security`
Expected: prints `OK` / exits 0 — this touches `User` and `LoginController` but not `ResolveTenant` or RLS policy, so this is confirming no regression, not expecting a new pass condition.

Run: `.php84/php.exe artisan test --filter=TenantIsolationTest`
Expected: PASS, unchanged from before this work.

- [ ] **Step 3: Fetch and check for remote divergence before pushing**

This repo has a second developer (Aaron) pushing directly to `main`. Check before pushing:

```bash
git fetch origin
git status -sb
```

If `git status -sb` shows the branch is still `ahead` of `origin/main` with no `behind`, proceed to Step 4. If it shows `behind`, stop and surface what changed on `origin/main` (`git log origin/main -5 --oneline`) before merging — don't resolve a real conflict unilaterally.

- [ ] **Step 4: Push**

```bash
git push origin main
```

This pushes everything from this session: the `resend/resend-php` fix, the 2FA design spec, this plan doc, and all 9 implementation commits — one coherent set, ready for the next cPanel "Update from Remote" + "Deploy HEAD Commit".

- [ ] **Step 5: Note what still needs a manual server step**

Not part of this push, needs doing directly on the server per the redeploy runbook already shared: the platform admin (`ebrinetushabe@gmail.com`) will hit the mandatory TOTP setup screen on next login, same as any platform operator — no special-casing needed, this plan's Task 5-7 already covers that account along with any other `is_platform=true` row.
