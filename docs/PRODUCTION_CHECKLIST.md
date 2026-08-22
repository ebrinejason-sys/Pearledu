# Production go-live checklist (PearlEdu)

Code on `main` is production-capable. What remains is **server configuration and credentials only you can supply**.

## A. What the app now enforces in code

After deploy, cPanel runs:

```bash
php artisan db:verify-security
php artisan migrate --force
php artisan app:production-check
```

`app:production-check` fails the deploy if production is misconfigured (debug on, insecure cookies, missing APP_KEY, demo seed on, local tenancy hosts, empty mail password, UAT SchoolPay URL, RLS failure).

Also in code:

- HTTPS force + `.htaccess` HTTPS redirect
- Security headers (HSTS in production, nosniff, frame deny, referrer policy)
- `fake` / `log` SMS drivers **cannot send** in production (throws)
- Passwords min 10 characters
- SchoolPay + EMIS are per-school opt-ins

## B. What you must provide / do on the server

Fill these and keep them **only on the server `.env`** (never commit):

### 1. Required for any go-live

| Item | Example / notes |
|---|---|
| `APP_KEY` | Run once: `php artisan key:generate` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://pearledu.voxsign.co.ug` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_DOMAIN` | `.voxsign.co.ug` |
| `SESSION_LIFETIME` | `30` (idle logout minutes; enforced even with Remember me) |
| `SESSION_IDLE_WARNING_MINUTES` | `2` (in-app warning before sign-out) |
| `SEED_DEMO_TENANT` | `false` |
| Postgres | Non-superuser, non-`BYPASSRLS` role + password |
| Tenancy hosts | `TENANCY_BASE_DOMAIN=voxsign.co.ug` |
| | `TENANCY_LANDING_HOSTS=voxsign.co.ug,www.voxsign.co.ug` |
| | `TENANCY_PEARLEDU_LANDING_HOST=pearledu.voxsign.co.ug` |
| `PLATFORM_ADMIN_EMAIL` | Your operator email |
| `PLATFORM_ADMIN_PASSWORD` | Strong unique password (used by PlatformSeeder) |
| `MAIL_PASSWORD` | Resend SMTP API key / password |
| Cron | `* * * * * cd /home/voxsignco/pearledu-app && php artisan schedule:run >> /dev/null 2>&1` |

### 2. Required if you use SMS

| Item | Notes |
|---|---|
| `SMS_DRIVER` | `twilio` |
| `TWILIO_ACCOUNT_SID` | From Twilio console |
| `TWILIO_AUTH_TOKEN` | From Twilio console |
| `TWILIO_FROM_NUMBER` | E.164 sender |

Without these, Send SMS is blocked in production (safe fail).

### 3. Required if you use SchoolPay fee collection

Per school (not global `.env`):

1. Contact Service Cops: **support@schoolpay.co.ug** / 0200 502 140
2. Get **school code** + **API password**
3. In PearlEdu → School identity → enable **SchoolPay** and save credentials
4. Register webhook URLs shown on that page
5. Set each learner’s **10-digit** SchoolPay payment code

Global `.env` can keep:

```env
SCHOOLPAY_BASE_URL=https://schoolpay.co.ug/paymentapi
SCHOOLPAY_ADHOC_ENABLED=true
```

### 4. Recommended

| Item | Notes |
|---|---|
| `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET` | Cloudflare Turnstile for contact / onboard / `/apply` |
| `LOG_LEVEL` | `warning` or `error` |
| `LOG_STACK` | `daily` |
| Document root | Domain → `/home/voxsignco/pearledu-app/public` |

## C. Deploy sequence (you run on cPanel / SSH)

```bash
cd /home/voxsignco/pearledu-app
# 1) Update .env with the values above
php artisan config:clear
composer install --no-dev --optimize-autoloader
php artisan db:verify-security          # must print OK
php artisan migrate --force
php artisan db:seed --force             # FIRST TIME ONLY
php artisan app:production-check        # must print OK
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then: Git Version Control → Update from Remote → Deploy HEAD (ongoing deploys).

## D. Pilot school smoke test

1. Sign in as platform admin → onboard or open the pilot school  
2. Invite school admin → accept invite (password 10+) → land on dashboard  
3. Complete setup wizard: identity, year/terms, classes, subjects, import learners, invite **named roles** (Head, DOS, bursar, teachers) — do not stack every job on School Admin  
4. Assign teaching (class + subject) before asking teachers to enter marks  
5. Create a student + fee invoice  
6. Invite a parent → pay fees (manual path, then SchoolPay if enabled)  
7. Walk away from a signed-in staff session for 30 minutes and confirm it asks for a password again  
8. Sign in as bursar and confirm Assessment is forbidden; sign in as DOS and confirm Fees is forbidden  
9. Send one SMS only after Twilio is configured  

### Online walkthrough school (optional)

To click through roles on the live host, sign in to `/admin` and open **Schools → Demonstration school**. Set one shared password (min 10 characters). That creates or refreshes St. Kizito and applies the password to every named test login.

SSH still works if you prefer:

```bash
cd /home/voxsignco/pearledu-app
php artisan school:seed-walkthrough --password='Choose-a-long-password' --force
```

Then sign in at `/login` with `admin@stkizito.test`, `head@stkizito.test`, `dos@stkizito.test`, `bursar@stkizito.test`, `english@stkizito.test`, `ct.p4@stkizito.test`, and so on (same password). Attendance and marks keep a local copy if the phone loses signal; fees still need a connection.

Do **not** put `SEED_TEST_SCHOOL_PASSWORD` in the live `.env` (`app:production-check` fails if it is set). When testing is finished, purge **St. Kizito Demonstration Primary** from the platform school page.

## E. Reply with these when ready (I can verify the checklist wording against them — do not paste live secrets into chat if you prefer not to)

1. Confirm document root points at `…/pearledu-app/public`  
2. Confirm cron for `schedule:run` is installed  
3. Confirm Resend domain is verified and `MAIL_PASSWORD` is set  
4. Whether you want SMS at launch (yes → Twilio values on server; no → leave `SMS_DRIVER=fake`)  
5. Whether SchoolPay is needed for the first school (yes → Service Cops credentials)  
6. Platform admin email you want seeded  
