# VoxSign / PearlEdu — Data Protection & Security

*Living document. Aligned to the Uganda **Data Protection and Privacy Act, 2019 (DPPA)** and its 2021 Regulations. It states both the policy and the exact mechanisms in the codebase that enforce it. Where something is designed but not yet built, it is marked **(planned)**.*

---

## 1. Principles we operate under

We follow the DPPA's data-protection principles: collect lawfully and fairly; collect only what's necessary; keep it accurate; retain it no longer than needed; secure it; and respect data-subject rights. Two product-level commitments sit above everything:

1. **Isolation is database-enforced, not just application-enforced.** One school can never see another's data, even if application code is wrong.
2. **There is no privileged key that bypasses tenant isolation.** (See §5 — this directly answers the "service-role key" concern.)

---

## 2. What data we collect, and why

| Category | Examples | Why (lawful basis) | Sensitivity |
|---|---|---|---|
| Account identity | full name, email, phone, password **hash** | provide the service / contract | normal |
| Role & access | role assignments per school | provide the service | normal |
| Learner records | name, class, EMIS number | school's statutory record-keeping | normal |
| **Sensitive identifiers** | **NIN, LIN** | EMIS/Ministry compliance | **high** |
| Guardianship | parent ↔ student links | parent communication | normal |
| Finance | fee charges, payments (decimal UGX) | school operations | normal |
| Communications | SMS records, credit ledger | deliver notifications | normal |
| Security telemetry | audit log (actor id, action, IP, time) | security / legal obligation | normal |

We do **not** collect biometrics, location tracking, or behavioural advertising data. Passwords are never stored in plaintext (`bcrypt`/`argon2` via Laravel's `hashed` cast).

### Treatment of sensitive identifiers (NIN/LIN)
- **Encrypted at rest** at the application layer (`encrypted` cast on `students.nin` / `students.lin`), so they are unreadable in the raw database, in backups, and in DB dumps.
- **Every read is audited** (`sensitive.read` in `audit_logs`) via model accessors — we can show exactly who viewed an identifier and when.
- LIN is a foreign reference issued by the Ministry; PearlEdu never generates it.

---

## 3. How the data is protected (defense in depth)

1. **PostgreSQL Row-Level Security (RLS)** on every tenant table, with `FORCE ROW LEVEL SECURITY` so even the table owner obeys it. Policies key off per-request session variables (`app.is_platform`, `app.current_school_id`). Fail-closed: no context ⇒ zero rows.
2. **Application global scope** (`BelongsToSchool`) mirrors the same predicate in Eloquent — a second, independent layer.
3. **Encryption in transit** (HTTPS everywhere; wildcard TLS) and **at rest** for sensitive fields.
4. **Audit trail** for security-relevant actions: logins, failures, onboarding, account creation, school entry, SMS credit movements, account erasure, and sensitive reads.
5. **Least privilege**: the app connects as a **non-superuser** Postgres role; the `db:verify-security` command refuses the role if it is superuser or has `BYPASSRLS`.

---

## 4. Analytics — are we distributing people's data?

No. Our stance:
- **No third-party trackers or ad/marketing SDKs** in the product surfaces. The platform is not ad-funded and does not sell or share personal data.
- Product analytics, when added, will be **first-party and aggregate** — counts and rates (e.g. "schools onboarded", "SMS sent"), never per-person profiles shipped to external analytics vendors. **(planned: a privacy-preserving, self-hosted metrics layer.)**
- Any future external processor (e.g. an email/SMS provider) is a **data processor** under a DPA, receiving only the minimum needed (e.g. a phone number + message to deliver an SMS), never the wider record.
- Error logs are scrubbed of sensitive identifiers; `APP_DEBUG=false` in production so stack traces never leak data to users.

---

## 5. Can RLS be "outpaced" by a service-role key? — No, by design

This is the question that breaks most multi-tenant systems (especially Supabase-style setups where a *service-role key* silently bypasses RLS). Our architecture removes that failure mode:

- **We do not use a bypass/service-role key at all.** "Platform admin sees all tenants" is **a branch inside the RLS policy** (`app.is_platform = 'on'`), not a privileged connection that skips RLS. The same connection, same role, always under policy.
- **`FORCE ROW LEVEL SECURITY`** means even the table **owner** is bound by policies — there is no "owner escape hatch".
- **The app role is non-superuser and non-`BYPASSRLS`.** Postgres superusers and `BYPASSRLS` roles skip RLS entirely; we forbid them. `php artisan db:verify-security` asserts this in deploy/CI and **fails the deploy** otherwise.
- **The `is_platform` switch is server-controlled**, set only by `EnsurePlatformOperator` after authentication — it is never derived from user input, a header, or a token the client can forge.
- **Entering a school drops the all-tenant view**: a platform operator inside School A is scoped to A and cannot read B.

Net effect: there is no key, header, or connection string a developer (or attacker) can present to read across tenants. Pure database-level security.

---

## 6. Right to erasure — delete your account, and your data is gone

DPPA gives data subjects the right to erasure. Implemented in `AccountDeletionService` and exposed at **Account → Delete account** (password + typed `DELETE` confirmation):

- The **user identity is hard-deleted** (`forceDelete`, not a soft delete) — truly removed from the database.
- **Role assignments, guardian links, and pending invitations are deleted.**
- If the person was a **learner login**, the learner row is **de-identified** (login link removed, NIN/LIN nulled) rather than destroyed, because the **academic record belongs to the school** as a statutory record — deleting a parent's account must not erase a child's results. This boundary is deliberate and documented.
- **Audit rows are retained but de-identified**: the actor foreign key nulls on delete, and audit entries store ids and actions, not names — so we keep a tamper record for security/legal duty without keeping the person's identity.

Erasure runs in **platform scope** so the person's data is reached across **every** school they belonged to, in one transaction.

> Retention note: where law requires a school to keep a financial or academic record, that record is retained by the *school* under its own obligation; the *individual's account and personal identifiers* are still erased.

---

## 7. Threat & loophole register

A living list of ways tenant isolation or privacy could fail, with status.

| # | Loophole | Mitigation | Status |
|---|---|---|---|
| 1 | Superuser/`BYPASSRLS` role silently skips RLS | non-privileged role + `db:verify-security` gate | **enforced** |
| 2 | Raw SQL bypasses Eloquent scope | RLS constrains raw queries too | **enforced** |
| 3 | Queued job runs with no/other tenant context | Jobs use `TenantAware` + `RestoreTenantContext` middleware to re-pin GUCs | **enforced (contract ready; no tenant jobs yet)** |
| 4 | Connection reuse leaks session GUCs between requests | GUCs re-set every request (fail-closed) by `ResolveTenant` | **enforced** |
| 5 | IDOR — guessing another tenant's record id | scope + RLS both return null cross-tenant | **enforced** |
| 6 | Forged `is_platform` via header/token | set server-side post-auth only | **enforced** |
| 7 | Sensitive identifiers readable in DB/backups | NIN/LIN encrypted at app layer | **enforced** |
| 8 | Sensitive data leaking via logs/errors | `APP_DEBUG=false`, scrub identifiers from logs | **enforced / ongoing** |
| 9 | Invitation token reuse or theft | hashed tokens, single-use, 7-day expiry | **enforced** |
| 10 | Login brute force / credential stuffing | rate limiting per email+IP, throttled routes | **enforced** |
| 11 | Account-deletion abuse (deleting others) | password + explicit confirmation required | **enforced** |
| 12 | SMS toll fraud / credit tampering | append-only credit ledger, locked balance writes, platform-only top-up | **enforced** |
| 13 | Email enumeration on login/reset | uniform error messages | **enforced** |
| 14 | Mass-assignment of protected fields | explicit `$fillable`; `is_platform` never user-fillable on tenant paths | **enforced** |
| 15 | Cross-subdomain session/cookie leakage | `SESSION_DOMAIN=.voxsign.co.ug` + `SESSION_SECURE_COOKIE=true` in production; TrustProxies + HTTPS force | **enforced (env + code)** |
| 16 | Custom-domain spoofing to impersonate a tenant | domains require `verified_at` before routing | **enforced** |
| 17 | Backups exfiltrated | encrypted sensitive fields stay encrypted in dumps; encrypt backups at rest | **partial (encrypt backups — planned)** |
| 18 | Third-party processor over-sharing | send minimum payload; DPA with each processor | **planned** |
| 19 | Data export endpoints leaking across tenants | all exports run under tenant scope + RLS | **enforced for current endpoints** |
| 20 | 2FA not enforced for high-privilege accounts | Platform operators require email OTP; authenticator TOTP optional; school-user 2FA | **partial (platform email OTP enforced; school-user 2FA planned)** |
| 21 | Insider/operator over-access | every `school.entered` is audited + scoped | **enforced** |
| 22 | CSRF on state-changing actions | Laravel CSRF tokens on all forms | **enforced** |

---

## 8. Operational duties

- **Breach response (DPPA):** on a personal-data breach, notify the Personal Data Protection Office and affected data subjects without undue delay; the audit log supports forensic scoping.
- **Registration:** register as a data collector/processor with the PDPO as required.
- **Access requests:** data subjects may request a copy of their data (subject access) and erasure (§6). **(planned: self-service data export.)**
- **Sub-processors:** maintain a current list (email, SMS) with a DPA each.
- **Key management:** `APP_KEY` rotation procedure documented; rotating it requires re-encrypting `encrypted` columns.

---

## 9. Verifying the guarantees

```bash
php artisan db:verify-security      # catalog-driven: every school_id table + schools must FORCE RLS
php artisan test --filter=TenantIsolationTest   # proves cross-tenant reads/writes are blocked (Eloquent + raw)
```
Run both in CI and on every deploy. If either fails, the isolation guarantee is not in force and the release must not ship. `db:verify-security` discovers tenant tables from PostgreSQL’s catalog (not a hard-coded list). Critical child tables also use composite FKs `(school_id, parent_id)` so mixed-tenant references cannot be inserted even under platform scope.
