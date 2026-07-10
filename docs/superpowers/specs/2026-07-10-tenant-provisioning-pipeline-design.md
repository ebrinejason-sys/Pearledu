# Tenant Provisioning Pipeline (Sprint 0)

## Problem

`SchoolProvisioner::onboard()` marks a school `status = 'active'` at creation time, before the invited admin has ever accepted the invite or logged in. There is no signal that a `pearledu{N}.voxsign.co.ug` subdomain is actually reachable end-to-end (DNS + tenant resolution + auth). Platform staff have no way to see, at a glance, whether a newly onboarded school is truly live.

Separately, while investigating this, we confirmed a real security gap: `ResolveTenant` pins the request to whatever school the subdomain maps to based on the Host header alone. It never checks that the authenticated user actually has a `RoleAssignment` at that school. Any logged-in user who navigates to another school's subdomain gets that school's `/home` rendered (permission-gated actions like `/sms` are still protected, but the base tenant surface is not).

This spec covers both: a provisioning-state signal for platform staff, and the membership check that makes "logged in" mean "logged in on the right school."

## Non-goals

- Custom domain verification flow (`school_domains.verified_at` UI) — separate spec.
- Automated (non-login-based) smoke testing / synthetic monitoring.
- Any new Learners/Attendance/Fees modules — separate spec (Sprint 1+).
- Resend-invitation UX changes — already works today, untouched.

## Data model

Add one nullable column: `schools.activated_at` (timestamp).

- Set once, in `LoginController::store`, immediately after `Auth::attempt` succeeds — but only when `TenantContext->school()` resolves to a school (i.e. the login happened on that school's actual subdomain, not the platform host) and `activated_at` is currently null.
- Impersonation (`ImpersonationService::start`) calls `Auth::login()` directly and never goes through `LoginController::store`, so imitating a user cannot spuriously flip `activated_at`.

No new state column beyond this. `School` gets a computed accessor instead of a stored enum, so there's nothing else to keep in sync:

```php
public function provisioningState(): string {
    if ($this->activated_at) return 'ready';
    $accepted = SchoolInvitation::where('school_id', $this->id)->whereNotNull('accepted_at')->exists();
    return $accepted ? 'invite_accepted' : 'pending_invite';
}
```

## Security fix: `RequireSchoolMembership` middleware

New middleware, same shape as the existing `RequirePermission`:

```php
class RequireSchoolMembership {
    public function __construct(private TenantContext $context) {}
    public function handle(Request $request, Closure $next): Response {
        $schoolId = $this->context->schoolId();
        abort_if($schoolId === null, 404);
        $u = $request->user();
        abort_unless(
            RoleAssignment::where('user_id', $u->id)->where('school_id', $schoolId)->where('is_active', true)->exists(),
            403
        );
        return $next($request);
    }
}
```

Registered in `routes/app.php` inside the existing `['web','auth']` group, before the `/home` route, so it covers every tenant-facing route without touching the `permission:` gates already in place on `/sms`.

Behavior on mismatch: **403 Forbidden** (matches the existing `RequirePermission` convention — no redirect-to-own-school logic).

## Platform UI

`platform/schools/show.blade.php`: replace the plain `Status: {{ $school->status }}` line with:

- A provisioning-state pill: "Pending invite" / "Invite accepted" / "Ready" (color-coded, same `.pill` class already used for levels/roles).
- When `activated_at` is set: "Verified live since {{ $school->activated_at->diffForHumans() }}".

`SchoolController::show` passes `provisioningState` (or the controller just calls `$school->provisioningState()` in the view — no new query needed, it's computed from already-loaded relations plus one lightweight `exists()` check). No new routes.

## Testing

- Feature test: onboard a school → accept the invitation → log in on `{slug}.{base_domain}` → assert `activated_at` is set and `provisioningState()` returns `ready`.
- Feature test: a user who is an active member of School A hits School B's subdomain while authenticated → asserts 403.
- Feature test: an unauthenticated or not-yet-accepted admin hitting the tenant subdomain still gets the existing "not activated yet" login error (unchanged behavior, regression guard).

## Files touched

- `database/migrations/xxxx_add_activated_at_to_schools_table.php` (new)
- `app/Models/School.php` — add `activated_at` to `$fillable`/casts, add `provisioningState()`
- `app/Http/Controllers/Auth/LoginController.php` — set `activated_at` on first tenant login
- `app/Http/Middleware/RequireSchoolMembership.php` (new)
- `bootstrap/app.php` or middleware alias registration (wherever `permission:` alias is registered) — register `school.member` alias if needed, or apply directly by class
- `routes/app.php` — add the middleware to the `['web','auth']` group
- `app/Http/Controllers/Platform/SchoolController.php` — pass provisioning state to the show view (likely no change needed if computed in the view)
- `resources/views/platform/schools/show.blade.php` — pill + verified-since line
- `tests/Feature/*` — new feature tests as above
