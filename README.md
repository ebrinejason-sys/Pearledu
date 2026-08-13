# PearlEdu — multi-tenant school management (on VoxSign)

Laravel 13 · PostgreSQL · multi-tenant with database-enforced isolation. Built for Ugandan schools (pre-primary -> A-Level), hosted under `voxsign.co.ug`.

- Marketing: `voxsign.co.ug`
- School app + sign-in (all tenants): `pearledu.voxsign.co.ug/login` — after login, `/home` loads data for that user’s school (`schools.id` = tenant id)
- PearlEdu admin/staff: `pearledu.voxsign.co.ug/admin` — schools (edit/delete with DB cascade), PearlEdu staff (admin / EMIS entrants / support agents), support inbox, SMS, pricing
- Onboard creates the tenant + links users via `role_assignments.school_id`; RLS enforces isolation

## What's in this build

**Tenant isolation (two layers, fail-closed)** — Eloquent global scope AND PostgreSQL `FORCE ROW LEVEL SECURITY`. No service-role/bypass key exists; "platform sees all" is an RLS policy branch, not a privileged connection. `php artisan db:verify-security` refuses to run on a superuser/`BYPASSRLS` role.

**Themes (3)** — full semantic palettes in `config/themes.php`:
- `pearledu` — deep lagoon navy + amber (Plus Jakarta Sans)
- `moodle` — academic Boost blue + orange (Nunito Sans)
- `emis` — formal government navy + gold (IBM Plex Sans)

Resolution: user preference → school theme → default. App/auth layouts inject CSS variables, load the theme font, and drive buttons/sidebar/status/danger from tokens (not hardcoded hexes).

**Auth (invite-only)** — hierarchical invites by **email and/or phone**; accept link sets password then opens the dashboard. Login with **email or phone + password**. Multi-role staff (union permissions). Roles include deputy head teacher. Platform 2FA email OTP unchanged.

**School setup** — identity (logo/badge/motto on report cards), academic years + Term I–III template, subjects edit, fee structures with archive, transport student assignment, visual timetable grid, class-teacher ↔ class binding on invite.

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

Core MIS surfaces are implemented end-to-end (migrations, RLS, services, school routes/UI), including:

- **Parent/student portal** — results, fees (parent pay submissions pending staff verification; staff can confirm/reject), timetable, announcements
- **Fees** — structures, single + bulk class invoicing, staff payment recording + pending parent payment review
- **CBT** — publish + student take + auto-score; **LMS** — student submit + staff grade
- **Public admissions** — `/apply` on school tenant hosts (Turnstile + honeypot + tenant-scoped class validation)
- **Ops polish** — library return, hostel vacate/capacity, HR leave approve/reject, helpdesk close

**Still planned for deeper polish:** UGSMS provider driver, parent phone OTP (deferred), live video classes, and broader school-user 2FA. Run migrations + `db:verify-security` + `app:production-check` + tests on real Postgres before production trust.

**Production go-live:** see `docs/PRODUCTION_CHECKLIST.md` (server `.env`, cron, Resend, Twilio, SchoolPay credentials). Deploy runs `app:production-check` after migrate.

**Fees / SchoolPay:** Parent portal can initiate SchoolPay MoMo debit requests when a school enables SchoolPay under School identity. Channel payments (student 10-digit payment codes) arrive via webhook or `php artisan schoolpay:sync`. Manual cash/MoMo “submit for verification” remains available as a fallback. See `docs/SCHOOLPAY.md` for MD5 auth, Service Cops credentials, and go-live checklist.

**Production accounts:** platform operator (seeded), school staff (invite), parents (guardian invite/attach on student record), students (student login invite/attach on student record — sets `students.user_id`). Parent portal payments stay pending until bursar/finance confirms.
