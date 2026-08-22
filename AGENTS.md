# Agent notes (PearlEdu)

This is a production multi-tenant school MIS (Laravel 13, PostgreSQL, RLS). Treat it as such.

## Before editing

1. Inspect existing architecture. Do not add a parallel service, table, or permission system.
2. Tenant isolation is Eloquent scope **and** `FORCE ROW LEVEL SECURITY`. There is no service-role bypass.
3. School authorization is `config/permissions.php` + `role_assignments` (union) + route middleware + scope services. UI hiding is not the security boundary.
4. Roles and data scope: `docs/ROLES.md`. Decisions: `docs/DECISIONS.md`.

## Hard rules

- Never grant finance writes (`finance.manage`) to director, head teacher, deputy, DOS, or teachers.
- Never grant grade writes (`assessment.enter` / `assessment.manage`) to director, head teacher, deputy, bursar, parent, or student.
- Class teacher must not receive `assessment.enter`.
- Scope teacher attendance and learner profile access to assigned classes.
- Do not use client-editable metadata for authorization.

## Verify

```bash
php artisan test --filter=Role
php artisan test --filter=AssessmentScope
php artisan test --filter=AttendanceScope
php artisan db:verify-security   # needs non-superuser Postgres
```

## Done means

Behaviour exists, architecture stays coherent, authorization is tested (allowed + forbidden + out-of-scope), docs match the code.

## Cursor Cloud specific instructions

The VM already has PHP 8.4, Composer, PostgreSQL 16, and Node 22 provisioned. The startup update script only refreshes app deps (`composer install` + `npm install`); everything below is per-boot/runtime context, not install steps.

- **Start PostgreSQL every boot** — it does not auto-start: `sudo pg_ctlcluster 16 main start` (or `sudo service postgresql start`). Check with `pg_isready -h 127.0.0.1`.
- **DB role is intentionally non-superuser.** Role `voxsign_app` owns `voxsign` (dev) and `voxsign_test` (tests). `db:verify-security` and RLS fail closed on a superuser/`BYPASSRLS` role — never point the app at the `postgres` superuser. Ad-hoc `psql` as `voxsign_app` returns 0 tenant rows until you `SET app.current_school_id = '<id>';` (or `SET app.is_platform = 'on';`); that is RLS working, not missing data.
- **Env files are local + gitignored:** `.env` (dev) and `.env.testing` (tests). If they go missing, recreate from `.env.example`, then set `DB_PASSWORD=voxsign_secret` and a strong `PLATFORM_ADMIN_PASSWORD` (the platform seeder refuses a blank one).
- **Full test suite needs `.env.testing`.** `php artisan test` reads `.env.testing` (APP_ENV=testing). Feature tests request `http://voxsign.co.ug/`, but `.env`/`.env.example` set `TENANCY_LANDING_HOSTS=voxsign.test`; if that value leaks into the test run ~29 `LandingPageTest` cases 302 instead of 200. `.env.testing` pins `TENANCY_LANDING_HOSTS=voxsign.co.ug,www.voxsign.co.ug` to keep the suite green.
- **Run the app (dev):** `php artisan serve --host=0.0.0.0 --port=8000` plus `npm run dev` (Vite), or `composer dev` for the full serve+queue+logs+vite stack. Lint/test/analyse scripts live in `composer.json`; see README "Run it" for the migrate/seed sequence.
- **Host-based tenancy — you cannot use `localhost`.** Sign in at `http://pearledu.voxsign.test:8000/login`; a seeded tenant resolves at `http://pearledu1.voxsign.test:8000`. `/etc/hosts` maps `voxsign.test`, `pearledu.voxsign.test`, `pearledu1.voxsign.test` (etc.) to 127.0.0.1 — re-add those entries if they are missing after a fresh boot.
- **Demo data:** `php artisan school:seed-walkthrough --password='<pw>'` seeds St. Kizito Demonstration Primary (`pearledu1`) with accounts like `admin@stkizito.test`, `english@stkizito.test`, `bursar@stkizito.test` (see README table).
