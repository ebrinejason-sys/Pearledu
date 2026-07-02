# VoxSign Landing Page v4 Revamp Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the v3 SVG hand-shape avatar demo with a real 3D avatar driven by procedural bone rotation, ship a hand-authored SVG logo in place of the raster PNG, and give the shared layout a new art-direction pass (hero, section bands, cards, eyebrows, stat numbers) — all while preserving every piece of existing copy and the v2 information architecture.

**Architecture:** Three self-contained visual/behavioral changes to the existing server-rendered Blade app (`layouts/marketing.blade.php` + `resources/views/landing/partials/*.blade.php`), no new backend code, no bundler. The 3D avatar loads Three.js from a CDN via an ES module import map scoped to the one partial that needs it, so the rest of the site's payload is unaffected.

**Tech Stack:** Laravel 13 Blade views, vanilla CSS/JS, Three.js r0.170.0 (ESM via unpkg, import map — no npm/bundler), existing `thirg glb.glb` Mixamo-rigged model (gitignored, deployed to the server manually, not via git).

## Global Constraints

- No npm/Vite/webpack bundler introduced. Three.js loads via ESM CDN + import map only, scoped to `avatar-demo.blade.php` — do not add it to the shared `<head>` in `layouts/marketing.blade.php`.
- Palette custom properties (`--ink`, `--paper`, `--surface`, `--voice`, `--sign`, `--muted`, `--line`, `--grad`) are unchanged — reuse them, do not introduce new colors.
- Fonts (`--display`: Clash Display, `--body`: Satoshi) are unchanged.
- All existing copy/content is preserved verbatim: team roster, partner list, testimonials, roadmap text, contact info, PearlEdu/Accessibility descriptions, ASR technical description, the two avatar-demo captions ("How are you?" / "What is your name?"), the "Concept preview" badge text, and the non-linguistic-accuracy disclaimer sentence.
- Every new animation (hero gradient shift, avatar pose-cycle, any new hover/transition) must be gated behind `@media(prefers-reduced-motion:reduce)` (CSS) or `window.matchMedia('(prefers-reduced-motion: reduce)').matches` (JS), matching the existing pattern already used for `.vx-reveal`, `.vx-card`, and the v3 avatar crossfade.
- `tests/Feature/LandingPageTest.php` routes require Postgres (`ResolveTenant` middleware) and cannot run locally in this dev environment — this is a standing, already-documented limitation, not something to fix in this plan. Implementers still write/update assertions; `php artisan view:cache` (or `.php84/php.exe artisan view:cache` on this machine) is the available local Blade-syntax check, run from the repo root.
- `*.glb` stays gitignored (already the case). The model file reaches the server via a manual upload, never a git commit — Task 5 documents this step explicitly.

---

### Task 1: SVG Logo

**Files:**
- Create: `resources/svg/voxsign-logo.svg` (source reference copy, not loaded by the app directly — kept for future editing)
- Modify: `resources/views/layouts/marketing.blade.php:104` (nav logo `<a>`), and the `.vx-nav img` CSS rule at line 27
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: an inline `<svg class="vx-logo">` element in the nav, replacing `<img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign">`. Later tasks do not depend on this markup.

- [ ] **Step 1: Write the SVG source file**

Create `resources/svg/voxsign-logo.svg` with this exact content (a rounded badge containing two overlapping shapes — an arc for "Vox"/voice and a simplified raised-hand silhouette for "Sign" — filled with the existing brand gradient, plus a wordmark):

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 32" fill="none" role="img" aria-label="VoxSign">
  <defs>
    <linearGradient id="vxLogoGrad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="#FF6A3D"/>
      <stop offset="1" stop-color="#12B3A6"/>
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="32" height="32" rx="9" fill="url(#vxLogoGrad)"/>
  <path d="M7 20c2.5-7 5-10.5 9-10.5s6.5 3.5 9 10.5" stroke="#FBFAF7" stroke-width="2.4" stroke-linecap="round" fill="none"/>
  <path d="M12 22.5v-6.2c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v4.2M15.6 20.5v-5.4c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v5.4M19.2 21v-4.6c0-1 .8-1.8 1.7-1.8s1.7.8 1.7 1.8v5.6c0 2.5-1.9 4.5-4.5 4.5h-1.6c-1.5 0-2.9-.7-3.8-1.9l-2.6-3.4a1.5 1.5 0 0 1 2.3-1.9l1.5 1.6" stroke="#FBFAF7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  <text x="40" y="22" font-family="'Clash Display',system-ui,sans-serif" font-weight="600" font-size="18" fill="#0B1020">VoxSign</text>
</svg>
```

- [ ] **Step 2: Inline the SVG into the nav**

In `resources/views/layouts/marketing.blade.php`, replace line 104:

```html
<a href="{{ url('/') }}"><img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign"></a>
```

with:

```html
<a href="{{ url('/') }}" class="vx-logo-link" aria-label="VoxSign home">
  <svg class="vx-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 32" fill="none" role="img" aria-label="VoxSign">
    <defs>
      <linearGradient id="vxLogoGrad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#FF6A3D"/>
        <stop offset="1" stop-color="#12B3A6"/>
      </linearGradient>
    </defs>
    <rect x="0" y="0" width="32" height="32" rx="9" fill="url(#vxLogoGrad)"/>
    <path d="M7 20c2.5-7 5-10.5 9-10.5s6.5 3.5 9 10.5" stroke="#FBFAF7" stroke-width="2.4" stroke-linecap="round" fill="none"/>
    <path d="M12 22.5v-6.2c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v4.2M15.6 20.5v-5.4c0-1 .8-1.8 1.8-1.8s1.8.8 1.8 1.8v5.4M19.2 21v-4.6c0-1 .8-1.8 1.7-1.8s1.7.8 1.7 1.8v5.6c0 2.5-1.9 4.5-4.5 4.5h-1.6c-1.5 0-2.9-.7-3.8-1.9l-2.6-3.4a1.5 1.5 0 0 1 2.3-1.9l1.5 1.6" stroke="#FBFAF7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    <text x="40" y="22" font-family="'Clash Display',system-ui,sans-serif" font-weight="600" font-size="18" fill="#0B1020">VoxSign</text>
  </svg>
</a>
```

- [ ] **Step 3: Replace the `.vx-nav img` CSS rule with `.vx-logo`**

In the same file, replace the CSS rule at line 27 (`.vx-nav img{height:28px;display:block}`) with:

```css
.vx-logo-link{display:flex;align-items:center}
.vx-logo{height:32px;width:auto;display:block}
```

- [ ] **Step 4: Verify Blade syntax**

Run: `.php84/php.exe artisan view:cache` from the repo root.
Expected: command completes with no Blade compilation errors (exit code 0). Run `.php84/php.exe artisan view:clear` afterward to avoid stale cached views during later tasks.

- [ ] **Step 5: Update the feature test**

In `tests/Feature/LandingPageTest.php`, find the test that currently asserts the nav renders the logo `<img>` (search for `voxsign-logo.png` or `'<img'` near nav assertions). Replace any assertion checking for the PNG `<img>` tag with an assertion that the response contains `<svg class="vx-logo"` and no longer contains `voxsign-logo.png`. If no such test exists yet, add one:

```php
public function test_nav_renders_inline_svg_logo_not_png(): void
{
    $response = $this->get('http://voxsign.co.ug/');

    $response->assertSee('<svg class="vx-logo"', false);
    $response->assertDontSee('voxsign-logo.png');
}
```

Note in your report that this test cannot be executed locally (Postgres-backed route) — this is the standing limitation described in Global Constraints, not a new gap.

- [ ] **Step 6: Commit**

```bash
git add resources/svg/voxsign-logo.svg resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Replace raster PNG logo with inline SVG mark"
```

---

### Task 2: 3D Avatar (Three.js procedural bone animation)

**Files:**
- Modify: `resources/views/landing/partials/avatar-demo.blade.php` (full rewrite of the `<div class="vx-avatar-demo">` block, `<style>`, and `<script>` sections; the `<section>` wrapper, eyebrow, heading, "Concept preview" badge, and disclaimer paragraph at lines 1-10 are unchanged)
- Modify: `resources/views/layouts/marketing.blade.php` (add one CSS utility class: `.vx-sr-only`)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: nothing from Task 1.
- Produces: nothing later tasks depend on — this task is self-contained to the avatar-demo partial plus one shared utility class.

**Context on the model:** `thirg glb.glb` is a Mixamo-rigged glTF with 68 nodes (`mixamorig:` prefix bone names), two untextured meshes with flat PBR materials (body ≈ `rgb(0.077,0.337,0.418)`, joints ≈ `rgb(0.123,0.170,0.178)`), and only one baked animation clip (a T-pose, not usable). The bones this task manipulates: `mixamorig:RightArm`, `mixamorig:RightForeArm`, `mixamorig:RightHand`, `mixamorig:RightHandIndex1/2/3`, `mixamorig:RightHandMiddle1/2/3`, `mixamorig:RightHandRing1/2/3`, `mixamorig:RightHandPinky1/2/3`, `mixamorig:RightHandThumb1/2/3`, `mixamorig:Spine1`. The model will be available at `/models/avatar.glb` on the deployed server (Task 5 handles getting it there — build and test this task's code against that path; it will 404 until Task 5's manual upload happens, which is expected and handled by the fallback path).

- [ ] **Step 1: Replace the avatar-demo markup, style, and script**

Replace lines 11-54 of `resources/views/landing/partials/avatar-demo.blade.php` (the `<div class="vx-avatar-demo">...</div>` through the closing `</script>`) — keep lines 1-10 (section wrapper, eyebrow, heading, badge, disclaimer paragraph) exactly as they are — with:

```html
    <div class="vx-avatar-demo">
      <div id="vx-avatar-3d" class="vx-avatar-3d"></div>
      <p id="vx-avatar-caption" class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
      <p class="vx-sr-only">3D figure demonstrating basic hand-shape signing for the two phrases named in the caption above and below it.</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-avatar-3d{width:280px;height:320px;margin:0 auto}
  .vx-avatar-3d canvas{display:block;margin:0 auto}
  .vx-avatar-fallback{display:flex;align-items:center;justify-content:center;height:100%}
  .vx-avatar-fallback .vx-hand{transition:opacity .5s ease}
  .vx-avatar-fallback .vx-hand-2{opacity:0}
  .vx-avatar-fallback.vx-phrase-2 .vx-hand-1{opacity:0}
  .vx-avatar-fallback.vx-phrase-2 .vx-hand-2{opacity:1}
  @media(prefers-reduced-motion:reduce){.vx-avatar-fallback .vx-hand{transition:none}}
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
(function () {
  var container = document.getElementById('vx-avatar-3d');
  if (!container) return;

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var caption = document.getElementById('vx-avatar-caption');
  var phrases = ['How are you?', 'What is your name?'];
  var captionIndex = 0;

  function showFallback() {
    container.innerHTML =
      '<svg class="vx-avatar-fallback-svg" viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Hand-shape illustration">' +
        '<g class="vx-hand vx-hand-1">' +
          '<rect x="80" y="90" width="40" height="60" rx="18" fill="var(--sign)"/>' +
          '<rect x="60" y="50" width="16" height="55" rx="8" fill="var(--sign)" transform="rotate(-8 68 78)"/>' +
          '<rect x="80" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>' +
          '<rect x="102" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>' +
          '<rect x="124" y="45" width="16" height="60" rx="8" fill="var(--sign)" transform="rotate(8 132 75)"/>' +
          '<rect x="55" y="95" width="30" height="15" rx="7" fill="var(--sign)" transform="rotate(-35 70 102)"/>' +
        '</g>' +
        '<g class="vx-hand vx-hand-2">' +
          '<rect x="80" y="100" width="42" height="55" rx="18" fill="var(--voice)"/>' +
          '<rect x="90" y="40" width="18" height="70" rx="9" fill="var(--voice)"/>' +
          '<rect x="60" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>' +
          '<rect x="118" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>' +
        '</g>' +
      '</svg>';
    container.classList.add('vx-avatar-fallback');
    if (caption) caption.textContent = phrases[0];
    if (!reduceMotion) {
      setInterval(function () {
        captionIndex = (captionIndex + 1) % phrases.length;
        if (caption) caption.textContent = phrases[captionIndex];
        container.classList.toggle('vx-phrase-2', captionIndex === 1);
      }, 3200);
    }
  }

  if (!window.WebGLRenderingContext) { showFallback(); return; }

  Promise.all([import('three'), import('three/addons/loaders/GLTFLoader.js')])
    .then(function (mods) { initScene(mods[0], mods[1].GLTFLoader); })
    .catch(function () { showFallback(); });

  function initScene(THREE, GLTFLoader) {
    var width = container.clientWidth || 280;
    var height = 320;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, width / height, 0.1, 100);
    camera.position.set(0, 1.4, 3.4);
    camera.lookAt(0, 1.1, 0);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (e) {
      showFallback();
      return;
    }
    renderer.setSize(width, height);
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
      '/models/avatar.glb',
      function (gltf) { onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar, container, caption, phrases, reduceMotion); },
      undefined,
      function () { showFallback(); }
    );
  }

  function onModelLoaded(gltf, THREE, scene, camera, renderer, getCssVar, container, caption, phrases, reduceMotion) {
    var root = gltf.scene;
    root.scale.setScalar(1.55);
    root.position.set(0, -1.15, 0);
    scene.add(root);

    var bodyColor = new THREE.Color(getCssVar('--sign', '#12B3A6'));
    var jointColor = new THREE.Color(getCssVar('--voice', '#FF6A3D'));
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

    function deg(x, y, z) {
      return new THREE.Quaternion().setFromEuler(new THREE.Euler(
        THREE.MathUtils.degToRad(x), THREE.MathUtils.degToRad(y), THREE.MathUtils.degToRad(z)
      ));
    }

    var poses = {
      howAreYou: {
        'mixamorig:RightArm': deg(-50, 0, 70),
        'mixamorig:RightForeArm': deg(-90, 0, 0),
        'mixamorig:RightHand': deg(0, 0, -10),
        'mixamorig:RightHandIndex1': deg(0, 0, -25), 'mixamorig:RightHandIndex2': deg(0, 0, -20), 'mixamorig:RightHandIndex3': deg(0, 0, -15),
        'mixamorig:RightHandMiddle1': deg(0, 0, -30), 'mixamorig:RightHandMiddle2': deg(0, 0, -25), 'mixamorig:RightHandMiddle3': deg(0, 0, -15),
        'mixamorig:RightHandRing1': deg(0, 0, -35), 'mixamorig:RightHandRing2': deg(0, 0, -25), 'mixamorig:RightHandRing3': deg(0, 0, -15),
        'mixamorig:RightHandPinky1': deg(0, 0, -35), 'mixamorig:RightHandPinky2': deg(0, 0, -25), 'mixamorig:RightHandPinky3': deg(0, 0, -15),
        'mixamorig:RightHandThumb1': deg(0, -10, -20), 'mixamorig:RightHandThumb2': deg(0, 0, -15), 'mixamorig:RightHandThumb3': deg(0, 0, -10),
        'mixamorig:Spine1': deg(0, -5, 0)
      },
      whatIsYourName: {
        'mixamorig:RightArm': deg(-80, 0, 60),
        'mixamorig:RightForeArm': deg(-15, 0, 0),
        'mixamorig:RightHand': deg(0, 0, 0),
        'mixamorig:RightHandIndex1': deg(0, 0, 0), 'mixamorig:RightHandIndex2': deg(0, 0, 0), 'mixamorig:RightHandIndex3': deg(0, 0, 0),
        'mixamorig:RightHandMiddle1': deg(0, 0, 0), 'mixamorig:RightHandMiddle2': deg(0, 0, 0), 'mixamorig:RightHandMiddle3': deg(0, 0, 0),
        'mixamorig:RightHandRing1': deg(0, 0, 0), 'mixamorig:RightHandRing2': deg(0, 0, 0), 'mixamorig:RightHandRing3': deg(0, 0, 0),
        'mixamorig:RightHandPinky1': deg(0, 0, 0), 'mixamorig:RightHandPinky2': deg(0, 0, 0), 'mixamorig:RightHandPinky3': deg(0, 0, 0),
        'mixamorig:RightHandThumb1': deg(0, -10, 0), 'mixamorig:RightHandThumb2': deg(0, 0, 0), 'mixamorig:RightHandThumb3': deg(0, 0, 0),
        'mixamorig:Spine1': deg(0, 5, 0)
      }
    };
    var poseOrder = ['howAreYou', 'whatIsYourName'];

    function applyPoseStatic(pose) {
      Object.keys(pose).forEach(function (name) { if (bones[name]) bones[name].quaternion.copy(pose[name]); });
    }

    if (reduceMotion) {
      applyPoseStatic(poses[poseOrder[0]]);
      if (caption) caption.textContent = phrases[0];
      renderer.render(scene, camera);
      return;
    }

    var HOLD_MS = 3200;
    var TRANSITION_MS = 600;
    var poseIdx = 0;
    var phase = 'hold';
    var phaseStart = performance.now();
    var fromPose = clonePose(bones);
    var toPose = poses[poseOrder[0]];
    applyPoseStatic(toPose);
    if (caption) caption.textContent = phrases[0];

    function clonePose(boneMap) {
      var out = {};
      Object.keys(boneMap).forEach(function (name) { out[name] = boneMap[name].quaternion.clone(); });
      return out;
    }

    function easeInOutQuad(t) { return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; }

    function tick(now) {
      var elapsed = now - phaseStart;
      if (phase === 'hold' && elapsed >= HOLD_MS) {
        phase = 'transition';
        phaseStart = now;
        fromPose = clonePose(bones);
        poseIdx = (poseIdx + 1) % poseOrder.length;
        toPose = poses[poseOrder[poseIdx]];
        if (caption) caption.textContent = phrases[poseIdx];
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
})();
</script>
```

- [ ] **Step 2: Add the `.vx-sr-only` utility class**

In `resources/views/layouts/marketing.blade.php`, add this rule to the `<style>` block (near the other utility rules, e.g. right after the `.vx-wrap` rule at line 22):

```css
.vx-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
```

- [ ] **Step 3: Verify Blade syntax**

Run: `.php84/php.exe artisan view:cache` from the repo root.
Expected: exit code 0, no compilation errors. Run `.php84/php.exe artisan view:clear` after.

- [ ] **Step 4: Update the feature test**

In `tests/Feature/LandingPageTest.php`, find the existing avatar-demo test (search for `vx-avatar-demo` or `Concept preview`). Update it to check for the new container instead of the old inline SVG groups, and confirm the preserved copy is still present:

```php
public function test_avatar_demo_renders_3d_container_and_preserved_copy(): void
{
    $response = $this->get('http://voxsign.co.ug/');

    $response->assertSee('id="vx-avatar-3d"', false);
    $response->assertSee('id="vx-avatar-caption"', false);
    $response->assertSee('How are you?');
    $response->assertSee('Concept preview');
    $response->assertSee('illustrative, not a verified Ugandan Sign Language rendering');
}
```

Remove or update any old assertion that specifically checked for the v3 `vx-hand-1`/`vx-hand-2` SVG groups being present in the initial server-rendered HTML (they're now only injected client-side as a fallback, not server-rendered). Note in your report that this test cannot run locally (Postgres-backed route).

- [ ] **Step 5: Commit**

```bash
git add resources/views/landing/partials/avatar-demo.blade.php resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Replace SVG hand-shape avatar demo with 3D Three.js procedural bone animation"
```

---

### Task 3: Hero Visual Revamp

**Files:**
- Modify: `resources/views/landing/partials/hero.blade.php`
- Modify: `resources/views/layouts/marketing.blade.php` (CSS additions only)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: nothing from Tasks 1-2.
- Produces: nothing later tasks depend on.

- [ ] **Step 1: Add a gradient-blob background layer behind the hero**

In `resources/views/landing/partials/hero.blade.php`, wrap the existing content in a positioned container and add a decorative background div. Replace the full file content with:

```html
<section class="vx-section vx-hero" style="padding-top:56px">
  <div class="vx-hero-glow" aria-hidden="true"></div>
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">AI &middot; Inclusion Technology</div>
    <h1 class="vx-h1">Technology built to <span class="vx-flow">include everyone.</span></h1>
    <p class="vx-lead">
      VoxSign is two things working toward one goal: <strong>PearlEdu</strong>, a school
      management platform for institutions, and <strong>VoxSign Accessibility</strong>, AI
      tools that give Deaf, hard-of-hearing, and speech-impaired people a voice in the room.
    </p>
    <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
      <a href="#contact" class="vx-btn">Talk to us</a>
      <a href="#pearledu" class="vx-btn-ghost">See both divisions ↓</a>
    </p>
  </div>
</section>
```

- [ ] **Step 2: Add hero CSS (gradient blob + animated gradient-text)**

In `resources/views/layouts/marketing.blade.php`, add these rules to the `<style>` block, directly after the `.vx-h1 .vx-flow{...}` rule at line 48:

```css
.vx-hero{position:relative;overflow:hidden}
.vx-hero-glow{position:absolute;inset:-20% -10% auto -10%;height:520px;z-index:0;pointer-events:none;
  background:
    radial-gradient(480px 320px at 15% 20%, rgba(255,106,61,.16), transparent 70%),
    radial-gradient(520px 360px at 85% 10%, rgba(18,179,166,.16), transparent 70%)}
.vx-hero .vx-wrap{position:relative;z-index:1}
.vx-h1 .vx-flow{background-size:200% 100%;animation:vxFlowShift 6s ease-in-out infinite}
@keyframes vxFlowShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
@media(prefers-reduced-motion:reduce){.vx-h1 .vx-flow{animation:none}}
```

- [ ] **Step 3: Verify Blade syntax**

Run: `.php84/php.exe artisan view:cache` from the repo root.
Expected: exit code 0, no compilation errors. Run `.php84/php.exe artisan view:clear` after.

- [ ] **Step 4: Update the feature test**

In `tests/Feature/LandingPageTest.php`, find the hero test (search for `vx-h1` or `include everyone`). Add an assertion that the decorative glow element and the preserved headline copy both render:

```php
public function test_hero_renders_gradient_glow_and_preserved_headline(): void
{
    $response = $this->get('http://voxsign.co.ug/');

    $response->assertSee('vx-hero-glow', false);
    $response->assertSee('include everyone.');
}
```

Note in your report that this test cannot run locally (Postgres-backed route).

- [ ] **Step 5: Commit**

```bash
git add resources/views/landing/partials/hero.blade.php resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add gradient-blob hero background and animated gradient headline text"
```

---

### Task 4: Section-Wide Visual Pass (bands, cards, eyebrows, stat numbers)

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php` (CSS only — no partial files change, since every affected element already uses shared classes: `.vx-section`, `.vx-eyebrow`, `.vx-card`, `.vx-step-n`)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: nothing from Tasks 1-3.
- Produces: nothing later tasks depend on.

This task is CSS-only because every section partial already emits `<div class="vx-eyebrow">`, `<section class="vx-section">`, `<div class="vx-card">`, and `<div class="vx-step-n">` with no per-partial variation — the new treatments apply purely by changing the shared rules for those classes.

- [ ] **Step 1: Alternating section band tint**

In `resources/views/layouts/marketing.blade.php`, add this rule immediately after the existing `.vx-band .vx-lead{color:#aeb4c2}` rule (around line 44), so `.vx-band`'s own background (declared earlier) still wins on the one dark section:

```css
.vx-section:nth-of-type(even):not(.vx-band){background:linear-gradient(180deg,var(--paper) 0%,#F5F3EC 100%)}
```

- [ ] **Step 2: Eyebrow pill treatment**

Replace the existing `.vx-eyebrow` rule (line 46):

```css
.vx-eyebrow{font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase}
```

with:

```css
.vx-eyebrow{display:inline-block;font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase;background:rgba(255,106,61,.1);border:1px solid rgba(255,106,61,.25);border-radius:999px;padding:5px 14px}
.vx-band .vx-eyebrow{background:rgba(18,179,166,.14);border-color:rgba(18,179,166,.35)}
```

(The second rule keeps the pill legible against the dark `.vx-band` section, which already overrides eyebrow *text* color to `--sign` via the existing `.vx-band .vx-eyebrow{color:var(--sign)}` rule at line 43 — that rule is unchanged and still applies.)

- [ ] **Step 3: Card top-accent bar and radius bump**

Replace the existing `.vx-card` rule (line 62):

```css
.vx-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px;transition:transform .2s ease,box-shadow .2s ease}
```

with:

```css
.vx-card{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:22px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
.vx-card::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:transform .25s ease}
.vx-card:hover::before{transform:scaleX(1)}
@media(prefers-reduced-motion:reduce){.vx-card::before{transition:none}}
```

- [ ] **Step 4: Gradient-text treatment for step numbers**

Replace the existing `.vx-step-n` rule (line 78):

```css
.vx-step-n{font-family:var(--display);font-size:24px;font-weight:800;color:var(--voice)}
```

with:

```css
.vx-step-n{display:inline-block;font-family:var(--display);font-size:24px;font-weight:800;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
```

- [ ] **Step 5: Verify Blade syntax**

Run: `.php84/php.exe artisan view:cache` from the repo root.
Expected: exit code 0, no compilation errors. Run `.php84/php.exe artisan view:clear` after.

- [ ] **Step 6: Update the feature test**

In `tests/Feature/LandingPageTest.php`, add one assertion confirming the CSS ships (checking a distinctive selector is present in the rendered `<style>` block is sufficient — this is a visual CSS change, not new markup):

```php
public function test_layout_includes_v4_visual_pass_css(): void
{
    $response = $this->get('http://voxsign.co.ug/');

    $response->assertSee('.vx-card::before', false);
    $response->assertSee('vxFlowShift', false);
}
```

Note in your report that this test cannot run locally (Postgres-backed route).

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add alternating section bands, eyebrow pills, card accent bars, and gradient stat numbers"
```

---

### Task 5: Deployment Notes (GLB upload + MIME type + verification)

**Files:**
- Modify: `docs/CPANEL_DEPLOY.md` (add a short section — read the existing file first to match its structure/tone before appending)
- Modify: `public/.htaccess` (one addition)

**Interfaces:**
- Consumes: Task 2's expectation that the model is reachable at `/models/avatar.glb` on the live server.

This task has no PHPUnit-testable behavior — it's deployment documentation plus one static-file-serving config line. Do not write a PHPUnit test for it.

- [ ] **Step 1: Add a MIME type hint for `.glb` files**

In `public/.htaccess`, add this line inside the existing `<IfModule mod_rewrite.c>` block, right after the opening `<IfModule mod_rewrite.c>` line (line 1) and before the `<IfModule mod_negotiation.c>` block:

```apache
    AddType model/gltf-binary .glb
```

This is not strictly required for the browser to load the file (the loader fetches it as raw bytes regardless of `Content-Type`), but it's correct practice and avoids the server serving it as `application/octet-stream` or, worse, `text/plain` under some default configs.

- [ ] **Step 2: Read the existing deploy doc**

Read `docs/CPANEL_DEPLOY.md` in full first, so the addition matches its existing heading style and level of detail.

- [ ] **Step 3: Append a manual-upload section**

Add a new section to `docs/CPANEL_DEPLOY.md` (match the existing heading level used for other operational steps in that file) with this content:

```markdown
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
```

- [ ] **Step 4: Commit**

```bash
git add public/.htaccess docs/CPANEL_DEPLOY.md
git commit -m "Document manual GLB upload step and add glTF MIME type"
```

---

## Post-Plan Steps (not subagent tasks — controller does these directly)

1. Push all task commits, deploy via cPanel Git Version Control ("Update from Remote" + "Deploy HEAD Commit").
2. Manually upload `thirg glb.glb` to `public/models/avatar.glb` per Task 5's documented steps (cPanel File Manager or SFTP — this repo's controller has done cPanel File Manager uploads before in this project's history).
3. Run the `webapp-testing` skill's Playwright setup against `https://voxsign.co.ug/` at 375×812, 768×1024, 1024×768, and 1440×900 to verify: no horizontal overflow, mobile nav opens/closes correctly, the avatar canvas renders (or gracefully shows the fallback) without breaking page scroll, the SVG logo renders crisply, and the hero gradient glow doesn't cause overflow on narrow viewports. Fix and re-verify any findings.
