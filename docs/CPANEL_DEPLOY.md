# Deploying to cPanel (Baasa Cloud Elite)

This app deploys via cPanel's **Git Version Control** feature, which pulls
from the private GitHub repo (`ebrinejason-sys/Pearledu`) and runs the tasks
in `.cpanel.yml` on every deploy.

## One-time setup

1. **cPanel → Git Version Control → Create**
   - Clone URL: `https://github.com/ebrinejason-sys/Pearledu.git`
   - Repository Path: something outside any web-exposed folder, e.g.
     `/home/CPANEL_USERNAME/repositories/pearledu`
   - You'll need to authenticate the clone — either a GitHub PAT with `repo`
     scope, or add the deploy via cPanel's SSH key (Git Version Control has
     an "SSH keys" helper if you prefer `git@github.com:...` over HTTPS).

2. `$DEPLOYPATH` (`/home/voxsignco/pearledu-app/`) is where the *application
   code* lands — outside `public_html`, not inside it.

3. **Two document roots, one codebase** (required on this host because the
   main domain Document Root cannot be changed away from `public_html`, and
   LiteSpeed 404s when `public_html` is a symlink):

   | Domain | Document Root | Notes |
   |--------|---------------|--------|
   | `voxsign.co.ug` (main) | `/home/voxsignco/public_html` | Locked. Deploy publishes a **real** directory here whose `index.php` bootstraps `~/pearledu-app`. |
   | `pearledu.voxsign.co.ug` | `/home/voxsignco/pearledu-app/public` | Set in Domains UI (relative path: `pearledu-app/public`). |
   | `www.voxsign.co.ug` | same as main / parked as needed | Keep as alias of the apex. |

   Do **not** symlink `public_html` → `pearledu-app/public`. Deploy replaces that
   symlink with a real folder automatically.

   Host routing is unchanged: `TENANCY_LANDING_HOSTS` → VoxSign marketing,
   `TENANCY_PEARLEDU_LANDING_HOST` → PearlEdu app / school portal.

4. **Create `.env` directly on the server** (never via git):
   - SSH or File Manager into `$DEPLOYPATH/.env`
   - Copy from `.env.example`, fill in real `DB_*` (non-superuser,
     non-BYPASSRLS Postgres role — see CLAUDE.md invariant #2), `APP_KEY`
     (`php artisan key:generate` once on the server), Resend SMTP creds.

5. **First deploy**: run migrations manually once via SSH to confirm
   everything works before trusting the automated `.cpanel.yml` pipeline:

   ```bash
   cd /home/voxsignco/pearledu-app
   composer install --no-dev --optimize-autoloader
   php artisan db:verify-security   # must print OK — hard gate
   php artisan migrate --force
   php artisan db:seed --force      # REQUIRED once: RoleSeeder + PlatformSeeder + PricingPlanSeeder
                                    # Keep SEED_DEMO_TENANT=false in production
   ```

   After first seed, set a strong `PLATFORM_ADMIN_PASSWORD` (already used by seeder)
   and sign in at `https://pearledu.voxsign.co.ug/login`.

6. **Sessions on the shared host** — set `SESSION_DOMAIN=.voxsign.co.ug`,
   `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, `APP_ENV=production`, and
   `APP_URL=https://pearledu.voxsign.co.ug` in `.env`. School users and
   PearlEdu operators all use the same host:
   - Schools: `https://pearledu.voxsign.co.ug/login` → `/home` (tenant from membership)
   - PearlEdu admin/staff: `https://pearledu.voxsign.co.ug/admin`
   - Legacy `/platform` and `/console` redirect to `/admin`

7. **Cron / queue** — with `QUEUE_CONNECTION=database`, add a cPanel cron:
   `* * * * * cd /home/voxsignco/pearledu-app && php artisan schedule:run >> /dev/null 2>&1`
   (invitations, mail, `queue:work --stop-when-empty`, and `schoolpay:sync` depend on this).
   Until cron is live, use `QUEUE_CONNECTION=sync`.

8. **Production gate** — after migrate, run:
   `php artisan app:production-check`
   Deploy (`.cpanel.yml`) runs this automatically and aborts if the server `.env` is unsafe
   (debug on, insecure cookies, missing mail password, demo seed, etc.).
   Full list: `docs/PRODUCTION_CHECKLIST.md`.

9. **Subdomains are optional** — wildcard `*.voxsign.co.ug` may still point at
   the same Document Root for legacy tenant hosts, but onboarding no longer
   requires creating a subdomain first. Isolation is by `schools.id` (tenant id)
   via role assignments + RLS.

## Ongoing deploys

After pushing to `main` on GitHub:

- cPanel → Git Version Control → your repo → **Update from Remote**, then
  **Deploy HEAD Commit**
- This runs `scripts/cpanel-deploy.sh`:
  - rsync app → `/home/voxsignco/pearledu-app`
  - publish main-domain bridge → `/home/voxsignco/public_html` (real dir, not symlink)
  - `composer install` **only when `composer.lock` changed**
  - migrate + caches
- First deploy after a lockfile change still takes a few minutes (Composer on
  shared hosting). Later deploys should finish in under a minute.
- Do **not** click Deploy again while the blue “in progress” banner is showing —
  you will stack two Composer runs. Watch Terminal with
  `ps aux | grep -E 'composer|cpanel-deploy'` if you need to confirm it is working.
- `db:verify-security` still aborts the deploy if RLS is misconfigured.
  `app:production-check` is logged but no longer blocks the copy.

- Optionally wire a GitHub webhook to cPanel's Git Version Control "Pull &
  Deploy" endpoint so pushing to `main` deploys automatically — set this up
  once the manual flow is verified.

## Before trusting any deploy as "done"

Per CLAUDE.md: run `php artisan db:verify-security` and
`php artisan test --filter=TenantIsolationTest` — ideally in a staging
subdomain first, not directly against a school's live data.

## If the site returns HTTP 500 ("This page isn't working")

Artisan working in Terminal while the browser 500s usually means the
**web PHP** handler differs from CLI, or Laravel cannot write logs/cache.

In cPanel Terminal:

```bash
cd /home/voxsignco/pearledu-app
php -v                                          # must be 8.3+
grep -E '^(APP_KEY|APP_DEBUG|DB_|SESSION_)' .env
php artisan config:clear
php artisan cache:clear
tail -n 80 storage/logs/laravel.log             # real exception
# After a browser hit, re-tail the log if it was empty.
ls -la storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Also confirm in **cPanel → Domains**:
- `pearledu.voxsign.co.ug` → `/home/voxsignco/pearledu-app/public`
- `voxsign.co.ug` → `/home/voxsignco/public_html` (locked; must be a real dir, not a symlink)
and in **MultiPHP Manager** that the domain is on **PHP 8.3+** (same as CLI).

If the main site still LiteSpeed-404s after deploy:
```bash
ls -la /home/voxsignco/public_html   # must NOT say "->" symlink
head -5 /home/voxsignco/public_html/index.php   # must reference pearledu-app
```
Re-run deploy, or manually:
```bash
cd /home/voxsignco/repositories/Pearledu   # or wherever the git clone lives
export DEPLOYPATH=/home/voxsignco/pearledu-app
export MAIN_DOCROOT=/home/voxsignco/public_html
/bin/bash ./scripts/cpanel-deploy.sh
```

If `pearledu.voxsign.co.ug` LiteSpeed-404s while `voxsign.co.ug` works and
`pearledu-app/public/index.php` exists, check that the app root is traversable:
```bash
# Must NOT be drwx------ (700) — LiteSpeed cannot enter public/
ls -ld /home/voxsignco/pearledu-app
chmod 755 /home/voxsignco/pearledu-app /home/voxsignco/pearledu-app/public
curl -sI https://pearledu.voxsign.co.ug/ | head -10
```

To seed the platform admin after a failed `PlatformSeeder` (config was
cached, so `env()` looked empty):

```bash
cd /home/voxsignco/pearledu-app
php artisan config:clear
php artisan db:seed --class=PlatformSeeder --force
php artisan config:cache
```

## SchoolPay fees (production)

1. Each school enables SchoolPay under **School identity** and enters their
   SchoolPay school code + API password (encrypted at rest).
2. Register these public HTTPS URLs in the SchoolPay portal for that school
   (ids are per `schools.id`):
   - Adhoc callback: `https://pearledu.voxsign.co.ug/webhooks/schoolpay/{id}/callback`
   - Webhook notify: `https://pearledu.voxsign.co.ug/webhooks/schoolpay/{id}/notify`
3. Map each learner’s SchoolPay payment code on the student record so channel
   / agent payments auto-match open invoices.
4. Ensure cron runs `schedule:run` — `schoolpay:sync` runs daily as a backup
   because SchoolPay webhooks are single-attempt.
5. Optional env on the server:
   - `SCHOOLPAY_BASE_URL=https://schoolpay.co.ug/paymentapi`
   - `SCHOOLPAY_ADHOC_ENABLED=true`

## Uploading the 3D avatar model

`public/models/avatar.glb` is tracked in git (with a `.gitignore` exception).
After deploy it should already be present. If a browser 404s `/models/avatar.glb`,
re-upload it once to `/home/voxsignco/pearledu-app/public/models/avatar.glb`.
