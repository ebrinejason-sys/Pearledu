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

2. `$DEPLOYPATH` (`/home/voxsign/pearledu-app/`) is where the *application
   code* lands — outside `public_html`, not inside it.

3. **Point every app-facing domain at `public/`**, not the app root:
   - cPanel → Domains → `voxsign.co.ug` (apex, VoxSign landing) — Document
     Root: `/home/voxsign/pearledu-app/public`
   - cPanel → Domains → `pearledu.voxsign.co.ug` (platform app) — same
     Document Root: `/home/voxsign/pearledu-app/public`
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
   cd /home/voxsign/pearledu-app
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

## Uploading the 3D avatar model (manual step, not part of git deploy)

`thirg glb.glb` is gitignored (see `.gitignore`) and is never part of a git
commit, so cPanel's Git Version Control deploy pipeline does not put it on
the server. The avatar-demo partial expects it at `public/models/avatar.glb`
on the live site. To deploy it:

1. In cPanel File Manager (or via SFTP), navigate to
   `/home/voxsign/pearledu-app/public/`.
2. Create a `models` directory if it doesn't already exist.
3. Upload the local file `thirg glb.glb` into that directory, renaming it to
   `avatar.glb` on upload (the code requests `/models/avatar.glb`).
4. Verify: `curl -I https://voxsign.co.ug/models/avatar.glb` should return
   `HTTP/1.1 200` (or `403`/`404` if the upload path or filename is wrong —
   re-check step 3).

This is a one-time step unless the model file itself changes. Re-run it if
`thirg glb.glb` is ever replaced with an updated version.
