# Student Records Module — Design

**Date:** 2026-07-10  
**Status:** Approved for implementation  
**Branch:** `feature/student-records`

## Goal

Give school admins a tenant-scoped Student Records UI: list/search, create, view, edit, soft-delete, and guardian attach/invite/primary/detach — without student portal login yet.

## Decisions

| Area | Choice |
|------|--------|
| Permission | Reuse `learners.manage` (school_admin) |
| Student status | DB values: `active`, `inactive`, `transferred`, `graduated` |
| Student login | Leave `user_id` null; no portal account |
| Guardians | Hybrid: attach existing school member **or** invite new parent |
| Schema | No new tables; `students` + `guardianships` already RLS-enabled |
| Auth surface | Do not modify `User`, Auth controllers/services, or `routes/auth.php` |

## Architecture

- Routes in `routes/app.php` under `permission:learners.manage`
- `StudentController` for CRUD; guardian actions via same controller or nested methods
- `App\Services\Students\GuardianLinkService` for attach/invite/primary/detach (reuses `SchoolInvitation` + `InvitationMailer`)
- Blade views under `resources/views/app/students/` matching existing app layout
- Nav: new **Learners** section → Students

## Routes

| Method | Path | Name |
|--------|------|------|
| GET | `/students` | `app.students.index` |
| GET | `/students/create` | `app.students.create` |
| POST | `/students` | `app.students.store` |
| GET | `/students/{student}` | `app.students.show` |
| GET | `/students/{student}/edit` | `app.students.edit` |
| PUT | `/students/{student}` | `app.students.update` |
| DELETE | `/students/{student}` | `app.students.destroy` |
| POST | `/students/{student}/guardians` | `app.students.guardians.store` |
| PUT | `/students/{student}/guardians/{guardianship}/primary` | `app.students.guardians.primary` |
| DELETE | `/students/{student}/guardians/{guardianship}` | `app.students.guardians.destroy` |

## Fields

Create/edit: `full_name`, `emis_number` (unique per school), `class_id`, `status`, optional `lin`/`nin` (encrypted + audit-on-read already on model).

## Guardians

- **Attach:** email of existing school member → `Guardianship` (+ ensure `parent` role if missing)
- **Invite:** new email → User (`invited`) + `parent` RoleAssignment + `SchoolInvitation` + `Guardianship` + `InvitationMailer::send`
- **Primary:** one primary per student (transaction clears others)
- **Detach:** delete guardianship only

## Testing

Feature tests: CRUD, search, soft-delete, permission 403, guardian flows, cross-tenant IDOR. Keep `TenantIsolationTest` green. Run `migrate`, `db:verify-security`.

## Out of scope

Student portal login, EMIS export, class promotions, guardian national ID field, Auth/2FA changes.
