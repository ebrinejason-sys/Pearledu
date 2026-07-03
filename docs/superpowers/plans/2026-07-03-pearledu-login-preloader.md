# PearlEdu Login Preloader Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give `pearledu.voxsign.co.ug`'s `/login` page a one-time-per-session preloader where the PearlEdu mark — a circle built from horizontal copper lines — draws itself in, line by line, starting from the middle and cascading outward, then fades to reveal the login page. Convert the raster `logo.png` into a recolorable SVG and use it everywhere that mark appears.

**Architecture:** A new procedurally-generated `logo.svg` (chord-geometry reconstruction of the circle-of-lines mark, using `currentColor` so it can be recolored per theme) replaces the raster logo in three static spots. A small standalone JS module (`vx-preloader.js`, no Three.js/bundler involved — this is 2D SVG, unrelated to the 3D avatar work) drives a staggered stroke-dashoffset reveal on an inlined copy of the same line geometry, gated by `sessionStorage` so it only plays once per browser session.

**Tech Stack:** Laravel Blade views, vanilla ES modules, inline SVG, PHPUnit feature tests.

## Global Constraints

- No JS test runner exists in this repo — the preloader's stagger timing and reduced-motion branch are verified manually in a browser; PHPUnit only asserts the markup that wires it up (matches the pattern already used for `vx-avatar-loader.js`).
- `prefers-reduced-motion: reduce` must skip the staggered animation entirely and go straight to the fully-formed logo.
- A broken/failed preloader must never trap a user off their login form — any failure path removes the overlay immediately.
- Blade syntax smoke check after every task touching a `.blade.php` file: `.php84/php.exe artisan view:cache` then `.php84/php.exe artisan view:clear`.

---

### Task 1: Create the procedurally-reconstructed SVG logo

**Files:**
- Create: `public/images/brand/logo.svg`

**Interfaces:**
- Produces: a static SVG asset with a `<g id="vx-logo-lines">` containing 21 `<line>` elements, each with a `data-index` attribute from `-10` (top) to `10` (bottom), `0` being the middle/longest line. Task 4 duplicates this exact line geometry inline in `login.blade.php` so the preloader script can animate it (a referenced `<img src="logo.svg">` cannot have its internals targeted by CSS/JS, so the animated copy must be inlined markup, not this file — this file is the static, non-animated version used everywhere else).

**Note on fidelity:** this is a procedural reconstruction (circle rendered as horizontal chords: at vertical offset `dy` from center, half-width = `sqrt(r² − dy²)`), not a pixel trace of the source PNG — no image-vectorization tool (ImageMagick/Python+PIL/potrace) is available in this environment. Per your earlier confirmation, proceeding with the reconstruction rather than a hand-vectorized file.

- [x] **Step 1: Create the file** with this exact content:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" role="img" aria-label="PearlEdu" style="color:#B5652F">
  <g id="vx-logo-lines" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
    <line data-index="-10" x1="35.97" y1="6.19" x2="64.03" y2="6.19"/>
    <line data-index="-9" x1="26.31" y1="10.57" x2="73.69" y2="10.57"/>
    <line data-index="-8" x1="20.21" y1="14.95" x2="79.79" y2="14.95"/>
    <line data-index="-7" x1="15.71" y1="19.33" x2="84.29" y2="19.33"/>
    <line data-index="-6" x1="12.25" y1="23.71" x2="87.75" y2="23.71"/>
    <line data-index="-5" x1="9.55" y1="28.1" x2="90.45" y2="28.1"/>
    <line data-index="-4" x1="7.47" y1="32.48" x2="92.53" y2="32.48"/>
    <line data-index="-3" x1="5.92" y1="36.86" x2="94.08" y2="36.86"/>
    <line data-index="-2" x1="4.84" y1="41.24" x2="95.16" y2="41.24"/>
    <line data-index="-1" x1="4.21" y1="45.62" x2="95.79" y2="45.62"/>
    <line data-index="0" x1="4" y1="50" x2="96" y2="50"/>
    <line data-index="1" x1="4.21" y1="54.38" x2="95.79" y2="54.38"/>
    <line data-index="2" x1="4.84" y1="58.76" x2="95.16" y2="58.76"/>
    <line data-index="3" x1="5.92" y1="63.14" x2="94.08" y2="63.14"/>
    <line data-index="4" x1="7.47" y1="67.52" x2="92.53" y2="67.52"/>
    <line data-index="5" x1="9.55" y1="71.9" x2="90.45" y2="71.9"/>
    <line data-index="6" x1="12.25" y1="76.29" x2="87.75" y2="76.29"/>
    <line data-index="7" x1="15.71" y1="80.67" x2="84.29" y2="80.67"/>
    <line data-index="8" x1="20.21" y1="85.05" x2="79.79" y2="85.05"/>
    <line data-index="9" x1="26.31" y1="89.43" x2="73.69" y2="89.43"/>
    <line data-index="10" x1="35.97" y1="93.81" x2="64.03" y2="93.81"/>
  </g>
</svg>
```

- [x] **Step 2: Verify it's valid, well-formed SVG**

Run: `.php84/php.exe -r "var_dump(simplexml_load_file('public/images/brand/logo.svg') !== false);"`
Expected: `bool(true)` (confirms well-formed XML — PHP's `simplexml_load_file` fails on malformed markup).

- [x] **Step 3: Visual sanity check**

Open `public/images/brand/logo.svg` directly in a browser tab (`file://` path or via any static file server). Expected: a copper-colored circle made of horizontal lines, matching the overall silhouette of `public/images/brand/logo.png` (longest line in the middle, tapering top and bottom).

- [x] **Step 4: Commit**

```bash
git add public/images/brand/logo.svg
git commit -m "Add procedurally-reconstructed SVG version of the PearlEdu logo"
```

---

### Task 2: Swap the raster logo for the SVG in its three static spots

**Files:**
- Modify: `resources/views/layouts/partials/brand.blade.php:7`
- Modify: `resources/views/auth/login.blade.php:7` (the small corner brand mark, not the preloader — that's Task 4)
- Modify: `resources/views/auth/accept-invitation.blade.php:5`

**Interfaces:**
- Consumes: `public/images/brand/logo.svg` from Task 1.

- [x] **Step 1: Update `brand.blade.php`** — change line 7 from:

```blade
  <img src="{{ asset('images/brand/logo.png') }}" alt="" class="brand__mark" width="36" height="36">
```

to:

```blade
  <img src="{{ asset('images/brand/logo.svg') }}" alt="" class="brand__mark" width="36" height="36">
```

- [x] **Step 2: Update `login.blade.php`'s corner brand mark** — change line 7 from:

```blade
        <img src="{{ asset('images/brand/logo.png') }}" alt="" width="40" height="40">
```

to:

```blade
        <img src="{{ asset('images/brand/logo.svg') }}" alt="" width="40" height="40">
```

- [x] **Step 3: Update `accept-invitation.blade.php`** — change line 5 from:

```blade
    <img src="{{ asset('images/brand/logo.png') }}" alt="" class="brand__mark brand__mark--lg" width="56" height="56">
```

to:

```blade
    <img src="{{ asset('images/brand/logo.svg') }}" alt="" class="brand__mark brand__mark--lg" width="56" height="56">
```

- [x] **Step 4: Verify no remaining references to the raster logo in these three files**

Run: `grep -rn "logo.png" resources/views/layouts/partials/brand.blade.php resources/views/auth/login.blade.php resources/views/auth/accept-invitation.blade.php`
Expected: no output (all three occurrences replaced). Note: `public/images/brand/logo.png` itself is intentionally left on disk and in git — only the three Blade references are swapped, in case anything else references the raster file.

- [x] **Step 5: Blade syntax smoke check**

Run: `.php84/php.exe artisan view:cache && .php84/php.exe artisan view:clear`
Expected: no errors.

- [x] **Step 6: Manual verification**

Load `/login` in a browser and confirm the small corner logo renders crisply (SVG, not the old raster). There is no existing automated test covering `brand.blade.php` or `accept-invitation.blade.php` (neither has a feature test today) — Task 4 adds the one piece of automated coverage for the login page's use of the SVG.

- [x] **Step 7: Commit**

```bash
git add resources/views/layouts/partials/brand.blade.php resources/views/auth/login.blade.php resources/views/auth/accept-invitation.blade.php
git commit -m "Use SVG logo instead of raster PNG in brand mark, login, and accept-invitation views"
```

---

### Task 3: Build the preloader animation module

**Files:**
- Create: `public/js/vx-preloader.js`

**Interfaces:**
- Produces: `runPreloader(container, { onDone })` — `container` is the overlay element (its `opacity` is animated for the fade-out); it must contain one or more `<line data-index="N">` elements (matching Task 1's geometry) to animate. `onDone` is called exactly once, after the reveal-and-fade sequence completes (or immediately, on failure/reduced-motion). Task 4 is the sole consumer.

- [x] **Step 1: Create the file**

```js
export function runPreloader(container, opts) {
  var onDone = (opts && opts.onDone) || function () {};
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var STAGGER_MS = 35;
  var REVEAL_MS = 250;
  var FADE_MS = 400;

  var lines = Array.prototype.slice.call(container.querySelectorAll('line[data-index]'));
  if (!lines.length) { onDone(); return; }

  function lineLength(line) {
    var x1 = parseFloat(line.getAttribute('x1'));
    var x2 = parseFloat(line.getAttribute('x2'));
    return Math.abs(x2 - x1);
  }

  lines.forEach(function (line) {
    var len = lineLength(line);
    line.style.strokeDasharray = String(len);
    line.style.strokeDashoffset = String(len);
    line.style.transition = 'none';
  });

  if (reduceMotion) {
    lines.forEach(function (line) { line.style.strokeDashoffset = '0'; });
    fadeOut();
    return;
  }

  var maxAbsIndex = lines.reduce(function (max, line) {
    return Math.max(max, Math.abs(parseInt(line.getAttribute('data-index'), 10)));
  }, 0);

  requestAnimationFrame(function () {
    lines.forEach(function (line) {
      var idx = Math.abs(parseInt(line.getAttribute('data-index'), 10));
      var delay = idx * STAGGER_MS;
      line.style.transition = 'stroke-dashoffset ' + REVEAL_MS + 'ms ease-out ' + delay + 'ms';
      line.style.strokeDashoffset = '0';
    });
  });

  var totalMs = maxAbsIndex * STAGGER_MS + REVEAL_MS;
  setTimeout(fadeOut, totalMs);

  function fadeOut() {
    container.style.transition = 'opacity ' + FADE_MS + 'ms ease';
    container.style.opacity = '0';
    setTimeout(onDone, reduceMotion ? 0 : FADE_MS);
  }
}
```

(`STAGGER_MS * idx` means the middle line, `data-index="0"`, has zero delay and animates first; every other line's delay grows with its distance from the middle, so the reveal cascades outward from the center exactly as specified. Each line's own reveal is a `stroke-dashoffset` transition from its full length to `0`, which — because the dash pattern is exactly one dash-length long — draws the line progressively from its `x1` end to its `x2` end, i.e., left to right.)

- [x] **Step 2: Verify the module has valid syntax**

Run: `node --check public/js/vx-preloader.js`
Expected: no output, exit code 0 (Node's `--check` flag parses without executing; confirms no syntax errors even though this module targets the browser, not Node).

- [x] **Step 3: Commit**

```bash
git add public/js/vx-preloader.js
git commit -m "Add copper-line stagger-reveal preloader animation module"
```

---

### Task 4: Wire the preloader into the login page

**Files:**
- Modify: `resources/views/auth/login.blade.php`
- Modify: `tests/Feature/LoginPageTest.php`

**Interfaces:**
- Consumes: `runPreloader(container, { onDone })` from Task 3, the line geometry from Task 1 (inlined here, not `<img>`-referenced).

- [x] **Step 1: Add the sessionStorage-gate script and overlay markup** — in `login.blade.php`, insert immediately after the opening `<div class="vx-auth-split">` on line 4:

```blade
  <script>
    (function () {
      try {
        if (sessionStorage.getItem('vx-preloader-shown')) {
          document.documentElement.classList.add('vx-preloader-skip');
        }
      } catch (e) {}
    })();
  </script>
  <div id="vx-preloader" class="vx-preloader" aria-hidden="true">
    <svg viewBox="0 0 100 100" role="img" aria-label="PearlEdu">
      <g id="vx-preloader-lines" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
        <line data-index="-10" x1="35.97" y1="6.19" x2="64.03" y2="6.19"/>
        <line data-index="-9" x1="26.31" y1="10.57" x2="73.69" y2="10.57"/>
        <line data-index="-8" x1="20.21" y1="14.95" x2="79.79" y2="14.95"/>
        <line data-index="-7" x1="15.71" y1="19.33" x2="84.29" y2="19.33"/>
        <line data-index="-6" x1="12.25" y1="23.71" x2="87.75" y2="23.71"/>
        <line data-index="-5" x1="9.55" y1="28.1" x2="90.45" y2="28.1"/>
        <line data-index="-4" x1="7.47" y1="32.48" x2="92.53" y2="32.48"/>
        <line data-index="-3" x1="5.92" y1="36.86" x2="94.08" y2="36.86"/>
        <line data-index="-2" x1="4.84" y1="41.24" x2="95.16" y2="41.24"/>
        <line data-index="-1" x1="4.21" y1="45.62" x2="95.79" y2="45.62"/>
        <line data-index="0" x1="4" y1="50" x2="96" y2="50"/>
        <line data-index="1" x1="4.21" y1="54.38" x2="95.79" y2="54.38"/>
        <line data-index="2" x1="4.84" y1="58.76" x2="95.16" y2="58.76"/>
        <line data-index="3" x1="5.92" y1="63.14" x2="94.08" y2="63.14"/>
        <line data-index="4" x1="7.47" y1="67.52" x2="92.53" y2="67.52"/>
        <line data-index="5" x1="9.55" y1="71.9" x2="90.45" y2="71.9"/>
        <line data-index="6" x1="12.25" y1="76.29" x2="87.75" y2="76.29"/>
        <line data-index="7" x1="15.71" y1="80.67" x2="84.29" y2="80.67"/>
        <line data-index="8" x1="20.21" y1="85.05" x2="79.79" y2="85.05"/>
        <line data-index="9" x1="26.31" y1="89.43" x2="73.69" y2="89.43"/>
        <line data-index="10" x1="35.97" y1="93.81" x2="64.03" y2="93.81"/>
      </g>
    </svg>
  </div>
```

- [x] **Step 2: Add the overlay CSS** — in the existing `@section('head')` `<style>` block, add (anywhere inside it, e.g. right after the `.vx-sr-only` rule on line 45):

```css
  .vx-preloader{position:fixed;inset:0;z-index:999;display:flex;align-items:center;justify-content:center;background:var(--bg,#F4F4EF);color:#B5652F}
  .vx-preloader svg{width:160px;height:160px}
  html.vx-preloader-skip .vx-preloader{display:none}
```

- [x] **Step 3: Run the preloader after the avatar-loader import, gated on the skip class** — in the existing `<script type="module">` block (lines 60-73), add this logic after the existing `if (window.matchMedia(...)) { ... }` block, still inside the same `<script type="module">` tag:

```js
  var preloaderEl = document.getElementById('vx-preloader');
  if (preloaderEl) {
    if (document.documentElement.classList.contains('vx-preloader-skip')) {
      preloaderEl.remove();
    } else {
      import('/js/vx-preloader.js').then(function (mod) {
        mod.runPreloader(preloaderEl, {
          onDone: function () {
            preloaderEl.remove();
            try { sessionStorage.setItem('vx-preloader-shown', '1'); } catch (e) {}
          }
        });
      }).catch(function () {
        preloaderEl.remove();
      });
    }
  }
```

- [x] **Step 4: Add test coverage** — add to `tests/Feature/LoginPageTest.php`:

```php
    public function test_login_page_renders_preloader_overlay_gated_by_session_storage(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('id="vx-preloader"', false);
        $response->assertSee('id="vx-preloader-lines"', false);
        $response->assertSee('vx-preloader-shown', false);
        $response->assertSee("import('/js/vx-preloader.js')", false);
    }

    public function test_login_page_uses_svg_logo_not_raster(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('logo.svg', false);
        $response->assertDontSee('logo.png', false);
    }
```

- [x] **Step 5: Run the tests**

Run: `.php84/php.exe artisan test --filter=LoginPageTest`
Expected: all tests PASS, including the two new ones and all pre-existing ones (split layout, theme tokens, mobile-hides-stage, form fields, invalid-credentials — none of these are affected by the preloader addition).

- [x] **Step 6: Blade syntax smoke check**

Run: `.php84/php.exe artisan view:cache && .php84/php.exe artisan view:clear`
Expected: no errors.

- [x] **Step 7: Manual browser verification**

- Clear site data / open a fresh private window, load `/login`: confirm the copper lines draw in starting from the middle row and cascading outward (top and bottom lines appear last), then the whole overlay fades out to reveal the split-layout login page.
- Reload the same page (same session): confirm the preloader does NOT replay — the login page appears immediately.
- Open a new private window (fresh session) and repeat: confirm it plays again.
- Enable OS-level "reduce motion", clear session storage, reload: confirm the logo appears fully-formed immediately with only a brief fade, no staggered line-by-line drawing.
- With browser devtools, throttle/block `/js/vx-preloader.js` to simulate a load failure, reload: confirm the overlay is removed immediately and the login form is fully usable (never stuck behind the overlay).

- [x] **Step 8: Full project test suite**

Run: `.php84/php.exe artisan test`
Expected: all tests across the whole suite PASS.

- [x] **Step 9: Commit**

```bash
git add resources/views/auth/login.blade.php tests/Feature/LoginPageTest.php
git commit -m "Wire copper-line preloader into the login page, gated once per session"
```
