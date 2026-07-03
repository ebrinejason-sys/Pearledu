# Login Page + 3D Avatar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the bare centered PearlEdu login card with a full-bleed split-screen layout (theme-colored brand panel + form on the left, white stage with an idle 3D avatar on the right), and extract the landing page's Three.js avatar loader into a shared module so both pages reuse it.

**Architecture:** Two self-contained changes. Task 1 extracts the existing inline Three.js loading/rendering/fallback logic from `avatar-demo.blade.php` into a plain ES module (`public/js/vx-avatar-loader.js`, no bundler) with zero visual/behavioral change to the landing page. Task 2 adds a new minimal auth layout and rewrites the login page to use the split layout plus the shared module in a new "idle" mode, recolored from the app's existing per-school theme tokens instead of VoxSign's fixed palette.

**Tech Stack:** Laravel 13 Blade views, vanilla CSS/JS, Three.js r0.170.0 (ESM via unpkg, import map — no npm/bundler, matching the existing project-wide constraint), existing `thirg glb.glb` model (served from `/models/avatar.glb` in production; gitignored, not part of this plan's commits).

## Global Constraints

- No npm/Vite/webpack bundler. Three.js loads via ESM CDN + import map only.
- Every animation must be gated behind `@media(prefers-reduced-motion:reduce)` (CSS) or `window.matchMedia('(prefers-reduced-motion: reduce)').matches` (JS) — this codebase already uses this pattern extensively (e.g. `.vx-reveal`, `.vx-card`, the existing avatar cycle); `@media` written literally inside a `<style>` block in a `.blade.php` file is safe and already proven elsewhere in this codebase (Blade does not treat `@media` as a directive).
- The landing page's avatar demo (`--sign`/`--voice` colors, sign-phrase captions, existing test `test_avatar_demo_renders_3d_container_and_preserved_copy` in `tests/Feature/LandingPageTest.php`) must keep working identically after Task 1 — this is a pure refactor, not a redesign.
- The login page must recolor the avatar and panel from the app's existing per-school theme tokens (`--sidebar`, `--sidebar-ink`, `--brand`, `--accent`, `--surface`, defined in `config/themes.php`, rendered as CSS `:root` variables by the existing global `View::composer('*', ...)` in `app/Providers/AppServiceProvider.php`) — never hardcode VoxSign's `--sign`/`--voice`/`#FF6A3D`/`#12B3A6` values on this page.
- Local dev: Postgres is already running (`.pgsql/pgsql/bin/pg_ctl.exe status -D .pgsql/data-local`) and migrated. Use `.php84/php.exe artisan <command>` for all artisan calls on this machine. Tests run against a separate `voxsign_test` database (see `phpunit.xml`) with `SESSION_DRIVER=array` — no session-table dependency, no seeding needed for the tests in this plan.
- `/login` has no tenant-resolving middleware (see `routes/auth.php`), so `tests/Feature/LoginPageTest.php` runs locally with a plain `$this->get('/login')` — no `http://voxsign.co.ug/` host prefix needed (unlike `LandingPageTest`).

---

### Task 1: Extract shared Three.js avatar loader module

**Files:**
- Create: `public/js/vx-avatar-loader.js`
- Modify: `resources/views/landing/partials/avatar-demo.blade.php` (only the `<script type="module">...</script>` block, lines 37-242 of the current file; the `<section>` markup, the `<style>` block, and the `<script type="importmap">` block above it are unchanged)
- Test: `tests/Feature/LandingPageTest.php` (existing test, used as a regression check — not modified)

**Interfaces:**
- Produces: `export function mountAvatar(config)` from `public/js/vx-avatar-loader.js`, where `config` is:
  ```
  {
    container: string (element id) | HTMLElement,
    mode: 'cycle' | 'idle',
    modelUrl?: string (default '/models/avatar.glb'),
    width?: number (default 280), height?: number (default 320),
    colorVars?: [bodyVarName, jointVarName] (default ['--sign','--voice']),
    colorFallbacks?: [bodyHex, jointHex] (default ['#12B3A6','#FF6A3D']),
    captionId?: string (element id, 'cycle' mode only),
    phrases?: string[] ('cycle' mode only),
    poseOrder?: string[] ('cycle' mode only),
    poses?: { [poseName]: { [boneName]: [xDeg, yDeg, zDeg] } } ('cycle' mode only)
  }
  ```
- Consumes: nothing from other tasks.

- [ ] **Step 1: Confirm the baseline test passes before touching anything**

Run: `.php84/php.exe artisan test --filter=test_avatar_demo_renders_3d_container_and_preserved_copy`
Expected: `Tests: 1 passed`

- [ ] **Step 2: Write `public/js/vx-avatar-loader.js`**

```js
export function mountAvatar(config) {
  var container = typeof config.container === 'string' ? document.getElementById(config.container) : config.container;
  if (!container) return;

  var mode = config.mode || 'cycle';
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var captionEl = config.captionId ? document.getElementById(config.captionId) : null;
  var phrases = config.phrases || [];
  var poses = config.poses || {};
  var poseOrder = config.poseOrder || Object.keys(poses);
  var modelUrl = config.modelUrl || '/models/avatar.glb';
  var colorVars = config.colorVars || ['--sign', '--voice'];
  var colorFallbacks = config.colorFallbacks || ['#12B3A6', '#FF6A3D'];
  var width = config.width || 280;
  var height = config.height || 320;
  var captionIndex = 0;

  function buildFallbackSvg() {
    return '<svg class="vx-avatar-fallback-svg" viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Hand-shape illustration">' +
        '<g class="vx-hand vx-hand-1">' +
          '<rect x="80" y="90" width="40" height="60" rx="18" fill="var(' + colorVars[0] + ')"/>' +
          '<rect x="60" y="50" width="16" height="55" rx="8" fill="var(' + colorVars[0] + ')" transform="rotate(-8 68 78)"/>' +
          '<rect x="80" y="35" width="16" height="65" rx="8" fill="var(' + colorVars[0] + ')"/>' +
          '<rect x="102" y="35" width="16" height="65" rx="8" fill="var(' + colorVars[0] + ')"/>' +
          '<rect x="124" y="45" width="16" height="60" rx="8" fill="var(' + colorVars[0] + ')" transform="rotate(8 132 75)"/>' +
          '<rect x="55" y="95" width="30" height="15" rx="7" fill="var(' + colorVars[0] + ')" transform="rotate(-35 70 102)"/>' +
        '</g>' +
        '<g class="vx-hand vx-hand-2">' +
          '<rect x="80" y="100" width="42" height="55" rx="18" fill="var(' + colorVars[1] + ')"/>' +
          '<rect x="90" y="40" width="18" height="70" rx="9" fill="var(' + colorVars[1] + ')"/>' +
          '<rect x="60" y="105" width="24" height="16" rx="8" fill="var(' + colorVars[1] + ')"/>' +
          '<rect x="118" y="105" width="24" height="16" rx="8" fill="var(' + colorVars[1] + ')"/>' +
        '</g>' +
      '</svg>';
  }

  function showFallback() {
    container.innerHTML = buildFallbackSvg();
    container.classList.add('vx-avatar-fallback');
    if (captionEl && phrases.length) {
      captionEl.textContent = phrases[0];
      if (!reduceMotion) {
        setInterval(function () {
          captionIndex = (captionIndex + 1) % phrases.length;
          captionEl.textContent = phrases[captionIndex];
          container.classList.toggle('vx-phrase-2', captionIndex === 1);
        }, 3200);
      }
    }
  }

  if (!window.WebGLRenderingContext) { showFallback(); return; }

  Promise.all([import('three'), import('three/addons/loaders/GLTFLoader.js')])
    .then(function (mods) { initScene(mods[0], mods[1].GLTFLoader); })
    .catch(function () { showFallback(); });

  function initScene(THREE, GLTFLoader) {
    var w = container.clientWidth || width;
    var h = height;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, w / h, 0.1, 100);
    camera.position.set(0, 1.4, 3.4);
    camera.lookAt(0, 1.1, 0);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (e) {
      showFallback();
      return;
    }
    renderer.setSize(w, h);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.domElement.setAttribute('aria-hidden', 'true');
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.7));
    var key = new THREE.DirectionalLight(0xffffff, 0.9);
    key.position.set(2, 3, 2);
    scene.add(key);

    function getCssVar(name, fallback) {
      var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return v || fallback;
    }

    var loader = new GLTFLoader();
    loader.load(
      modelUrl,
      function (gltf) { onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar); },
      undefined,
      function () { showFallback(); }
    );
  }

  function deg(THREE, x, y, z) {
    return new THREE.Quaternion().setFromEuler(new THREE.Euler(
      THREE.MathUtils.degToRad(x), THREE.MathUtils.degToRad(y), THREE.MathUtils.degToRad(z)
    ));
  }

  function onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar) {
    var root = gltf.scene;
    root.scale.setScalar(1.55);
    root.position.set(0, -1.15, 0);
    scene.add(root);

    var bodyColor = new THREE.Color(getCssVar(colorVars[0], colorFallbacks[0]));
    var jointColor = new THREE.Color(getCssVar(colorVars[1], colorFallbacks[1]));
    root.traverse(function (node) {
      if (node.isMesh && node.material) {
        node.material = node.material.clone();
        var isJoint = (node.material.name || '').indexOf('Joint') !== -1;
        node.material.color.copy(isJoint ? jointColor : bodyColor);
      }
    });

    var boneNames = [
      'mixamorig:RightArm', 'mixamorig:RightForeArm', 'mixamorig:RightHand',
      'mixamorig:RightHandIndex1', 'mixamorig:RightHandIndex2', 'mixamorig:RightHandIndex3',
      'mixamorig:RightHandMiddle1', 'mixamorig:RightHandMiddle2', 'mixamorig:RightHandMiddle3',
      'mixamorig:RightHandRing1', 'mixamorig:RightHandRing2', 'mixamorig:RightHandRing3',
      'mixamorig:RightHandPinky1', 'mixamorig:RightHandPinky2', 'mixamorig:RightHandPinky3',
      'mixamorig:RightHandThumb1', 'mixamorig:RightHandThumb2', 'mixamorig:RightHandThumb3',
      'mixamorig:Spine1'
    ];
    var bones = {};
    boneNames.forEach(function (name) {
      var b = root.getObjectByName(name);
      if (b) bones[name] = b;
    });

    var restQuats = {};
    Object.keys(bones).forEach(function (name) { restQuats[name] = bones[name].quaternion.clone(); });

    if (mode === 'idle') {
      runIdle(THREE, bones, restQuats, renderer, scene, camera);
    } else {
      runCycle(THREE, bones, restQuats, renderer, scene, camera);
    }
  }

  function runIdle(THREE, bones, restQuats, renderer, scene, camera) {
    if (reduceMotion) { renderer.render(scene, camera); return; }
    var start = performance.now();
    function tick(now) {
      var t = (now - start) / 1000;
      var spine = bones['mixamorig:Spine1'];
      if (spine && restQuats['mixamorig:Spine1']) {
        spine.quaternion.copy(restQuats['mixamorig:Spine1']).multiply(deg(THREE, Math.sin(t * 0.6) * 2.5, Math.sin(t * 0.4) * 1.5, 0));
      }
      var arm = bones['mixamorig:RightArm'];
      if (arm && restQuats['mixamorig:RightArm']) {
        arm.quaternion.copy(restQuats['mixamorig:RightArm']).multiply(deg(THREE, Math.sin(t * 0.5 + 1) * 3, 0, 0));
      }
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function runCycle(THREE, bones, restQuats, renderer, scene, camera) {
    var builtPoses = {};
    poseOrder.forEach(function (key) {
      var raw = poses[key] || {};
      var built = {};
      Object.keys(raw).forEach(function (boneName) {
        var t = raw[boneName];
        built[boneName] = deg(THREE, t[0], t[1], t[2]);
      });
      builtPoses[key] = built;
    });

    function applyPoseStatic(pose) {
      Object.keys(pose).forEach(function (name) { if (bones[name]) bones[name].quaternion.copy(pose[name]); });
    }

    if (reduceMotion) {
      applyPoseStatic(builtPoses[poseOrder[0]]);
      if (captionEl) captionEl.textContent = phrases[0];
      renderer.render(scene, camera);
      return;
    }

    var HOLD_MS = 3200;
    var TRANSITION_MS = 600;
    var poseIdx = 0;
    var phase = 'hold';
    var phaseStart = performance.now();

    function clonePose(boneMap) {
      var out = {};
      Object.keys(boneMap).forEach(function (name) { out[name] = boneMap[name].quaternion.clone(); });
      return out;
    }

    var fromPose = clonePose(bones);
    var toPose = builtPoses[poseOrder[0]];
    applyPoseStatic(toPose);
    if (captionEl) captionEl.textContent = phrases[0];

    function easeInOutQuad(t) { return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; }

    function tick(now) {
      var elapsed = now - phaseStart;
      if (phase === 'hold' && elapsed >= HOLD_MS) {
        phase = 'transition';
        phaseStart = now;
        fromPose = clonePose(bones);
        poseIdx = (poseIdx + 1) % poseOrder.length;
        toPose = builtPoses[poseOrder[poseIdx]];
        if (captionEl) captionEl.textContent = phrases[poseIdx];
      } else if (phase === 'transition') {
        var t = Math.min(1, (now - phaseStart) / TRANSITION_MS);
        var e = easeInOutQuad(t);
        Object.keys(bones).forEach(function (name) {
          var target = toPose[name] || restQuats[name];
          bones[name].quaternion.slerpQuaternions(fromPose[name], target, e);
        });
        if (t >= 1) { phase = 'hold'; phaseStart = now; }
      }
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
}
```

- [ ] **Step 3: Replace the `<script type="module">` block in `avatar-demo.blade.php`**

Keep everything from the start of the file through the closing `</script>` of the `importmap` block (lines 1-36) exactly as-is. Replace everything from the next `<script type="module">` (currently line 37) through the final `</script>` (currently line 242) with:

```html
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js';
  mountAvatar({
    container: 'vx-avatar-3d',
    captionId: 'vx-avatar-caption',
    mode: 'cycle',
    width: 280,
    height: 320,
    colorVars: ['--sign', '--voice'],
    colorFallbacks: ['#12B3A6', '#FF6A3D'],
    phrases: ['How are you?', 'What is your name?'],
    poseOrder: ['howAreYou', 'whatIsYourName'],
    poses: {
      howAreYou: {
        'mixamorig:RightArm': [-50, 0, 70],
        'mixamorig:RightForeArm': [-90, 0, 0],
        'mixamorig:RightHand': [0, 0, -10],
        'mixamorig:RightHandIndex1': [0, 0, -25], 'mixamorig:RightHandIndex2': [0, 0, -20], 'mixamorig:RightHandIndex3': [0, 0, -15],
        'mixamorig:RightHandMiddle1': [0, 0, -30], 'mixamorig:RightHandMiddle2': [0, 0, -25], 'mixamorig:RightHandMiddle3': [0, 0, -15],
        'mixamorig:RightHandRing1': [0, 0, -35], 'mixamorig:RightHandRing2': [0, 0, -25], 'mixamorig:RightHandRing3': [0, 0, -15],
        'mixamorig:RightHandPinky1': [0, 0, -35], 'mixamorig:RightHandPinky2': [0, 0, -25], 'mixamorig:RightHandPinky3': [0, 0, -15],
        'mixamorig:RightHandThumb1': [0, -10, -20], 'mixamorig:RightHandThumb2': [0, 0, -15], 'mixamorig:RightHandThumb3': [0, 0, -10],
        'mixamorig:Spine1': [0, -5, 0]
      },
      whatIsYourName: {
        'mixamorig:RightArm': [-80, 0, 60],
        'mixamorig:RightForeArm': [-15, 0, 0],
        'mixamorig:RightHand': [0, 0, 0],
        'mixamorig:RightHandIndex1': [0, 0, 0], 'mixamorig:RightHandIndex2': [0, 0, 0], 'mixamorig:RightHandIndex3': [0, 0, 0],
        'mixamorig:RightHandMiddle1': [0, 0, 0], 'mixamorig:RightHandMiddle2': [0, 0, 0], 'mixamorig:RightHandMiddle3': [0, 0, 0],
        'mixamorig:RightHandRing1': [0, 0, 0], 'mixamorig:RightHandRing2': [0, 0, 0], 'mixamorig:RightHandRing3': [0, 0, 0],
        'mixamorig:RightHandPinky1': [0, 0, 0], 'mixamorig:RightHandPinky2': [0, 0, 0], 'mixamorig:RightHandPinky3': [0, 0, 0],
        'mixamorig:RightHandThumb1': [0, -10, 0], 'mixamorig:RightHandThumb2': [0, 0, 0], 'mixamorig:RightHandThumb3': [0, 0, 0],
        'mixamorig:Spine1': [0, 5, 0]
      }
    }
  });
</script>
```

- [ ] **Step 4: Verify Blade syntax**

Run: `.php84/php.exe artisan view:cache`
Expected: exit code 0, no compilation errors. Then run `.php84/php.exe artisan view:clear`.

- [ ] **Step 5: Confirm the regression test still passes**

Run: `.php84/php.exe artisan test --filter=test_avatar_demo_renders_3d_container_and_preserved_copy`
Expected: `Tests: 1 passed` (identical output markup — this is a pure refactor).

- [ ] **Step 6: Commit**

```bash
git add public/js/vx-avatar-loader.js resources/views/landing/partials/avatar-demo.blade.php
git commit -m "Extract shared Three.js avatar loader module from landing page"
```

---

### Task 2: Login page split layout + theme-driven idle avatar

**Files:**
- Create: `resources/views/layouts/auth.blade.php`
- Modify: `resources/views/auth/login.blade.php` (full rewrite)
- Test: Create `tests/Feature/LoginPageTest.php`

**Interfaces:**
- Consumes: `mountAvatar` from `public/js/vx-avatar-loader.js` (Task 1), called with `mode: 'idle'`.
- Produces: nothing later tasks depend on.

- [ ] **Step 1: Write the failing test file**

Create `tests/Feature/LoginPageTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_renders_split_layout_with_avatar_stage(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('vx-auth-panel', false);
        $response->assertSee('vx-auth-stage', false);
        $response->assertSee('id="vx-login-avatar-3d"', false);
    }

    public function test_login_page_uses_theme_tokens_not_hardcoded_brand_colors(): void
    {
        $response = $this->get('/login');

        $response->assertSee('var(--sidebar)', false);
        $response->assertSee('var(--accent)', false);
        $response->assertDontSee('#FF6A3D', false);
        $response->assertDontSee('#12B3A6', false);
    }

    public function test_login_page_hides_avatar_stage_on_narrow_viewports(): void
    {
        $response = $this->get('/login');

        $response->assertSee('@media(max-width:860px)', false);
        $response->assertSee('.vx-auth-stage{display:none}', false);
    }

    public function test_login_form_still_renders_fields_and_csrf(): void
    {
        $response = $this->get('/login');

        $response->assertSee('name="_token"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
    }

    public function test_invalid_credentials_shows_error_message(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
```

- [ ] **Step 2: Run the test to confirm it fails**

Run: `.php84/php.exe artisan test --filter=LoginPageTest`
Expected: `test_login_page_renders_split_layout_with_avatar_stage`, `test_login_page_uses_theme_tokens_not_hardcoded_brand_colors`, and `test_login_page_hides_avatar_stage_on_narrow_viewports` FAIL (today's `auth/login.blade.php` has none of this markup). `test_login_form_still_renders_fields_and_csrf` and `test_invalid_credentials_shows_error_message` should PASS already (today's page already has these fields and error behavior) — that's expected, they exist to guard against regressions in the rewrite.

- [ ] **Step 3: Create `resources/views/layouts/auth.blade.php`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', config('app.name'))</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<style>
  {!! $themeCss !!}
  *{box-sizing:border-box} html,body{margin:0;height:100%}
  body{font-family:var(--font,'Inter',sans-serif)}
</style>
@yield('head')
</head>
<body>
  @yield('content')
</body>
</html>
```

- [ ] **Step 4: Rewrite `resources/views/auth/login.blade.php`**

```html
@extends('layouts.auth')
@section('title','Sign in')
@section('content')
  <div class="vx-auth-split">
    <div class="vx-auth-panel">
      <a href="{{ url('/login') }}" class="vx-auth-brand">
        <img src="{{ asset('images/brand/logo.png') }}" alt="" width="40" height="40">
        <span>Pearl<b>Edu</b></span>
      </a>
      <div class="vx-auth-card">
        <h1>Sign in</h1>
        <form method="post" action="/login">
          @csrf
          <label>Email</label><input name="email" type="email" value="{{ old('email') }}" required autofocus>
          <label>Password</label><input name="password" type="password" required>
          <label class="vx-auth-remember"><input type="checkbox" name="remember"> Remember me</label>
          @error('email')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">Sign in</button>
        </form>
      </div>
    </div>
    <div class="vx-auth-stage">
      <div id="vx-login-avatar-3d" class="vx-login-avatar-3d"></div>
      <p class="vx-sr-only">Decorative 3D figure, no functional purpose.</p>
    </div>
  </div>
@endsection
@section('head')
<style>
  .vx-auth-split{display:flex;min-height:100vh}
  .vx-auth-panel{flex:0 0 44%;max-width:480px;min-width:340px;background:var(--sidebar);color:var(--sidebar-ink);display:flex;flex-direction:column;justify-content:center;padding:48px;gap:28px}
  .vx-auth-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px;color:#fff}
  .vx-auth-brand span b{opacity:.8}
  .vx-auth-card h1{margin:0 0 18px;font-size:26px;color:#fff}
  .vx-auth-card label{color:var(--sidebar-ink);font-size:13px;margin:12px 0 4px}
  .vx-auth-card input{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);color:#fff}
  .vx-auth-card input::placeholder{color:rgba(255,255,255,.5)}
  .vx-auth-remember{display:flex;align-items:center;gap:8px;font-size:13px}
  .vx-auth-remember input{width:auto}
  .vx-auth-card .btn{width:100%;margin-top:20px;background:var(--accent);color:var(--ink)}
  .vx-auth-card .err{color:#FFD3D3;font-size:13px;margin-top:6px}
  .vx-auth-stage{flex:1;background:var(--surface);display:flex;align-items:center;justify-content:center}
  .vx-login-avatar-3d{width:320px;height:420px}
  .vx-login-avatar-3d canvas{display:block;margin:0 auto}
  .vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  @media(max-width:860px){
    .vx-auth-split{flex-direction:column;min-height:auto}
    .vx-auth-panel{flex:none;max-width:none;min-width:0;padding:32px 24px}
    .vx-auth-stage{display:none}
  }
</style>
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>
<script type="module">
  import { mountAvatar } from '/js/vx-avatar-loader.js';
  mountAvatar({
    container: 'vx-login-avatar-3d',
    mode: 'idle',
    width: 320,
    height: 420,
    colorVars: ['--brand', '--accent'],
    colorFallbacks: ['#13443A', '#DDA22E']
  });
</script>
@endsection
```

- [ ] **Step 5: Verify Blade syntax**

Run: `.php84/php.exe artisan view:cache`
Expected: exit code 0, no compilation errors. Then run `.php84/php.exe artisan view:clear`.

- [ ] **Step 6: Run the tests again to confirm they pass**

Run: `.php84/php.exe artisan test --filter=LoginPageTest`
Expected: `Tests: 5 passed`

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/auth.blade.php resources/views/auth/login.blade.php tests/Feature/LoginPageTest.php
git commit -m "Redesign login page as theme-aware split layout with idle 3D avatar"
```

---

## Post-Plan Steps (not subagent tasks — controller does these directly)

1. Copy the local `thirg glb.glb` file to `public/models/avatar.glb` (gitignored, local-only) so the avatar renders instead of falling back to SVG during manual browser verification.
2. Start the local dev server (`.php84/php.exe artisan serve`) and visually check `/login` and `/` (landing page) in a browser at a few viewport widths, confirming: the login avatar idles gently without sign-language captions, the landing avatar's pose-cycle is unchanged, the login split layout collapses to single-column with no avatar under 860px, and the theme colors render (deep green, matching the default `pearledu` theme).
3. Push the two commits and note that this is now included in the same deploy as the landing-page v4 work already in progress.
