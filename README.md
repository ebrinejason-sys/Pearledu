# PearlEdu — multi-tenant school management (on VoxSign)

Laravel 13 · PostgreSQL · multi-tenant with database-enforced isolation. Built for Ugandan schools (pre-primary -> A-Level), hosted under `voxsign.co.ug`.

- Marketing: `voxsign.co.ug`
- Platform app / sign-in: `pearledu.voxsign.co.ug`
- Tenant schools (auto): `pearledu1.voxsign.co.ug`, `pearledu2...` (wildcard DNS)

## What's in this build

**Tenant isolation (two layers, fail-closed)** — Eloquent global scope AND PostgreSQL `FORCE ROW LEVEL SECURITY`. No service-role/bypass key exists; "platform sees all" is an RLS policy branch, not a privileged connection. `php artisan db:verify-security` refuses to run on a superuser/`BYPASSRLS` role.

**Themes (3)** — `pearledu` (green/gold), `moodle` (Boost blue/orange), `emis` (Ugandan-government blue/gold). Resolution: user preference -> school theme -> default. Palettes live in `config/themes.php`; the layout injects them as CSS variables.

**Auth** — invitation-based activation (hashed, single-use, expiring tokens), login with per-email+IP rate limiting, session regeneration, last-login tracking, 2FA columns scaffolded, `permission:` route middleware (union of a user's active roles).

**Onboarding** — `SchoolProvisioner` onboards atomically, auto-assigns `pearledu{N}` subdomain, seeds class scaffold, creates the contact person as School Admin, and issues their activation invite.

**SMS reselling** — platform-controlled provider + per-segment credit price; append-only credit ledger with locked balance writes; platform tops schools up; schools spend credit to message parents/staff. Charge-then-dispatch with automatic refund on failure. `FakeGateway` until a real provider is wired.

**Account deletion** — `AccountDeletionService` hard-deletes the identity + personal links; de-identifies any learner record the school must keep; retains de-identified audit. Self-service at Account -> Delete account.

**Email** — Resend via SMTP (`.env.example`).

See `docs/DATA_PROTECTION.md` for the full privacy/security architecture.

## Run it (local or server)

```bash
composer install
cp .env.example .env && php artisan key:generate
# create a NON-superuser Postgres role + db, fill DB_* in .env
php artisan migrate --force
php artisan db:seed --force
php artisan db:verify-security      # must print OK
php artisan serve
```

> `composer install` must run where Packagist is reachable (your machine or the server) — it was not run in the build sandbox.

## Local demo seed (optional)

`php artisan db:seed` can create a local demo school for exploration. Demo login emails/passwords are **not published here** — check your local seeder output or ask a teammate with seed access. Never use demo credentials in production.

## Verify the security guarantees

```bash
php artisan db:verify-security
php artisan test --filter=TenantIsolationTest
```

## Honest status

Real, working implementations: themes, auth/invitations, onboarding, SMS-credit reselling, account deletion, platform dashboard, RLS + guard. Lint-clean (98/98) but not yet executed — no Postgres/Composer in the build sandbox, so run migrations + tests on a real Postgres before trusting. Still planned: MTN/Airtel MoMo, full 2FA enforcement, self-service data export, fees/assessment/attendance modules (covered by the multi-agent kit).
