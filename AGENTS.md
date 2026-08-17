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
