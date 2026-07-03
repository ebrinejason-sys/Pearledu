# PearlEdu Login Preloader: Copper-Line Logo Build-In

**Goal:** Give `pearledu.voxsign.co.ug` (the `/login` page) a one-time-per-session preloader where the PearlEdu mark — a circle built from horizontal copper lines — draws itself in, line by line, starting from the middle and cascading outward until the full logo has formed, then fades to reveal the login page. Convert the raster `images/brand/logo.png` mark into a recolorable SVG as part of this, and use it everywhere that mark currently appears.

**Non-goals:** No changes to the voxsign.co.ug marketing-site nav logo (a different mark entirely — the inline "V" wordmark SVG in `layouts/marketing.blade.php` is untouched). No pixel-perfect vector trace of the source PNG — see Architecture for why, and the accepted tradeoff. No preloader on any page besides `/login` (not `accept-invitation.blade.php`, not the post-login dashboard).

## Architecture

- **New `public/images/brand/logo.svg`**: procedurally reconstructed, not traced. The source mark is a circle rendered as ~21 horizontal chords (line at height `dy` from center has half-width `sqrt(r² − dy²)`, standard "circle as scanlines" construction). No image-vectorization tool (ImageMagick/Python+PIL/potrace) is available in this environment, so the SVG is built by computing each line's endpoints from that formula rather than tracing the PNG's actual pixels. Color uses `stroke="currentColor"` so any wrapper can recolor it via CSS `color`, matching the per-tenant `--accent` tokens in `config/themes.php` — this is the "changing colors where necessary" mechanism, no per-theme image exports needed.
  - **Accepted tradeoff:** this will look very close to the original but is a clean reconstruction, not an exact trace of the hand-authored PNG's line weights/tapering. Flagged and accepted per your call to proceed rather than provide a pre-vectorized file.
- **New `public/js/vx-preloader.js`**: small standalone module (no Three.js dependency — this is 2D SVG animation, not the 3D avatar). Exports `runPreloader(svgEl, onDone)`.
- **`resources/views/auth/login.blade.php`**: gains a full-screen overlay `<div id="vx-preloader">` containing an inlined copy of `logo.svg` (inlined so individual `<line>` elements can be targeted/animated — an `<img src="logo.svg">` can't be animated internally), shown/skipped based on `sessionStorage`.
- **`brand.blade.php`, `login.blade.php`, `accept-invitation.blade.php`**: swap `<img src=".../logo.png">` for the new SVG (inlined or `<img src=".../logo.svg">` where no animation is needed — `brand.blade.php` and `accept-invitation.blade.php` just need the static mark, not the animation).

## Components

**`logo.svg`**
- `viewBox="0 0 100 100"`, one `<line>` per row (matching the source's apparent ~21-line density), each with `stroke="currentColor"`, `stroke-width` and `stroke-linecap="round"` tuned to visually match the source image's line weight, positioned via the chord formula above with the same y-spacing.
- Each `<line>` carries a `data-index` (0 = middle row, increasing outward) for the preloader script to key off, and lives inside a `<g id="vx-logo-lines">`.

**`vx-preloader.js`**
- `runPreloader(container, { onDone })`:
  - Reads all `line[data-index]` inside the container.
  - For each line, sets an initial `stroke-dasharray`/`stroke-dashoffset` (or a `clip-path` inset, whichever renders a cleaner left-to-right "slide/wipe" reveal — decided during implementation via quick visual comparison) equal to the line's own full length, i.e., invisible at t=0.
  - Staggers each line's reveal transition by `abs(data-index) * STAGGER_MS` (default ~35ms) off a shared start time, so the middle line animates first and outer lines cascade in after it, each individual line's own reveal taking ~250ms left-to-right.
  - After the last line's transition ends (computed, not guessed — `max(data-index) * STAGGER_MS + 250ms`), fades the whole overlay out over ~400ms, then removes it from the DOM and calls `onDone()`.
  - `prefers-reduced-motion: reduce`: skips straight to all lines visible, brief fade, `onDone()` — no staggered animation.

**`auth/login.blade.php`**
- Inline SVG check gate: a tiny synchronous inline `<script>` (before the overlay markup, to avoid a flash) checks `sessionStorage.getItem('vx-preloader-shown')`; if already set, the overlay `<div>` is never rendered visible (rendered with `hidden` attribute or simply not inserted — implementation detail, functionally: no overlay, no animation, straight to the page).
- If not set: overlay renders, `vx-preloader.js` runs, `onDone` sets the sessionStorage flag and removes the overlay.
- Overlay sits at `position:fixed;inset:0;z-index:999` above the existing split-layout content, background matching `--bg`/`--sidebar` theme token so it doesn't flash a mismatched color before fading.

## Data flow

No new data flows, no backend involvement — this is entirely client-side (sessionStorage + CSS/SVG animation). `LoginController` is unchanged.

## Error handling

- If `logo.svg` fails to load/parse for some reason, the inline `<script>`'s `runPreloader` call is wrapped so a failure falls back to immediately removing the overlay (`onDone()` called directly) rather than leaving a stuck full-screen blocker over the login form — a broken preloader must never trap a user off their login page.
- `sessionStorage` unavailable (privacy mode edge case in some browsers): wrapped in try/catch; treat as "always show" rather than throwing.

## Testing

- New assertions in `tests/Feature/LoginPageTest.php`:
  - Response includes `id="vx-preloader"` and an inlined `<svg` containing `id="vx-logo-lines"`.
  - Response includes the sessionStorage-gate inline script (assert on a stable string like `vx-preloader-shown`).
  - `brand.blade.php`/`accept-invitation.blade.php` no longer reference `logo.png` (assert `assertDontSee('logo.png')` / `assertSee('logo.svg')` in the relevant response bodies).
- No PHPUnit coverage for the actual line-by-line stagger timing or `prefers-reduced-motion` branch (browser-only) — verified manually via `/verify`: load `/login` fresh (cleared sessionStorage), confirm lines draw in starting from the middle and cascading outward, confirm fade-out reveals the login form, reload the page and confirm the preloader does NOT replay, and confirm reduced-motion setting skips straight to the static logo.
- `.php84/php.exe artisan view:cache` then `view:clear` as the Blade-syntax smoke check.
