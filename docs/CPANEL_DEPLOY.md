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

3. **Point every app-facing domain at `public/`**, not the app root:
   - cPanel → Domains → `voxsign.co.ug` (apex, VoxSign landing) — Document
     Root: `/home/voxsignco/pearledu-app/public`
   - cPanel → Domains → `pearledu.voxsign.co.ug` (platform app) — same
     Document Root: `/home/voxsignco/pearledu-app/public`
   - The wildcard `*.voxsign.co.ug` subdomain (for auto-provisioned tenant
     subdomains like `pearledu1.voxsign.co.ug`) also needs to resolve to
     the same Document Root — the app resolves which tenant/host it's
     serving internally via `ResolveTenant` middleware, so one codebase +
     one document root serves all of them.
   - This keeps `.env`, `app/`, `storage/`, etc. unreachable from the web —
     required, since `.env` holds DB credentials and this app handles
     DPPA-protected data (NIN/LIN).

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
   (invitations, mail, and scheduled `queue:work --stop-when-empty` depend on this).
   Until cron is live, use `QUEUE_CONNECTION=sync`.

8. **Subdomains are optional** — wildcard `*.voxsign.co.ug` may still point at
   the same Document Root for legacy tenant hosts, but onboarding no longer
   requires creating a subdomain first. Isolation is by `schools.id` (tenant id)
   via role assignments + RLS.

## Ongoing deploys

After pushing to `main` on GitHub:

- cPanel → Git Version Control → your repo → **Update from Remote**, then
  **Deploy HEAD Commit**
- This re-runs `.cpanel.yml`: copies files, `composer install`,
  `db:verify-security` (deploy aborts here if RLS/role checks fail),
  `migrate --force`, then cache rebuilds.
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

Also confirm in **cPanel → Domains** that `pearledu.voxsign.co.ug` (and the
apex) use document root `/home/voxsignco/pearledu-app/public`, and in
**MultiPHP Manager** that the domain is on **PHP 8.3+** (same as CLI).

To seed the platform admin after a failed `PlatformSeeder` (config was
cached, so `env()` looked empty):

```bash
cd /home/voxsignco/pearledu-app
php artisan config:clear
php artisan db:seed --class=PlatformSeeder --force
php artisan config:cache
```

## Uploading the 3D avatar model (manual step, not part of git deploy)

`thirg glb.glb` is gitignored (see `.gitignore`) and is never part of a git
commit, so cPanel's Git Version Control deploy pipeline does not put it on
the server. The avatar-demo partial expects it at `public/models/avatar.glb`
on the live site. To deploy it:

1. In cPanel File Manager (or via SFTP), navigate to
   `/home/voxsignco/pearledu-app/public/`.
2. Create a `models` directory if it doesn't already exist.
3. Upload the local file `thirg glb.glb` into that directory, renaming it to
   `avatar.glb` on upload (the code requests `/models/avatar.glb`).
4. Verify: `curl -I https://voxsign.co.ug/models/avatar.glb` should return
   `HTTP/1.1 200` (or `403`/`404` if the upload path or filename is wrong —
   re-check step 3).

This is a one-time step unless the model file itself changes. Re-run it if
`thirg glb.glb` is ever replaced with an updated version.
