# VoxSign Landing Page v5 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reposition VoxSign Accessibility ahead of VoxSign Institutions (formerly "PearlEdu") across the voxsign.co.ug marketing site, add "Get Started" CTAs linking out to each division's subdomain, rebuild the footer as a professional multi-column layout, and add a full-length, drag-to-rotate 3D avatar to the hero — plus fix the `.gitignore` bug silently dropping `avatar.glb` from every push.

**Architecture:** Content/copy edits land first (cheap to review, no runtime risk), then the footer rebuild (isolated to `marketing.blade.php`), then the avatar loader gets two new backward-compatible options (`frame`, `interactive`) added to the existing shared module, and finally the hero gets wired up to use them — which requires consolidating the page's Three.js import map from a per-partial declaration into the shared layout `<head>`, since a document can only have one `<script type="importmap">`.

**Tech Stack:** Laravel Blade views, vanilla ES modules (no bundler for the Three.js loader — `<script type="module">` + import map), PHPUnit feature tests.

**Amendment (implemented 2026-07-03):** All tasks below executed with these deviations from the original spec, per direct follow-up instructions:
- Avatar model swapped to a new file (`t.1.glb`, supplied by the user) instead of the original `avatar.glb`; the old file was never committed so this was a plain overwrite.
- Task 4's hardcoded `FRAME_PRESETS` camera/scale/offset numbers were replaced with a real bounding-box auto-fit (`fitCameraToModel` in `vx-avatar-loader.js`) so framing is correct regardless of which GLB is loaded — the original numbers were tuned for the old model and would have mis-framed the new one.
- Task 5's hero layout is an absolutely-positioned overlay (`.vx-hero-avatar-wrap`, right-aligned, full section height) instead of a flex row, and `.vx-hero` changed from `overflow:hidden` to `overflow-x:hidden;overflow-y:visible` — both to guarantee the full-length avatar is never clipped.
- Headings/buttons/eyebrows/labels (`--display` token) switched from Clash Display to Google Sans, matching a separate font-change request; Satoshi remains the body font.
- All `.vx-grid` card sections (divisions, features, team, accessibility, pearledu) changed to a single vertical column, centered, `max-width:640px` — not part of the original spec.
- Contact form given `margin:0 auto` to center it — not part of the original spec.

## Global Constraints

- Base domain in the test environment is `voxsign.test` (set by `phpunit.xml`'s `<env name="TENANCY_BASE_DOMAIN" value="voxsign.test"/>`), NOT `voxsign.co.ug` — any test assertion on a `config('tenancy.base_domain')`-derived URL must use `voxsign.test`.
- No JS test runner exists in this repo (`package.json` has no test script, no vitest/jest) — JS logic changes (the avatar loader) are verified via PHPUnit assertions on the rendered Blade markup that wires them up, plus manual browser verification. This matches the existing project convention (see `tests/Feature/LoginPageTest.php`, which only asserts markup, never WebGL behavior).
- `prefers-reduced-motion: reduce` must continue to be respected exactly as today wherever animation logic is touched.
- Blade syntax smoke check after every task that touches a `.blade.php` file: `.php84/php.exe artisan view:cache` then `.php84/php.exe artisan view:clear`.
- Run the full suite with `.php84/php.exe artisan test` before the final commit of each task.

---

### Task 1: Fix `.gitignore` GLB exclusion and commit the avatar model

**Files:**
- Modify: `.gitignore:28`
- Commit: `public/models/avatar.glb` (already present on disk, 4MB, currently untracked because of the blanket `*.glb` rule)

**Interfaces:**
- Produces: `public/models/avatar.glb` becomes a tracked, pushed file — every later task that relies on the 3D avatar rendering (Tasks 4–5, and the existing login page) depends on this being present in the repo for fresh clones/deploys to work at all.

- [x] **Step 1: Confirm the current ignore/tracking state**

Run: `git check-ignore -v public/models/avatar.glb`
Expected output: `.gitignore:28:*.glb	public/models/avatar.glb` (confirms it's currently ignored)

- [x] **Step 2: Add a targeted exception**

Edit `.gitignore`, locate line 28 (`*.glb`), and add a negation immediately after it:

```gitignore
*.glb
!public/models/avatar.glb
```

- [x] **Step 3: Verify the exception works**

Run: `git check-ignore -v public/models/avatar.glb`
Expected: command exits non-zero / prints nothing (file is no longer ignored). If it still prints a match, the negation line was placed before a broader ignore rule that re-excludes it — check for any `public/models/` or `public/` ignore rules elsewhere in `.gitignore` and add further negations as needed (there are none as of this writing — the fix above is sufficient).

- [x] **Step 4: Stage and commit**

```bash
git add .gitignore public/models/avatar.glb
git commit -m "Track avatar.glb so the 3D avatar model ships to deploys"
```

- [x] **Step 5: Verify the commit contains the binary**

Run: `git show --stat HEAD`
Expected: output lists `public/models/avatar.glb` with a size delta (insertion), and `git cat-file -s HEAD:public/models/avatar.glb` returns approximately `3999744` (matches the on-disk size).

---

### Task 2: Rename "PearlEdu" → "VoxSign Institutions" and reorder Accessibility-first

**Files:**
- Modify: `resources/views/landing/partials/hero.blade.php`
- Modify: `resources/views/landing/partials/divisions.blade.php`
- Modify: `resources/views/landing/partials/pearledu.blade.php:4`
- Modify: `resources/views/layouts/marketing.blade.php:134-139` (nav links)
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `config('tenancy.base_domain')` (already used elsewhere in `marketing.blade.php`'s footer).
- Produces: no new interfaces — this task is copy/markup only. Task 5 will further modify `hero.blade.php`'s wrapper markup on top of this task's copy changes, so land this task first.

- [x] **Step 1: Update the hero copy and anchor** — replace all of `hero.blade.php` with:

```blade
<section class="vx-section vx-hero" style="padding-top:56px">
  <div class="vx-hero-glow" aria-hidden="true"></div>
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">AI &middot; Inclusion Technology</div>
    <h1 class="vx-h1">Technology built to <span class="vx-flow">include everyone.</span></h1>
    <p class="vx-lead">
      VoxSign is two things working toward one goal: <strong>VoxSign Accessibility</strong>, AI
      tools that give Deaf, hard-of-hearing, and speech-impaired people a voice in the room, and
      <strong>VoxSign Institutions</strong>, a school management platform for institutions.
    </p>
    <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
      <a href="#contact" class="vx-btn">Talk to us</a>
      <a href="#accessibility" class="vx-btn-ghost">See both divisions ↓</a>
    </p>
  </div>
</section>
```

(The "See both divisions" anchor now points at `#accessibility` instead of `#pearledu`, consistent with Accessibility now leading.)

- [x] **Step 2: Reorder and rename the divisions cards** — replace all of `divisions.blade.php` with:

```blade
<section class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">What VoxSign is</div>
    <h2 class="vx-h2">Two divisions, one mission.</h2>
    <p class="vx-lead vx-sec-head">Every VoxSign product exists to close a gap someone else's technology left open.</p>
    <div class="vx-grid" style="grid-template-columns:repeat(2,1fr)">
      <div class="vx-card">
        <h3>VoxSign Accessibility</h3>
        <p>AI tools for Deaf, hard-of-hearing, and speech-impaired users — Ugandan Sign Language translation and assistive speech recognition.</p>
        <p style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
          <a href="#accessibility" class="vx-btn-ghost">Learn more ↓</a>
          <a href="https://accessibility.{{ config('tenancy.base_domain') }}" class="vx-btn">Get Started</a>
        </p>
      </div>
      <div class="vx-card">
        <h3>VoxSign Institutions</h3>
        <p>A school management platform for institutions — attendance, grading, fees, and communication in one place.</p>
        <p style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
          <a href="#pearledu" class="vx-btn-ghost">Learn more ↓</a>
          <a href="https://pearledu.{{ config('tenancy.base_domain') }}" class="vx-btn">Get Started</a>
        </p>
      </div>
    </div>
  </div>
</section>
```

- [x] **Step 3: Rename the institutions detail section heading** — in `pearledu.blade.php`, change line 4:

```blade
<h2 class="vx-h2">PearlEdu — school management, without the spreadsheets.</h2>
```

to:

```blade
<h2 class="vx-h2">VoxSign Institutions — school management, without the spreadsheets.</h2>
```

(Leave the section's `id="pearledu"`, the eyebrow "For institutions", and the feature cards below it unchanged — only the heading text changes.)

- [x] **Step 4: Reorder the nav links** — in `marketing.blade.php`, change lines 135-136 from:

```blade
      <a href="#pearledu">Institutions</a>
      <a href="#accessibility">Accessibility</a>
```

to:

```blade
      <a href="#accessibility">Accessibility</a>
      <a href="#pearledu">Institutions</a>
```

- [x] **Step 5: Update existing tests for the new copy** — in `tests/Feature/LandingPageTest.php`, replace `test_hero_renders_platform_level_copy`:

```php
    public function test_hero_renders_platform_level_copy(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Technology built to include everyone.', false);
        $response->assertSee('VoxSign Institutions', false);
        $response->assertSee('VoxSign Accessibility', false);
        $response->assertSee('Talk to us');
        $response->assertDontSee('Communication gaps between hearing instructors', false);
        $response->assertDontSee('PearlEdu', false);

        $content = $response->getContent();
        $accessibilityPos = strpos($content, 'VoxSign Accessibility');
        $institutionsPos = strpos($content, 'VoxSign Institutions');
        $this->assertNotFalse($accessibilityPos);
        $this->assertNotFalse($institutionsPos);
        $this->assertLessThan($institutionsPos, $accessibilityPos);
    }
```

- [x] **Step 6: Replace the divisions-order test** — replace `test_two_divisions_section_introduces_pearledu_and_accessibility` with:

```php
    public function test_two_divisions_section_introduces_accessibility_and_institutions(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Two divisions, one mission', false);
        $response->assertSee('school management platform', false);
        $response->assertSee('href="#pearledu"', false);
        $response->assertSee('href="#accessibility"', false);
        $response->assertSee('href="https://accessibility.voxsign.test"', false);
        $response->assertSee('href="https://pearledu.voxsign.test"', false);
        $response->assertSee('Get Started', false);

        $content = $response->getContent();
        $sectionPos = strpos($content, 'Two divisions, one mission');
        $this->assertNotFalse($sectionPos);
        $accessibilityCardPos = strpos($content, 'href="#accessibility"', $sectionPos);
        $institutionsCardPos = strpos($content, 'href="#pearledu"', $sectionPos);
        $this->assertNotFalse($accessibilityCardPos);
        $this->assertNotFalse($institutionsCardPos);
        $this->assertLessThan($institutionsCardPos, $accessibilityCardPos);
    }
```

- [x] **Step 7: Update the institutions-section test** — in `test_pearledu_section_describes_institution_features`, add a heading assertion right after the `assertStatus(200)` line:

```php
        $response->assertSee('VoxSign Institutions — school management, without the spreadsheets.', false);
```

- [x] **Step 8: Run the updated tests**

Run: `.php84/php.exe artisan test --filter=LandingPageTest`
Expected: all tests in the file PASS (the three modified tests plus all untouched ones, which should be unaffected).

- [x] **Step 9: Blade syntax smoke check**

Run: `.php84/php.exe artisan view:cache && .php84/php.exe artisan view:clear`
Expected: both commands complete with no Blade compilation errors.

- [x] **Step 10: Commit**

```bash
git add resources/views/landing/partials/hero.blade.php resources/views/landing/partials/divisions.blade.php resources/views/landing/partials/pearledu.blade.php resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Reorder Accessibility-first and rename PearlEdu to VoxSign Institutions"
```

---

### Task 3: Rebuild the footer as a professional multi-column layout

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php:109-111` (CSS)
- Modify: `resources/views/layouts/marketing.blade.php:147-151` (markup)
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `config('tenancy.base_domain')`, the same nav logo SVG markup already present at lines 121-132 of `marketing.blade.php` (reused, not duplicated as a partial — this is a single-layout file, YAGNI applies).

- [x] **Step 1: Replace the footer CSS** — in `marketing.blade.php`, replace lines 109-111:

```css
  .vx-footer{background:var(--ink);color:#c7cdda;padding:32px 24px;font-size:13px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px}
  .vx-footer a{color:#c7cdda}.vx-footer a:hover{color:#fff}
  @media(max-width:640px){.vx-footer{flex-direction:column}}
```

with:

```css
  .vx-footer{background:var(--ink);color:#c7cdda;padding:56px 24px 28px}
  .vx-footer-inner{max-width:1120px;margin:0 auto}
  .vx-footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:8px}
  .vx-footer-brand svg{height:28px;width:auto}
  .vx-footer-tagline{color:#aeb4c2;font-size:14px;margin:0 0 36px}
  .vx-footer-cols{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding-bottom:28px;border-bottom:1px solid rgba(255,255,255,.12)}
  .vx-footer-col h4{font-family:var(--display);font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin:0 0 14px}
  .vx-footer-col a,.vx-footer-col span{display:block;color:#c7cdda;font-size:14px;margin-bottom:10px}
  .vx-footer-col a:hover{color:#fff}
  .vx-footer-bottom{padding-top:20px;font-size:13px;color:#8b93a5}
  @media(max-width:640px){.vx-footer-cols{grid-template-columns:1fr;gap:28px}}
```

- [x] **Step 2: Replace the footer markup** — replace lines 147-151:

```blade
  <div class="vx-footer">
    <span>&copy; {{ date('Y') }} VoxSign, Uganda</span>
    <span>+256 770 680769 &middot; voxsign3@gmail.com</span>
    <a href="https://pearledu.{{ config('tenancy.base_domain') }}">PearlEdu — school management →</a>
  </div>
```

with:

```blade
  <div class="vx-footer">
    <div class="vx-footer-inner">
      <div class="vx-footer-brand">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 32" fill="none" role="img" aria-label="VoxSign">
          <defs>
            <linearGradient id="vxFooterLogoGrad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop offset="0" stop-color="#FF6A3D"/>
              <stop offset="1" stop-color="#12B3A6"/>
            </linearGradient>
          </defs>
          <rect x="0" y="0" width="32" height="32" rx="9" fill="url(#vxFooterLogoGrad)"/>
          <path d="M7 20c2.5-7 5-10.5 9-10.5s6.5 3.5 9 10.5" stroke="#FBFAF7" stroke-width="2.4" stroke-linecap="round" fill="none"/>
          <path d="M12 22.5v-6.2c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v4.2M15.6 20.5v-5.4c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v5.4M19.2 21v-4.6c0-1 .8-1.8 1.7-1.8s1.7.8 1.7 1.8v5.6c0 2.5-1.9 4.5-4.5 4.5h-1.6c-1.5 0-2.9-.7-3.8-1.9l-2.6-3.4a1.5 1.5 0 0 1 2.3-1.9l1.5 1.6" stroke="#FBFAF7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <text x="40" y="22" font-family="'Clash Display',system-ui,sans-serif" font-weight="600" font-size="18" fill="#fff">VoxSign</text>
        </svg>
      </div>
      <p class="vx-footer-tagline">Technology built to include everyone.</p>
      <div class="vx-footer-cols">
        <div class="vx-footer-col">
          <h4>Products</h4>
          <a href="https://accessibility.{{ config('tenancy.base_domain') }}">VoxSign Accessibility</a>
          <a href="https://pearledu.{{ config('tenancy.base_domain') }}">VoxSign Institutions</a>
        </div>
        <div class="vx-footer-col">
          <h4>Company</h4>
          <a href="#team">Team</a>
          <a href="#contact">Contact</a>
        </div>
        <div class="vx-footer-col">
          <h4>Contact</h4>
          <span>+256 770 680769</span>
          <span>voxsign3@gmail.com</span>
        </div>
      </div>
      <div class="vx-footer-bottom">&copy; {{ date('Y') }} VoxSign, Uganda</div>
    </div>
  </div>
```

- [x] **Step 3: Write the new footer test** — add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_footer_renders_professional_multi_column_layout(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('vx-footer-cols', false);
        $response->assertSee('vx-footer-brand', false);
        $response->assertSee('Technology built to include everyone.', false);
        $response->assertSee('>Products<', false);
        $response->assertSee('>Company<', false);
        $response->assertSee('>Contact<', false);
        $response->assertSee('href="https://accessibility.voxsign.test"', false);
        $response->assertSee('href="https://pearledu.voxsign.test"', false);
        $response->assertDontSee('Privacy Policy', false);
        $response->assertDontSee('Terms', false);
        $response->assertDontSee('href="#privacy"', false);
    }
```

- [x] **Step 4: Run the tests**

Run: `.php84/php.exe artisan test --filter=LandingPageTest`
Expected: all PASS, including the new `test_footer_renders_professional_multi_column_layout`.

- [x] **Step 5: Blade syntax smoke check**

Run: `.php84/php.exe artisan view:cache && .php84/php.exe artisan view:clear`
Expected: no errors.

- [x] **Step 6: Commit**

```bash
git add resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Rebuild footer as a professional multi-column layout"
```

---

### Task 4: Add `frame` and `interactive` options to the shared avatar loader

**Files:**
- Modify: `public/js/vx-avatar-loader.js`

**Interfaces:**
- Consumes: nothing new from other tasks.
- Produces: `mountAvatar(config)` now accepts two new optional keys consumed by Task 5:
  - `config.frame: 'upper' | 'full'` (default `'upper'`) — `'upper'` preserves today's exact camera/scale/offset values (used unchanged by the login page and the "See it in motion" demo section); `'full'` frames the whole body.
  - `config.interactive: boolean` (default `false`) — when `true`, enables horizontal pointer-drag rotation of the loaded model's root object.
- All existing callers (`avatar-demo.blade.php`, `login.blade.php`) pass neither option and are byte-for-byte unaffected.

- [x] **Step 1: Add frame presets and read the two new config options** — in `public/js/vx-avatar-loader.js`, add this constant above `export function mountAvatar(config) {` (i.e., as a new top-level declaration before line 1's function):

```js
var FRAME_PRESETS = {
  upper: { camera: [0, 1.4, 3.4], lookAt: [0, 1.1, 0], scale: 1.55, offset: [0, -1.15, 0] },
  full: { camera: [0, 1.0, 5.2], lookAt: [0, 0.85, 0], scale: 1.05, offset: [0, -1.0, 0] }
};

export function mountAvatar(config) {
```

Then, inside `mountAvatar`, immediately after the existing line `var mode = config.mode || 'cycle';` (line 5), add:

```js
  var frame = config.frame || 'upper';
  var interactive = !!config.interactive;
  var preset = FRAME_PRESETS[frame] || FRAME_PRESETS.upper;
```

- [x] **Step 2: Use the preset for camera setup** — in `initScene`, replace:

```js
    camera.position.set(0, 1.4, 3.4);
    camera.lookAt(0, 1.1, 0);
```

with:

```js
    camera.position.set(preset.camera[0], preset.camera[1], preset.camera[2]);
    camera.lookAt(preset.lookAt[0], preset.lookAt[1], preset.lookAt[2]);
```

- [x] **Step 3: Use the preset for model scale/offset** — in `onModelLoaded`, replace:

```js
    var root = gltf.scene;
    root.scale.setScalar(1.55);
    root.position.set(0, -1.15, 0);
```

with:

```js
    var root = gltf.scene;
    root.scale.setScalar(preset.scale);
    root.position.set(preset.offset[0], preset.offset[1], preset.offset[2]);
```

- [x] **Step 4: Add drag-to-rotate** — in `onModelLoaded`, immediately before the existing `if (mode === 'idle') { ... } else { ... }` block (around line 139), add:

```js
    if (interactive) { enableDragRotate(renderer, root); }
```

Then add this new function anywhere at the same nesting level as `runIdle`/`runCycle` (e.g., directly above `function runIdle`):

```js
  function enableDragRotate(renderer, root) {
    var dragging = false;
    var lastX = 0;
    var baseY = root.rotation.y;
    var el = renderer.domElement;
    el.style.touchAction = 'pan-y';
    el.style.cursor = 'grab';

    function pointerDown(e) {
      dragging = true;
      lastX = e.clientX;
      baseY = root.rotation.y;
      el.style.cursor = 'grabbing';
      el.setPointerCapture(e.pointerId);
    }
    function pointerMove(e) {
      if (!dragging) return;
      var deltaX = e.clientX - lastX;
      root.rotation.y = baseY + deltaX * 0.012;
    }
    function pointerUp() {
      dragging = false;
      el.style.cursor = 'grab';
    }

    el.addEventListener('pointerdown', pointerDown);
    el.addEventListener('pointermove', pointerMove);
    el.addEventListener('pointerup', pointerUp);
    el.addEventListener('pointerleave', pointerUp);
  }
```

(Root-level `rotation.y` spin is orthogonal to the bone-local quaternion animation `runIdle`/`runCycle` apply, so dragging and idle sway never fight each other — on `pointerup`, idle sway continues from whatever facing angle the drag left it at.)

- [x] **Step 5: Manual verification** (no automated JS test exists in this repo — see Global Constraints)

This task has no directly-runnable verification until Task 5 wires a consumer; defer browser verification to Task 5's Step 6, which exercises both new options end-to-end.

- [x] **Step 6: Commit**

```bash
git add public/js/vx-avatar-loader.js
git commit -m "Add full-body framing and drag-to-rotate options to the shared avatar loader"
```

---

### Task 5: Consolidate the Three.js import map and add the full-length interactive avatar to the hero

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php` (add shared import map to `<head>`)
- Modify: `resources/views/landing/partials/avatar-demo.blade.php:29-36` (remove its now-duplicate import map)
- Modify: `resources/views/landing/partials/hero.blade.php` (from Task 2's version — wrap in a flex layout, add the avatar mount)
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `mountAvatar({ frame, interactive, ... })` from Task 4.
- Produces: nothing consumed by later tasks — this is the last task in this plan.

**Why the import map must move:** a browser document can only have one `<script type="importmap">`. Today only `avatar-demo.blade.php` declares one, which works because it's the page's only Three.js consumer. Adding a second avatar mount in the hero — appearing earlier in the DOM than the demo section — means either two import maps (broken: the second one is rejected by the browser) or one shared map declared once, early, in `<head>`. This task does the latter.

- [x] **Step 1: Add the shared import map to the layout head** — in `marketing.blade.php`, insert immediately before the closing `</style>` tag's following `</head>` (i.e., right after the big `<style>...</style>` block that ends around line 116, before `</head>` at line 117):

```blade
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>
```

- [x] **Step 2: Remove the now-duplicate import map from the demo section** — in `avatar-demo.blade.php`, delete lines 29-36:

```blade
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>
```

(Leave its `<script type="module"> import { mountAvatar } from '/js/vx-avatar-loader.js'; ... </script>` block immediately below that — untouched, it still works, now relying on the layout's shared import map.)

- [x] **Step 3: Wrap the hero in a flex layout and add the avatar mount** — replace all of `hero.blade.php` (as last written in Task 2) with:

```blade
<section class="vx-section vx-hero" style="padding-top:56px">
  <div class="vx-hero-glow" aria-hidden="true"></div>
  <div class="vx-wrap vx-reveal vx-hero-flex">
    <div class="vx-hero-copy">
      <div class="vx-eyebrow">AI &middot; Inclusion Technology</div>
      <h1 class="vx-h1">Technology built to <span class="vx-flow">include everyone.</span></h1>
      <p class="vx-lead">
        VoxSign is two things working toward one goal: <strong>VoxSign Accessibility</strong>, AI
        tools that give Deaf, hard-of-hearing, and speech-impaired people a voice in the room, and
        <strong>VoxSign Institutions</strong>, a school management platform for institutions.
      </p>
      <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="#contact" class="vx-btn">Talk to us</a>
        <a href="#accessibility" class="vx-btn-ghost">See both divisions ↓</a>
      </p>
    </div>
    <div class="vx-hero-avatar-wrap">
      <div id="vx-hero-avatar-3d" class="vx-hero-avatar-3d"></div>
      <p class="vx-sr-only">Decorative 3D figure of a person, shown for visual interest; drag horizontally to rotate it.</p>
    </div>
  </div>
</section>
<style>
  .vx-hero-flex{display:flex;align-items:center;gap:32px;flex-wrap:wrap}
  .vx-hero-copy{flex:1 1 420px;min-width:280px}
  .vx-hero-avatar-wrap{flex:0 0 340px}
  .vx-hero-avatar-3d{width:340px;height:560px}
  .vx-hero-avatar-3d canvas{display:block;margin:0 auto;touch-action:pan-y}
  @media(max-width:860px){
    .vx-hero-flex{flex-direction:column}
    .vx-hero-avatar-wrap{flex:none;order:2}
    .vx-hero-avatar-3d{width:220px;height:360px;margin:0 auto}
  }
</style>
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js';
  mountAvatar({
    container: 'vx-hero-avatar-3d',
    mode: 'idle',
    frame: 'full',
    interactive: !window.matchMedia('(max-width:860px)').matches,
    width: 340,
    height: 560,
    colorVars: ['--sign', '--voice'],
    colorFallbacks: ['#12B3A6', '#FF6A3D']
  });
</script>
```

- [x] **Step 4: Add tests for the new hero markup** — add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_hero_renders_full_length_interactive_avatar(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('id="vx-hero-avatar-3d"', false);
        $response->assertSee("frame: 'full'", false);
        $response->assertSee('interactive:', false);
        $response->assertSee('@media(max-width:860px)', false);
    }

    public function test_shared_importmap_declared_once_in_head(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'type="importmap"'));
    }
```

- [x] **Step 5: Run the full landing test suite**

Run: `.php84/php.exe artisan test --filter=LandingPageTest`
Expected: all tests PASS, including the two new ones and the existing `test_avatar_demo_renders_3d_container_and_preserved_copy` (which must still pass unchanged, proving the demo section survived the import-map move).

- [x] **Step 6: Manual browser verification**

Run: `.php84/php.exe artisan serve` (or the project's existing local dev-server command), then in a browser at the landing page:
- Desktop viewport (>860px): confirm the hero shows the full-length avatar (head to feet in frame) beside the text; click-drag horizontally on it and confirm it rotates; release and confirm it eases back into idle sway from the released angle.
- Scroll down to "See it in motion": confirm the existing small captioned avatar-demo still cycles through its two phrases exactly as before (proves the import-map consolidation didn't break it).
- Resize below 860px: confirm the avatar shrinks and stacks below the hero text, and dragging it no longer rotates it (idle sway only).
- Toggle OS-level "reduce motion" and reload: confirm both avatars render a static pose with no animation, matching prior reduced-motion behavior.

- [x] **Step 7: Full project test suite**

Run: `.php84/php.exe artisan test`
Expected: all tests across the whole suite PASS (no regressions in unrelated areas, e.g. `LoginPageTest`, which shares `vx-avatar-loader.js` but uses its own separate `layouts.auth` layout/import map, untouched by this task).

- [x] **Step 8: Blade syntax smoke check**

Run: `.php84/php.exe artisan view:cache && .php84/php.exe artisan view:clear`
Expected: no errors.

- [x] **Step 9: Commit**

```bash
git add resources/views/layouts/marketing.blade.php resources/views/landing/partials/avatar-demo.blade.php resources/views/landing/partials/hero.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add full-length interactive hero avatar; consolidate shared Three.js importmap"
```
