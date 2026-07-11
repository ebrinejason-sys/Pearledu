# Student Records Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship tenant-scoped Student Records CRUD with guardian attach/invite/primary/detach for school admins.

**Architecture:** Reuse existing `Student`/`Guardianship` models + RLS. Add `StudentController`, `GuardianLinkService`, Blade views, `learners.manage` routes, and feature tests. No new tables. Do not touch Auth/User files.

**Tech Stack:** Laravel 13, PostgreSQL RLS, Blade, PHPUnit feature tests

## Global Constraints

- Work on `feature/student-records` only (isolated worktree)
- Do not modify `app/Http/Controllers/Auth/`, `app/Services/Auth/`, `app/Models/User.php`, `routes/auth.php`, `app/Models/AuthLoginToken.php`
- Follow `BelongsToSchool` for tenant scoping; never hand-roll `school_id` filters
- Permission key: `learners.manage`
- Student status values: `active|inactive|transferred|graduated`
- Leave `user_id` null on students

---

### Task 1: Model relationships + factory

**Files:**
- Modify: `app/Models/Student.php`
- Modify: `app/Models/SchoolClass.php`
- Create: `database/factories/StudentFactory.php`

- [ ] Add `user()`, `schoolClass()` on Student; `students()` on SchoolClass
- [ ] Add StudentFactory with sensible defaults
- [ ] Commit

### Task 2: GuardianLinkService

**Files:**
- Create: `app/Services/Students/GuardianLinkService.php`

- [ ] Implement `attachExisting`, `inviteNew`, `makePrimary`, `detach`
- [ ] Reuse `InvitationMailer` + `SchoolInvitation` for invites; role_key `parent`
- [ ] Commit

### Task 3: Controller + routes + nav

**Files:**
- Create: `app/Http/Controllers/StudentController.php`
- Modify: `routes/app.php`
- Modify: `app/Services/Navigation/NavigationBuilder.php`
- Modify: `resources/views/layouts/partials/sidebar.blade.php`
- Modify: `resources/views/app/home.blade.php`

- [ ] CRUD + guardian endpoints gated by `permission:learners.manage`
- [ ] Index: paginate 20, search `full_name` / `emis_number`
- [ ] Nav Learners → Students; home quick action
- [ ] Commit

### Task 4: Blade views

**Files:**
- Create: `resources/views/app/students/index.blade.php`
- Create: `resources/views/app/students/create.blade.php`
- Create: `resources/views/app/students/show.blade.php`
- Create: `resources/views/app/students/edit.blade.php`
- Create: `resources/views/app/students/_form.blade.php`

- [ ] Match SMS/app layout patterns (`.card`, `.page-header`, forms)
- [ ] Show page includes guardian list + attach/invite forms
- [ ] Commit

### Task 5: Feature tests

**Files:**
- Create: `tests/Feature/StudentRecordsTest.php`
- Modify: `tests/Feature/SidebarNavigationTest.php` (admin sees Learners/Students)

- [ ] CRUD, search, soft-delete, 403 without permission, guardian flows, IDOR
- [ ] Run: `php artisan test --filter=StudentRecordsTest`
- [ ] Run: `php artisan test --filter=TenantIsolationTest`
- [ ] Run: `php artisan test --filter=SidebarNavigationTest`
- [ ] Run: `php artisan migrate` + `php artisan db:verify-security`
- [ ] Commit

## File map

| File | Responsibility |
|------|----------------|
| `StudentController` | HTTP CRUD + guardian actions |
| `GuardianLinkService` | Guardianship + invite orchestration |
| `routes/app.php` | Route registration |
| `NavigationBuilder` | Learners nav section |
| `app/students/*.blade.php` | UI |
| `StudentRecordsTest` | Feature coverage |
