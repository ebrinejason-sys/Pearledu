# PearlEdu Login Page Redesign + 3D Avatar Design

**Goal:** Replace the bare centered login card with a full-bleed split-screen layout (colored brand panel + form on the left, white stage with a 3D avatar on the right), matching the visual quality of the VoxSign marketing site while staying theme-aware across PearlEdu's white-label themes (`pearledu`, `moodle`, `emis`). Extract the landing page's Three.js avatar-loading logic into a shared module so the login page can reuse it with different pose behavior.

**Non-goals:** No changes to the marketing landing page's own avatar demo behavior or colors. No backend/controller changes — `LoginController::show()` is unchanged. No changes to the app's post-login navigation (separate, later piece of work).

## Architecture

- New `resources/views/layouts/auth.blade.php`: minimal full-height layout (theme CSS variables, no app topbar/nav, `@yield('content')`). Used only by `auth/login.blade.php` for now.
- Rewritten `resources/views/auth/login.blade.php`: two-panel split screen using this new layout.
- New shared static ES module `public/js/vx-avatar-loader.js`: WebGL detection, GLTFLoader load of `/models/avatar.glb`, CSS-variable-driven recoloring, fallback-to-SVG on failure, and two pose modes (`cycle`, `idle`). No bundler — plain `<script type="module">` import, matching the existing project-wide constraint.
- `resources/views/landing/partials/avatar-demo.blade.php` refactored to call the shared module in `cycle` mode with its existing pose data — behavior and output unchanged from the visitor's perspective.

## Components

**`layouts/auth.blade.php`**
- Renders `{!! $themeCss !!}` (already globally composed by `AppServiceProvider`) plus a small reset/base style block.
- No `.wrap` container, no app header — full viewport height (`min-height:100vh`) two-column flex/grid.

**`auth/login.blade.php`**
- `.vx-auth-panel` (left): `background:var(--sidebar); color:var(--sidebar-ink)`. Contains PearlEdu logo (`images/brand/logo.png`) + wordmark, "Sign in" heading, and the existing form fields (email, password, remember, CSRF, `@error('email')`) — validation/error markup unchanged from today's `auth/login.blade.php`.
- `.vx-auth-stage` (right): `background:var(--surface)` (or `var(--bg)`), holds `<div id="vx-login-avatar-3d">` mount point plus a visually-hidden descriptive `<p>` for screen readers (matching the `.vx-sr-only` pattern from the landing page).
- `@media(max-width:860px)`: `.vx-auth-stage{display:none}`, panel becomes full width, single column.

**`public/js/vx-avatar-loader.js`**
- Exported function: `mountAvatar(container, { modelUrl, mode, poses, captionEl, phrases, colorVars })`.
- `mode: 'cycle'` — today's discrete pose-hold-transition state machine + caption swapping (landing page).
- `mode: 'idle'` — new: continuous per-frame sinusoidal offset applied to `Spine1` and `RightArm` from their rest quaternion (small amplitude, slow period, no captions). Renders one static rest frame (no `requestAnimationFrame` loop) when `prefers-reduced-motion: reduce`.
- `colorVars`: which two CSS custom properties to read for body/joint recoloring — landing page passes `['--sign','--voice']` (unchanged), login page passes `['--brand','--accent']` (theme-driven).
- Same fallback SVG rendering + `showFallback()` behavior on WebGL-unsupported or model-load failure, parameterized by container size (login page's stage vs. landing page's smaller demo box).

## Data flow

No new data flows. `$themeCss` already reaches every view via the existing `View::composer('*', ...)` in `AppServiceProvider`. `LoginController` is unchanged.

## Error handling

- WebGL unavailable, or `/models/avatar.glb` 404s/fails to parse → fallback SVG shown in the stage panel (reused/adapted from the existing fallback markup), no console-breaking errors, page remains fully usable for login regardless.
- Existing login form error handling (`@error('email')`, rate limiting, validation) is untouched.
- `prefers-reduced-motion: reduce` is respected in both `idle` and `cycle` modes (static single frame, no animation).

## Testing

- New `tests/Feature/LoginPageTest.php` (this route has no tenant-resolving middleware, so — unlike the marketing landing tests — it runs locally against the already-running local Postgres):
  - Split layout renders: assert presence of `.vx-auth-panel` and `.vx-auth-stage` (or equivalent class hooks) in the response.
  - Avatar mount container present: `id="vx-login-avatar-3d"`.
  - Theme-token usage: assert the rendered `<style>`/inline styles reference `var(--sidebar)` / `var(--brand)`, not a hardcoded VoxSign hex value, so the page stays theme-agnostic.
  - Mobile media query present: assert the `@media(max-width:860px)` rule hiding `.vx-auth-stage` ships in the response.
  - Existing behavior still works: email/password fields render, CSRF token present, invalid-credentials flow still shows the `@error('email')` message (extend/adapt from prior manual coverage — there was no dedicated login test file before this).
- `.php84/php.exe artisan view:cache` (then `view:clear`) as the Blade-syntax smoke check, per the project's established pattern.
