# Decision log

## 2026-08-17 — School role model vs generic RBAC tables

**Problem:** A role-definition brief proposed `roles` / `permissions` / `role_permissions` / `user_roles` SQL tables and eight school roles including Dean/DOS.

**Decision:** Keep the existing catalog (`roles.key` + `config/permissions.php` + `role_assignments`). Add `director_of_studies`. Realign grants and add class-level scope services. Do not create a parallel permission table.

**Alternatives considered:** (1) New SQL RBAC matching the brief. (2) Rename `subject_teacher` → `teacher`.

**Reason:** The app already unions multi-role assignments, enforces permissions in middleware, and isolates tenants with RLS. A second RBAC schema would drift. `subject_teacher` is referenced in invites, seeds, tests, and teaching assignment filters.

**Consequences:** Agents must treat `config/permissions.php` as the grant matrix. DOS is the academic lead; Head Teacher no longer writes grades or fees.

**Rollback/revisit:** Revisit a SQL permission table only if schools need per-tenant custom grants. Revisit renaming `subject_teacher` with a data migration and invite-token compatibility plan.

## 2026-08-17 — Class teacher does not inherit mark entry

**Problem:** The brief said class teacher inherits all teacher permissions, including grade CRUD.

**Decision:** Class teacher keeps `assessment.view` only. Mark entry requires `assessment.enter` plus a current-year teaching assignment (typically a second `subject_teacher` role).

**Reason:** Academic integrity — homeroom oversight must not silently overwrite subject-teacher marks. This was already the behaviour (`AssessmentScopeTest`).

**Consequences:** A homeroom teacher who also teaches a subject must be invited with both roles.

**Rollback/revisit:** If a school requires homeroom-only mark entry, grant `subject_teacher` + teaching assignment rather than weakening `class_teacher`.

## 2026-08-17 — Head Teacher / Director separation of duties

**Problem:** Head Teacher had `assessment.enter` / `assessment.manage`. Director had `finance.manage` and attendance writes.

**Decision:** Head Teacher and Deputy: finance and assessment **view** only; they retain staff, learners, promotions (head only), timetable, and school-wide attendance. Director: finance/assessment/attendance **view** only; retains staff appointments.

**Reason:** Least privilege and SoD from the role brief (academic integrity + finance not writable by governance roles).

**Consequences:** Day-to-day fee posting is bursar/school admin. Day-to-day grading is subject teachers and DOS.

**Rollback/revisit:** If a small school has no DOS, school admin remains the academic break-glass, not the Head Teacher.

## 2026-08-17 — Platform admin sends password-reset links, not temporary passwords

**Problem:** The PearlEdu staff "Reset password" action set an unknown random password and emailed `/forgot-password` (no token). Staff were locked out and still had to request a reset themselves.

**Decision:** Platform admins send `ResetPasswordMail` with a broker token. The existing password stays valid until the link is used. Support agents and other operator roles cannot send staff resets (`platform.staff.manage` + `canManage`).

**Alternatives considered:** (1) Email a temporary password. (2) Invalidate the current password immediately.

**Reason:** Least surprise and recoverability if mail delivery fails. Matches the public forgot-password flow.

**Consequences:** Compromised accounts can still sign in with the old password until the staff member completes the reset (admins can Force logout separately).

**Rollback/revisit:** If recovery must lock the account immediately, invalidate sessions and rotate the password in the same action.

## 2026-08-17 — Role workspaces, scoped LMS/CBT, marksheet workflow

**Problem:** School users shared one generic dashboard. LMS/CBT were not assignment-scoped. Class-teacher homeroom could not be edited safely. Parents/students had no attendance. DOS could invite teachers in policy but could not open Staff. Assessment had period statuses but no teacher submit → DOS verify step.

**Decision:** Keep `PermissionResolver` + `config/permissions.php`. Add `LmsScope`, `CbtScope`, `MarksheetWorkflow`, `RoleWorkspaceService`. Split invite route permissions (`staff.invite.teacher`, `users.invite.parent`, `enrollment.manage`). User-facing Teacher label; internal key remains `subject_teacher`.

**Reason:** Least privilege and the existing multi-role architecture. Navigation regrouping is permission-driven, not a second RBAC system.

**Consequences:** Class teachers invite parents from the learner profile. DOS invites teachers from Staff but cannot revoke access or manage school identity. Submitted marksheets are locked for teachers.

**Rollback/revisit:** Fee granular keys still OR with `finance.manage` so bursar/school admin are not locked out. A later pass can drop the broad key once every fee route is granular-only.

## 2026-08-22 — Idle sessions and invite-only staff mutations

**Problem:** `SESSION_LIFETIME` only expired the cookie. Remember-me could silently sign someone back in on a shared staff computer. DOS (`staff.invite.teacher`) could POST `/staff/{user}/roles` and strip a Head Teacher or bursar. Class teachers had school-wide `sms.send`. Anyone with `assessment.view` could open the assessment-period admin screen.

**Decision:** Persist `users.last_seen_at`. `EnforceIdleSession` signs out after 30 idle minutes and rotates remember tokens. Heartbeat + in-app warning extend the window only after real activity. `updateRoles` / `revoke` require `staff.manage`. `StaffRoleService` refuses to remove a responsibility the actor cannot invite. Teachers lose school-wide SMS. Assessment period admin is `assessment.manage` only.

**Reason:** Least privilege and workstation lock for a school MIS. Invitation authority stays; mutation of existing staff is an HR/ops action.

**Consequences:** DOS still invites teachers from Staff. Head Teacher still cannot demote the bursar via the role checkboxes. Idle logout is user-level (activity on any device refreshes `last_seen_at`).

**Rollback/revisit:** If a school needs class-level SMS, add a scoped sender rather than restoring school-wide `sms.send` on teachers.

## 2026-08-22 — Walkthrough primary school is an opt-in artisan command

**Problem:** First-school testing needed Baby–P7 filled (~100 learners) and named staff logins. `DemoTenantSeeder` stays passwordless for CI.

**Decision:** Add `php artisan school:seed-walkthrough` backed by `WalkthroughSchoolService`. It calls `SchoolProvisioner`, `role_assignments`, enrollments, and `config/permissions.php`. Production refuses the command unless `--force` is passed (password on the CLI only). The same seed is also available from **Schools → Demonstration school** in `/admin` (`platform.schools.create` + recent platform password). The form field is `walkthrough_password` so password-confirm resume does not strip it. `SEED_TEST_SCHOOL_PASSWORD` must stay unset on the live `.env`.

**Reason:** Operators can click through each role on a laptop or on the live host without inventing a parallel RBAC, bloating PHPUnit’s demo tenant, or needing SSH just to set the shared test password.

**Consequences:** Kindergarten remains an empty scaffold class. Homeroom teachers do not receive `assessment.enter`; English/Maths subject teachers do, scoped by teaching assignments. Purge EMIS `1999001` from the platform console when online testing is finished.

**Rollback/revisit:** Delete the walkthrough school from the platform console.

## 2026-08-22 — Offline-first attendance and marks

**Problem:** Staff on school grounds often lose mobile data mid-register. A full offline MIS would duplicate authorization. Idle logout must still protect shared computers.

**Decision:** Service worker caches the last-loaded school pages. Attendance save and mark draft save (`data-offline-queue`) write to IndexedDB when the request cannot complete, then POST the same existing routes when online. Queue is keyed by user id + school id. Fees, role edits, and marksheet verify/submit stay online-only. 401/419 keeps the queue and sends the user to login.

**Reason:** Replay through `AttendanceController` / `AssessmentController` so `AttendanceScope` and `AssessmentScope` remain the security boundary. Client storage is not authorization.

**Consequences:** A class roster must be opened once while online before it can be reused offline. Idle 30-minute logout is unchanged.

**Rollback/revisit:** Add more form kinds only when they upsert through an existing scoped service.

## 2026-08-22 — School ops: workspace integrations, secretary clock, identity, messages, payroll view

**Problem:** Platform admins already inside a school workspace still left it to edit EMIS/SchoolPay. Staff had no printable ID or clock. Profiles lacked photo/NIN/gender. Bursar/director could not print a class defaulter list or notify that class teacher without SMS. Staff had no in-school messages. Director needed class/staff/salary visibility without finance or grade writes.

**Decision:** Keep `config/permissions.php` + `role_assignments`. Add school role `secretary` (`staff.id.print`, `staff.attendance.mark`, `staff.messages`; no `finance.manage` / `assessment.enter`). Platform workspace settings are `platform.schools.update` + `platform.recent_auth` on the entered school. Impersonation stays the existing platform staff imitate flow. Staff clock and messages are new school-scoped tables with FORCE RLS. `hr.payroll.view` is granted to director/head/deputy; `hr.payroll.manage` stays bursar + school admin. NIN is required for staff and parents, optional for learners. Gender stats use `GenderStatsService`. Class defaulter notify uses staff messages, not `sms.send` on class teachers. Class overview is `reports.view` so teachers cannot see every class.

**Reason:** Least privilege and the existing union architecture. A second RBAC or mixing staff punches into learner `attendance_records` would blur pastoral attendance with HR clock.

**Consequences:** Secretary is invitable by school admin, director, and head. Director can open payroll and clock history but cannot POST salary or clock punches. Learner NIN stays optional.

**Rollback/revisit:** A full payroll engine (leave, PAYE) remains deferred. Expand class-level SMS later without restoring school-wide `sms.send` on teachers.

## 2026-08-22 — Fee types, exam sets, scoped homeroom edits, teaching load on invite

**Problem:** Bursars needed day vs boarding structures, extra fees (van) for a named group, and printable/emailable receipts without mixing every ledger onto one page. Class teachers needed exam-set oversight and the ability to lock late mark uploads, plus homeroom bio/photo/restream, without inheriting grade or school-wide learner writes. Teacher invites that omitted subject and class made timetable collisions invisible until generate time.

**Decision:** Keep `config/permissions.php` + `role_assignments`. Fee structures gain `kind`, `residency`, and `applies_to` (class or learner group via `fee_structure_students` with FORCE RLS). Invoices, cleared, and overdue are their own pages; record payment is a modal. Receipts print and email through `FeeReceiptService`. Assessment periods gain BOT/MOT/EOT/custom `kind` and `entry_deadline`. Class teacher receives `assessment.lock` (revoke after deadline) and `learners.profile.update` (homeroom bio/photo/sibling-stream restream only) — still no `assessment.enter` or `learners.manage`. Inviting `subject_teacher` requires `teaching_assignments` (subject + classes in the current year). Subject-teacher assessment lives under **My classes**. Director home uses `GenderStatsService::emisOverview` for EMIS-style census cards. Theme accent is teal on navy to match that census UI.

**Reason:** Least privilege and the existing union architecture. Homeroom lock is pastoral oversight, not mark entry. Teaching load at invite is the same `teaching_assignments` row the timetable already uses.

**Consequences:** Existing Teacher invites without a subject/class fail validation. Class teachers can restream only between streams of the same class name and level. Subject teachers can still upload after the deadline until the class teacher revokes.

**Rollback/revisit:** If a school needs homeroom mark entry, grant `subject_teacher` + teaching assignment rather than `assessment.enter` on `class_teacher`.

## 2026-08-22 — Teaching load classification for timetable (many rows per staff)

**Problem:** Teacher invites captured a crude subject/class pair, existing staff could be given Teacher with no load, and the teaching page was a single-row form. Timetable generation then collided because `periods_per_week` and multiple subject→class rows were not classified at role creation.

**Decision:** Keep the existing `teaching_assignments` table. `TeachingLoadService` expands one staff member into many `(subject, class)` rows with `periods_per_week` (1–20, default 3), optional term, and optional effective dates. Invite, existing-staff Teacher grants, and the teaching page all use the same builder. Occupancy is a class × subject matrix; two teachers on the same cell is a warning, not a new uniqueness rule. Class teacher still does not receive `assessment.enter`.

**Reason:** The generator already sorts by `periods_per_week` and forbids teacher/class double-booking. Classification belongs on the role, not a parallel load table.

**Consequences:** Granting Teacher without current-year load fails until subject + class (+ periods) are set. One person may teach English to P5 East and Biology to S3 West in the same save.

**Rollback/revisit:** Tighten shared-cell collisions to a hard reject only if schools ask the generator to pick a single owner.

## 2026-08-22 — Class+residency billing, one-window profiles, secretary files, teaching vs non-teaching staff

**Problem:** Learners were not billed automatically from saved day/boarding class structures. Extras such as a van fee lived on the fees page instead of the named learner. Learner create/show did not capture biodata and guardian photos together. Staff invite did not distinguish teaching vs non-teaching, salary, documents, or clock ID at create time. Secretary could print IDs but could not look up learners or keep staff files.

**Decision:** Keep `config/permissions.php` + `role_assignments`. Enrolling a learner invoices matching class+residency structures (`FeeInvoiceService::assignDefaultStructures`). Bursar applies a learner-specific extra on the profile (`applyCustomFee`); the statement balance is the cumulative amount due. Learner biodata (DOB, religion, address, medical notes) and first guardian + photos are captured on create; more guardians stay on the same profile window. Staff invite requires `staff_kind` teaching|non_teaching (UX/validation only — no new column). Teaching staff must include Teacher and/or Class Teacher and still send `teaching_assignments` (with optional periods). Non-teaching capture biodata, NIN, salary amount (`staff_salaries`), and other duties. Academic documents use `staff_documents` with FORCE RLS. Clock IDs issue on invite via `StaffBadgeService`. Secretary gains `learners.view` and `staff.profile.update` (and is `Role::SCHOOL_WIDE` for reception lookup) — still no `finance.manage`, `assessment.enter`, `staff.manage`, or `hr.payroll.manage`.

**Reason:** Charge by class and residence without a second billing engine. Front office keeps files and IDs; bursar keeps money; teachers keep grades.

**Consequences:** Changing class or residency as `learners.manage` adds matching structures; old invoices are not voided. Secretary can open every learner profile. EMIS teaching vs non-teaching still uses role heuristics, not `staff_kind`. Class tuition must be day or boarding; other class fees may apply to both residences and are billed at admit/enroll with the matching residence types. From the learner profile the bursar attaches any saved type that matches class/residence (or a named extra) and invoices it. Bursar may delete a type that has no confirmed/pending payments. Staff detail edits follow the invite hierarchy except secretary files.

**Rollback/revisit:** A PAYE/allowance engine remains deferred. Do not add a `staff_kind` column unless a person must be classified independently of their school roles.

## 2026-08-22 — EMIS-direction chrome without cloning MoES modules

**Problem:** School staff asked for the MoES EMIS information architecture: teal sidebar head, nested Learners / Human Resource / Finance menus, an academic-year chip, and a learner profile with a left rail (photo, age, sex, section links) plus a definition-list detail pane — including attaching a fee type from that profile.

**Decision:** Keep PearlEdu identity, `config/permissions.php`, and existing routes. Restyle the school shell (full-height navy sidebar with teal brand head, collapsible groups, year chip). Group current items under **Manage school data** as Learners (View Learners, Admissions, Enrollments, Promotions), Human Resource, and Finance. Learner show uses `?tab=` for Basic Info / Parents/Guardian / Fees / Login. Do **not** add MoES-only modules (Infrastructure, P.E & Sports, NIRA, coat of arms).

**Reason:** Familiar institution UX without a second product or a parallel permission system. UI hiding is still not the security boundary.

**Consequences:** Sidebar labels change (`Students` → `View Learners`, `Fees` → `Fee types` under Finance). Profile attach-fee lives on the Fees tab. Bursar still opens profiles with `learners.view` and still cannot write grades.

**Rollback/revisit:** Pixel-copy of DashLite/MoES chrome stays out of scope.

## 2026-08-22 — Payment reverse and reject require a bursar reason

**Problem:** Confirmed payments could be reversed with an optional reason stuffed into `provider_ref`. Pending parent submissions could be rejected with no reason and no audit row. The demanded ledger had Confirm but no Reject/Reverse dialogs. Leadership must not undo money.

**Decision:** Keep `fees.payment.reverse` / `fees.payment.reject` (OR `finance.manage` for school-admin break-glass). Both POSTs require `reason` (8–500 characters). Store it on `fee_payments.decision_reason` (FORCE RLS already on the table). Write `fees.payment.reversed` / `fees.payment.rejected` to the audit trail. Ledger and receipt dialogs collect the reason. Director, head, deputy, DOS, and teachers stay forbidden.

**Reason:** A payment change without a documented why is not auditable. UI hiding is not the security boundary — the permission middleware is.

**Consequences:** Existing reject/reverse callers must send a reason. Reversal no longer overwrites `provider_ref`. Learner statements show reversed payments and the reversal reason.

**Rollback/revisit:** Do not grant reverse/reject to director or head. School admin remains break-glass only.

## 2026-08-22 — Product mark is the 21-chord sphere, system-wide

**Problem:** The filled scanline sphere (the current PearlEdu / VoxSign mark) lived in several Blade copies. Login used a stroke-line reconstruction for the preloader. Favicons preferred PNG/ICO and did not advertise `favicon.svg`. The traced `voxsign-logo.svg` dump is not the product mark.

**Decision:** `layouts.partials.logo` is the single inline source (21 filled chords, `viewBox="30 30 340 340"`). Auth, app chrome, marketing, PearlEdu landing, email, and the login preloader include that partial. Favicons and the PWA manifest lead with `favicon.svg`. School `logo_path` remains a per-school crest, not the product mark. Do not use `voxsign-logo.svg` in the UI.

**Reason:** One geometry, recolored with `currentColor` / theme tokens. The preloader should reveal the same mark staff already see in the sidebar, not a different line drawing.

**Consequences:** Raster PNG/ICO remain as fallbacks for clients that ignore SVG icons. Apple touch icons stay PNG.

**Rollback/revisit:** Do not replace a school's uploaded crest with the PearlEdu sphere.


