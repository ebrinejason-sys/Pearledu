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

2. **Edit `.cpanel.yml`** in the repo (before or after first deploy) and
   replace `CPANEL_USERNAME` with your real cPanel username. `$DEPLOYPATH`
   is where the *application code* lands — keep it outside `public_html`.

3. **Point the domain at `public/`**, not the app root:
   - cPanel → Domains → `pearledu.voxsign.co.ug` (and any other app domain)
   - Set Document Root to `$DEPLOYPATH/public` (e.g.
     `/home/CPANEL_USERNAME/pearledu-app/public`)
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
   cd /home/CPANEL_USERNAME/pearledu-app
   composer install --no-dev --optimize-autoloader
   php artisan db:verify-security   # must print OK — hard gate
   php artisan migrate --force
   php artisan db:seed --force      # optional, only if you want demo data
   ```

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
