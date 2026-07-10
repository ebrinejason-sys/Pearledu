# Tenant Provisioning Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give platform staff a real signal that a newly onboarded school's subdomain is verifiably live, and close the gap where a logged-in user from one school could load another school's tenant pages.

**Architecture:** Add one nullable `schools.activated_at` timestamp, set once by `LoginController::store` the first time a login succeeds while `TenantContext` is resolved to that school (i.e. a real login on that school's actual subdomain). `School::provisioningState()` derives a 3-value state (`pending_invite` / `invite_accepted` / `ready`) from `activated_at` plus existing `SchoolInvitation.accepted_at` data — no new state to keep in sync. A new `RequireSchoolMembership` middleware, added to the `routes/app.php` route group, checks the authenticated user has an active `RoleAssignment` for the resolved school before any tenant page renders.

**Tech Stack:** Laravel 11, PostgreSQL (RLS enforced — see Global Constraints), PHPUnit feature tests.

## Global Constraints

- DB is Postgres with `FORCE ROW LEVEL SECURITY` on `schools` and all tenant child tables (see `database/migrations/2026_01_01_000016_enable_row_level_security.php`). Any direct Eloquent write in a test (e.g. `School::create`, `SchoolInvitation::create`) must run after `app(\App\Services\Tenancy\TenantContext::class)->forPlatform()`, or it will silently affect zero rows / fail the RLS `WITH CHECK`.
- `activated_at` must only ever be set from `LoginController::store`, and only when `TenantContext->school()` resolves — never from impersonation (`ImpersonationService::start` calls `Auth::login()` directly, bypassing this controller entirely, by design).
- Wrong-school access is a **403 Forbidden** (`abort_unless`), not a redirect. No "bounce to your own school" logic.
- Test env base domain is `voxsign.test` (`TENANCY_BASE_DOMAIN` in `phpunit.xml`), so tenant-subdomain requests in tests use `http://{slug}.voxsign.test/...` — matches the existing pattern in `tests/Feature/LoginPageTest.php` and `tests/Feature/PearlEduLandingPageTest.php`.
- Reuse `User::activeAssignments()` (already respects `is_active`, `starts_on`, `ends_on`) for the membership check — do not write a second, looser "is member" query.

---

### Task 1: `activated_at` column + `School::provisioningState()`

**Files:**
- Create: `database/migrations/2026_07_10_000001_add_activated_at_to_schools_table.php`
- Modify: `app/Models/School.php`
- Test: `tests/Feature/SchoolProvisioningStateTest.php`

**Interfaces:**
- Produces: `School::provisioningState(): string` returning one of `'pending_invite' | 'invite_accepted' | 'ready'`. `School::invitations(): HasMany` (to `SchoolInvitation`). `schools.activated_at` nullable timestamp column, cast to `datetime` on the model.
- Consumes: nothing from other tasks (this is the foundation task).

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolProvisioningStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->forPlatform();
    }

    public function test_state_is_pending_invite_with_no_accepted_invitation(): void
    {
        $school = School::create(['name' => 'State School', 'slug' => 'statesch', 'status' => 'active']);

        $this->assertSame('pending_invite', $school->provisioningState());
    }

    public function test_state_is_invite_accepted_once_invitation_accepted_but_no_login_yet(): void
    {
        $school = School::create(['name' => 'State School', 'slug' => 'statesch', 'status' => 'active']);
        SchoolInvitation::create([
            'school_id' => $school->id,
            'role_key' => 'school_admin',
            'token_hash' => 'x',
            'expires_at' => now()->addDays(7),
            'accepted_at' => now(),
        ]);

        $this->assertSame('invite_accepted', $school->provisioningState());
    }

    public function test_state_is_ready_once_activated_at_is_set(): void
    {
        $school = School::create(['name' => 'State School', 'slug' => 'statesch', 'status' => 'active']);
        $school->forceFill(['activated_at' => now()])->save();

        $this->assertSame('ready', $school->provisioningState());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SchoolProvisioningStateTest`
Expected: FAIL — `activated_at` column does not exist / `provisioningState` method does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('schools', function (Blueprint $t) {
            $t->timestamp('activated_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn('activated_at');
        });
    }
};
```

Save as `database/migrations/2026_07_10_000001_add_activated_at_to_schools_table.php`.

- [ ] **Step 4: Update `App\Models\School`**

Modify `app/Models/School.php` to add the cast, the `invitations()` relation, and `provisioningState()`:

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class School extends Model
{
    protected $fillable = ['name','slug','emis_number','district','theme','status','created_by'];
    protected $casts = ['activated_at' => 'datetime'];

    public function offerings(): HasMany { return $this->hasMany(SchoolOffering::class); }
    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function smsLedger(): HasMany { return $this->hasMany(SmsCreditEntry::class); }
    public function invitations(): HasMany { return $this->hasMany(SchoolInvitation::class); }

    public function subdomainUrl(): string {
        return 'https://'.$this->slug.'.'.config('tenancy.base_domain');
    }

    public function smsBalance(): int {
        return (int) ($this->smsLedger()->orderByDesc('id')->value('balance_after') ?? 0);
    }

    public function provisioningState(): string {
        if ($this->activated_at) return 'ready';
        return $this->invitations()->whereNotNull('accepted_at')->exists() ? 'invite_accepted' : 'pending_invite';
    }
}
```

- [ ] **Step 5: Run migrations and the test to verify it passes**

Run: `php artisan migrate` then `php artisan test --filter=SchoolProvisioningStateTest`
Expected: PASS (all 3 tests)

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_10_000001_add_activated_at_to_schools_table.php app/Models/School.php tests/Feature/SchoolProvisioningStateTest.php
git commit -m "Add schools.activated_at and School::provisioningState()"
```

---

### Task 2: `RequireSchoolMembership` middleware

**Files:**
- Create: `app/Http/Middleware/RequireSchoolMembership.php`
- Modify: `routes/app.php`
- Test: `tests/Feature/RequireSchoolMembershipTest.php`

**Interfaces:**
- Consumes: `TenantContext->schoolId(): ?int` (existing), `User->activeAssignments()` query builder (existing, from `app/Models/User.php`).
- Produces: nothing consumed by later tasks — this task is self-contained.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RequireSchoolMembershipTest extends TestCase
{
    use RefreshDatabase;

    private School $alpha;
    private School $beta;
    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $ctx = app(TenantContext::class);
        $ctx->forPlatform();

        $this->alpha = School::create(['name' => 'Alpha', 'slug' => 'alpha', 'status' => 'active']);
        $this->beta = School::create(['name' => 'Beta', 'slug' => 'beta', 'status' => 'active']);

        $this->alice = User::create([
            'full_name' => 'Alice Admin', 'email' => 'alice@alpha.test',
            'status' => 'active', 'password' => Hash::make('password12345'),
        ]);
        RoleAssignment::create([
            'user_id' => $this->alice->id,
            'role_id' => Role::where('key', 'school_admin')->value('id'),
            'school_id' => $this->alpha->id,
            'is_active' => true,
        ]);
    }

    public function test_member_can_access_their_own_schools_home(): void
    {
        $response = $this->actingAs($this->alice)->get('http://alpha.voxsign.test/home');

        $response->assertOk();
    }

    public function test_non_member_gets_403_on_another_schools_subdomain(): void
    {
        $response = $this->actingAs($this->alice)->get('http://beta.voxsign.test/home');

        $response->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RequireSchoolMembershipTest`
Expected: FAIL — `test_non_member_gets_403_on_another_schools_subdomain` fails because `/home` currently renders 200 for any authenticated user regardless of school.

- [ ] **Step 3: Write the middleware**

```php
<?php
namespace App\Http\Middleware;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route guard: the authenticated user must have an active RoleAssignment at the resolved tenant. */
class RequireSchoolMembership {
    public function __construct(private TenantContext $context) {}
    public function handle(Request $request, Closure $next): Response {
        $schoolId = $this->context->schoolId();
        abort_if($schoolId === null, 404);
        abort_unless(
            $request->user()->activeAssignments()->where('school_id', $schoolId)->exists(),
            403
        );
        return $next($request);
    }
}
```

Save as `app/Http/Middleware/RequireSchoolMembership.php`.

- [ ] **Step 4: Wire the middleware into `routes/app.php`**

```php
<?php
use App\Http\Controllers\AppHomeController;
use App\Http\Controllers\SmsController;
use App\Http\Middleware\RequireSchoolMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', RequireSchoolMembership::class])->group(function () {
    Route::get('/home', [AppHomeController::class, 'index'])->name('app.home');

    Route::middleware('permission:sms.send')->group(function () {
        Route::get('/sms', [SmsController::class, 'index'])->name('app.sms');
        Route::post('/sms', [SmsController::class, 'send'])->name('app.sms.send');
    });
});
```

Replace the full contents of `routes/app.php` with the above.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=RequireSchoolMembershipTest`
Expected: PASS (both tests)

- [ ] **Step 6: Run the existing sidebar/impersonation/auth suites to check for regressions**

Run: `php artisan test --filter=SidebarNavigationTest` and `php artisan test --filter=ImpersonationTest` and `php artisan test --filter=AuthEmailTest`
Expected: All PASS unchanged (demo users already have `RoleAssignment` rows for the demo school via `DemoTenantSeeder`; impersonation logs in as the real target user who genuinely belongs to that school).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Middleware/RequireSchoolMembership.php routes/app.php tests/Feature/RequireSchoolMembershipTest.php
git commit -m "Add RequireSchoolMembership middleware to close cross-tenant access gap"
```

---

### Task 3: Set `activated_at` on first real tenant login

**Files:**
- Modify: `app/Http/Controllers/Auth/LoginController.php`
- Test: `tests/Feature/TenantActivationTest.php`

**Interfaces:**
- Consumes: `School::provisioningState()` and `activated_at` cast (Task 1). `TenantContext->school(): ?School` (existing).
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;

use App\Models\SchoolInvitation;
use App\Models\User;
use App\Services\Provisioning\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_first_real_login_on_tenant_subdomain_marks_school_ready(): void
    {
        $result = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Ready Test School', 'district' => 'Kampala'],
            levels: ['primary'],
            admin: ['full_name' => 'Rita Ready', 'email' => 'rita@readytest.test'],
            operatorId: null,
        );
        $school = $result['school'];
        $this->assertSame('pending_invite', $school->provisioningState());

        $invitation = SchoolInvitation::where('school_id', $school->id)->latest('id')->first();

        $this->post("/invitations/{$invitation->id}/accept", [
            'token' => $result['invite_token'],
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ])->assertRedirect('/login');

        $school->refresh();
        $this->assertSame('invite_accepted', $school->provisioningState());
        $this->assertNull($school->activated_at);

        $this->post("http://{$school->slug}.voxsign.test/login", [
            'email' => 'rita@readytest.test',
            'password' => 'password12345',
        ])->assertRedirect(route('app.home'));

        $school->refresh();
        $this->assertNotNull($school->activated_at);
        $this->assertSame('ready', $school->provisioningState());
    }

    public function test_uninvited_admin_login_attempt_does_not_activate_school(): void
    {
        $result = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Slow Start School'],
            levels: ['primary'],
            admin: ['full_name' => 'Ivy Idle', 'email' => 'ivy@slowstart.test'],
            operatorId: null,
        );
        $school = $result['school'];

        $response = $this->post("http://{$school->slug}.voxsign.test/login", [
            'email' => 'ivy@slowstart.test',
            'password' => 'whatever-not-set-yet',
        ]);

        $response->assertSessionHasErrors('email');
        $school->refresh();
        $this->assertNull($school->activated_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TenantActivationTest`
Expected: FAIL on `test_first_real_login_on_tenant_subdomain_marks_school_ready` — `activated_at` stays null after login (second `assertNotNull` fails).

- [ ] **Step 3: Update `LoginController::store`**

Modify `app/Http/Controllers/Auth/LoginController.php`:

```php
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
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=TenantActivationTest`
Expected: PASS (both tests)

- [ ] **Step 5: Run the full auth suite to check for regressions**

Run: `php artisan test --filter=AuthEmailTest` and `php artisan test --filter=LoginPageTest`
Expected: All PASS unchanged (platform-host logins have no resolved school, so `$context->school()` is null and the new block is a no-op).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/LoginController.php tests/Feature/TenantActivationTest.php
git commit -m "Mark school activated_at on first real login on its tenant subdomain"
```

---

### Task 4: Platform school-show page — provisioning pill

**Files:**
- Modify: `resources/views/platform/schools/show.blade.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/SchoolShowProvisioningTest.php`

**Interfaces:**
- Consumes: `School::provisioningState()` (Task 1), `School->activated_at` (Task 1).
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolShowProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pending_school_shows_pending_invite_pill(): void
    {
        // DemoTenantSeeder force-activates the demo admin's User row directly but never
        // sets SchoolInvitation.accepted_at, so the seeded school's state is 'pending_invite'.
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();

        $response = $this->actingAs($operator)->get(route('platform.schools.show', $school));

        $response->assertOk();
        $response->assertSee('Pending invite');
    }

    public function test_ready_school_shows_ready_pill_and_verified_timestamp(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $school->forceFill(['activated_at' => now()])->save();

        $response = $this->actingAs($operator)->get(route('platform.schools.show', $school));

        $response->assertOk();
        $response->assertSee('Ready');
        $response->assertSee('Verified live');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SchoolShowProvisioningTest`
Expected: FAIL — neither "Ready" nor "Verified live" text exists on the page yet.

- [ ] **Step 3: Add the `.pill--muted` style**

In `resources/views/layouts/app.blade.php`, find the line:

```
.pill{display:inline-block;background:var(--accent-soft);color:var(--brand);border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600}
```

Add immediately after it:

```
.pill--muted{background:var(--bg);color:var(--muted)}
```

- [ ] **Step 4: Update the school-show view**

In `resources/views/platform/schools/show.blade.php`, find:

```blade
      <p><strong>Status:</strong> {{ $school->status }}</p>
```

Replace it with:

```blade
      <p><strong>Status:</strong> {{ $school->status }}</p>
      @php($provisioning = $school->provisioningState())
      <p>
        <strong>Provisioning:</strong>
        <span class="pill @if($provisioning !== 'ready') pill--muted @endif">
          {{ ['pending_invite' => 'Pending invite', 'invite_accepted' => 'Invite accepted', 'ready' => 'Ready'][$provisioning] }}
        </span>
        @if($school->activated_at)
          <span style="color:var(--muted);font-size:13px"> — Verified live since {{ $school->activated_at->diffForHumans() }}</span>
        @endif
      </p>
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=SchoolShowProvisioningTest`
Expected: PASS (both tests, after resolving the seeded-state check noted in Step 1)

- [ ] **Step 6: Commit**

```bash
git add resources/views/platform/schools/show.blade.php resources/views/layouts/app.blade.php tests/Feature/SchoolShowProvisioningTest.php
git commit -m "Show provisioning state pill on platform school detail page"
```

---

### Task 5: Full regression pass

**Files:** none (verification only)

- [ ] **Step 1: Run the complete test suite**

Run: `php artisan test`
Expected: All tests PASS, including the 4 new test files and all pre-existing suites (`SidebarNavigationTest`, `ImpersonationTest`, `AuthEmailTest`, `LoginPageTest`, `TenantIsolationTest`, etc.).

- [ ] **Step 2: If anything fails, fix forward**

Do not skip or delete a failing pre-existing test — if `RequireSchoolMembership` broke something, the fix belongs in the middleware or the affected controller/route, not in loosening the test.
