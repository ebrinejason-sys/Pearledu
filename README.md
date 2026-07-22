# PearlEdu — multi-tenant school management (on VoxSign)

Laravel 13 · PostgreSQL · multi-tenant with database-enforced isolation. Built for Ugandan schools (pre-primary -> A-Level), hosted under `voxsign.co.ug`.

- Marketing: `voxsign.co.ug`
- Platform app / sign-in: `pearledu.voxsign.co.ug`
- Tenant schools (auto): `pearledu1.voxsign.co.ug`, `pearledu2...` (wildcard DNS)

## What's in this build

**Tenant isolation (two layers, fail-closed)** — Eloquent global scope AND PostgreSQL `FORCE ROW LEVEL SECURITY`. No service-role/bypass key exists; "platform sees all" is an RLS policy branch, not a privileged connection. `php artisan db:verify-security` refuses to run on a superuser/`BYPASSRLS` role.

**Themes (3)** — full semantic palettes in `config/themes.php`:
- `pearledu` — deep lagoon navy + amber (Plus Jakarta Sans)
- `moodle` — academic Boost blue + orange (Nunito Sans)
- `emis` — formal government navy + gold (IBM Plex Sans)

Resolution: user preference → school theme → default. App/auth layouts inject CSS variables, load the theme font, and drive buttons/sidebar/status/danger from tokens (not hardcoded hexes).

**Auth (invite-only)** — hierarchical invites by **email and/or phone**; accept link sets password then opens the dashboard. Login with **email or phone + password**. Multi-role staff (union permissions). Roles include deputy head teacher. Platform 2FA email OTP unchanged.

**School MIS modules** — academic years/terms, subjects, teaching assignments, enrollments, attendance (+ optional SMS), assessment/marks/broadsheet/report cards, promotions, non-colliding timetable, fees (cash + MoMo methods), announcements, admissions, LMS, CBT, library, inventory, transport, hostel, HR leave, clinic, helpdesk, EMIS CSV export.

**Onboarding** — `SchoolProvisioner` onboards atomically, auto-assigns `pearledu{N}` subdomain, seeds class scaffold, creates the contact person as School Admin, and issues their activation invite.

**SMS reselling** — platform-controlled provider + per-segment credit price; append-only credit ledger; drivers: `fake`, `log`, `twilio`.

**Account deletion** — `AccountDeletionService` hard-deletes identity + personal links; de-identifies learner records schools must keep.

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

Set `SEED_DEMO_TENANT=true` and run `php artisan db:seed` to create a sample school + role assignments for exploration. **No shared demo passwords are published** — accounts stay invitation-status until you activate them via the invite flow. Never invent or circulate demo credentials for production.

## Verify the security guarantees

```bash
php artisan db:verify-security
php artisan test --filter=TenantIsolationTest
```

## Honest status

Core MIS surfaces above are implemented end-to-end (migrations, RLS, services, school routes/UI). Still planned for deeper polish: live MoMo gateway callbacks, full CBT exam-taking UI for students, live video classes, and broader school-user 2FA. Run migrations + `db:verify-security` + tests on real Postgres before production trust.
