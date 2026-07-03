# VoxSign Landing Page v5: Accessibility-First Positioning + Professional Footer + Hero Avatar

**Goal:** Reposition VoxSign Accessibility ahead of VoxSign Institutions (formerly branded "PearlEdu") throughout the marketing site, give each division a "Get Started" link out to its subdomain alongside the existing "Learn more" in-page anchor, replace the thin single-row footer with a multi-column professional footer, and add a full-length, drag-to-rotate interactive 3D avatar to the hero section. Also fixes the `.gitignore` bug that has been silently dropping `public/models/avatar.glb` from every push.

**Non-goals:** No changes to the PearlEdu app itself (subdomain name, login flow, tenant provisioning). No live `accessibility.voxsign.co.ug` app or route — that CTA is an outbound link to a host that isn't wired up yet, by explicit choice. No changes to the "See it in motion" captioned avatar-demo section further down the page. No privacy/terms links in the footer — no such pages exist anywhere in the app.

## Architecture

- `resources/views/landing/partials/divisions.blade.php`: swap card order (Accessibility first), rename "PearlEdu" → "VoxSign Institutions", add a second CTA (`Get Started`) per card.
- `resources/views/landing/partials/hero.blade.php`: swap mention order in the lead paragraph, add a new avatar-mount container and its `mountAvatar` call.
- `resources/views/landing/partials/pearledu.blade.php`: heading copy updated to "VoxSign Institutions"; file itself is not renamed (blade include path stays `landing.partials.pearledu`, matching the still-unchanged `pearledu.voxsign.co.ug` subdomain concept) — only the visible `<h2>` text changes.
- `resources/views/layouts/marketing.blade.php`: nav links swap order, footer replaced with a new multi-column layout + CSS, existing `--footer` styles reworked.
- `public/js/vx-avatar-loader.js`: extended (not forked) with a `frame` option (`'upper'` default, unchanged; new `'full'`) and an `interactive` option (hand-rolled horizontal drag rotation). Existing callers (login page, avatar-demo section) pass no new options and keep today's behavior byte-for-byte.
- `.gitignore`: add `!public/models/avatar.glb` exception beneath the existing `*.glb` line; commit the 4MB file.

## Components

**`divisions.blade.php`**
- Card order: VoxSign Accessibility, then VoxSign Institutions.
- Each card keeps its `Learn more ↓` ghost-button anchor (`#accessibility` / `#pearledu`) and gains a second `vx-btn` (solid) reading `Get Started` linking to `https://accessibility.{{ config('tenancy.base_domain') }}` and `https://pearledu.{{ config('tenancy.base_domain') }}` respectively — same domain-config pattern the footer's existing PearlEdu link already uses.

**`hero.blade.php`**
- Lead paragraph reorders to name VoxSign Accessibility before VoxSign Institutions.
- New markup: `<div id="vx-hero-avatar-3d" class="vx-hero-avatar-3d"></div>` placed within the hero `.vx-wrap`, plus a `<p class="vx-sr-only">` description (matching the accessibility pattern already used for the avatar-demo section).
- New `<style>` block: `.vx-hero-avatar-3d{width:340px;height:560px}` desktop, with a `@media(max-width:860px)` rule shrinking it and stacking it below the text (not hiding it — unlike the login page's stage panel, the hero has no side-by-side layout to collapse away from).
- New `<script type="module">` (mirroring the pattern already used in `avatar-demo.blade.php` and `login.blade.php`) importing `vx-avatar-loader.js` and calling `mountAvatar` with `frame: 'full'`, `mode: 'idle'`, `colorVars: ['--sign','--voice']`, and `interactive: !window.matchMedia('(max-width:860px)').matches`.

**`public/js/vx-avatar-loader.js`**
- New config keys, both optional and backward-compatible:
  - `frame: 'upper' | 'full'` (default `'upper'`). Only affects the camera position / `root.position.set` values used in `onModelLoaded` — `'full'` pulls the camera back and lowers its target so the whole rig (head to feet) is in view, `'upper'` keeps today's exact values.
  - `interactive: boolean` (default `false`). When true, adds `pointerdown`/`pointermove`/`pointerup` listeners on the renderer's canvas: while dragging, horizontal pointer delta is converted to a Y-axis rotation applied to `root` (the loaded `gltf.scene`), overriding (but not replacing) whatever the active `idle`/`cycle` tick function is doing to bone rotations that frame — the two are orthogonal (root-level rotation vs. bone-level pose). On `pointerup`, rotation stops changing and the existing idle sway continues from the current facing angle. No inertia/momentum — simple 1:1 drag, no `OrbitControls` dependency.
- Reduced-motion: `interactive` is still enabled under `prefers-reduced-motion: reduce` (drag is user-initiated, not automatic motion) but the ambient idle sway that normally plays alongside it is skipped, matching the existing reduced-motion handling for `mode: 'idle'`.

**Footer (`marketing.blade.php`)**
- Structure: brand row (inline SVG logo mark reused from the nav + one-line tagline "Technology built to include everyone."), then a 3-column grid — **Products** (VoxSign Institutions, VoxSign Accessibility, both linking to their subdomains same as the division cards), **Company** (Team → `#team`, Contact → `#contact`), **Contact** (phone, email — same values as today, no new data). Bottom bar: `© {{ date('Y') }} VoxSign, Uganda` only.
- CSS: dark `--ink` background retained from today's footer; grid collapses to a single stacked column under 640px (same breakpoint the current footer already uses).

## Data flow

No new data flows or backend changes. `config('tenancy.base_domain')` (already used for the existing footer PearlEdu link) is reused for both new subdomain CTAs and the footer's Products column.

## Error handling

- Avatar: same fallback path as today — WebGL unavailable or `avatar.glb` failing to load shows the existing fallback SVG hand illustration inside the hero's mount container (unchanged `showFallback()` logic, just a bigger container).
- Drag rotation only binds listeners after a successful model load; no dead event listeners if the model fails and the fallback renders instead.
- `prefers-reduced-motion: reduce` continues to be respected exactly as it is today for the idle/cycle tick loops.

## Testing

- Update `tests/Feature/LandingPageTest.php` for renamed/reordered copy:
  - `test_hero_renders_platform_level_copy`: replace `assertSee('PearlEdu')` with `assertSee('VoxSign Institutions')`; assert Accessibility mention precedes Institutions mention (`strpos` ordering check, following the existing `test_partners_section_appears_directly_after_hero` pattern).
  - `test_two_divisions_section_introduces_pearledu_and_accessibility`: rename/update to assert `href="#accessibility"` now appears before `href="#pearledu"` in the response body, and both `Get Started` CTA hrefs (`https://accessibility.` / `https://pearledu.`) are present.
  - `test_pearledu_section_describes_institution_features`: keep `id="pearledu"` assertion (subdomain-linked anchor unchanged), update any literal "PearlEdu" heading-text assertion to "VoxSign Institutions".
  - New test: footer renders the three-column structure (`assertSee` on distinguishing class hooks) and does not contain any `href="#privacy"` / `Privacy Policy` / `Terms` text.
  - New test: hero response includes `id="vx-hero-avatar-3d"`.
- No test coverage is feasible in PHPUnit for actual WebGL drag behavior (browser-only, canvas-rendered) — that part is verified manually via `/verify` (drag left/right on desktop viewport, confirm avatar visibly rotates and resumes idle sway on release; confirm full-length framing shows feet-to-head; confirm mobile viewport shows a smaller static-idle avatar with no drag response).
- `.php84/php.exe artisan view:cache` then `view:clear` as the Blade-syntax smoke check, per established project convention.
- Manual check: after the `.gitignore` fix, confirm `git status` no longer shows `avatar.glb` as untracked/ignored, and `git show HEAD:public/models/avatar.glb | wc -c` (post-commit) returns the expected file size.
