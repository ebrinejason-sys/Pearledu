# VoxSign Landing Page v3 Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Raise VoxSign's landing page to a premium visual/interaction bar — consistent spacing, a distinctive font pairing, a working mobile hamburger nav, a redesigned avatar-demo visual (hand shapes instead of a generic circle-head figure), and smoother animation — all in vanilla CSS/JS.

**Architecture:** Same Blade structure as v2 (`layouts/marketing.blade.php` + `landing/partials/*.blade.php`). Every task here is CSS/markup/JS only — no PHP logic, no controller, no database changes, no new dependencies.

**Tech Stack:** Laravel 13 Blade, plain CSS/JS, Fontshare (replacing Google Fonts).

## Global Constraints

- No new build tooling — no npm, no bundler, no React, no Framer Motion. Vanilla CSS/JS only.
- Palette is unchanged from v2: `--ink:#0B1020; --paper:#FBFAF7; --surface:#FFFFFF; --voice:#FF6A3D; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC; --grad: linear-gradient(100deg,var(--voice),var(--sign));` — only fonts change.
- New fonts: `Clash Display` (headings/`--display`), `Satoshi` (body/`--body`), loaded via Fontshare (`https://api.fontshare.com`).
- v2's content, section order, and information architecture are unchanged — this is a visual/interaction pass only.
- `prefers-reduced-motion` must be respected by every new animation introduced here (scroll-reveal easing, card hover, grid stagger, avatar-demo crossfade).
- The avatar demo's "Concept preview" label and non-linguistic-accuracy disclaimer are unchanged hard requirements — only the illustration changes.
- Mobile nav toggle button needs `aria-label="Menu"` and `aria-expanded` toggling.
- Test host for landing-page feature tests is `http://voxsign.co.ug/`.
- No local PostgreSQL is available in this dev environment (confirmed, pre-existing) — feature tests cannot run end-to-end locally. Verify via `artisan view:cache` (confirms Blade syntax compiles) plus direct file inspection, and say so in reports. Never work around this by changing `phpunit.xml` or database config.

---

### Task 1: Consistent section spacing via `.vx-sec-head` utility

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php:33` (`.vx-section` padding), add new `.vx-sec-head` rule
- Modify: `resources/views/landing/partials/divisions.blade.php:5`
- Modify: `resources/views/landing/partials/pearledu.blade.php:5`
- Modify: `resources/views/landing/partials/accessibility.blade.php:5`
- Modify: `resources/views/landing/partials/how-it-works.blade.php:4`
- Modify: `resources/views/landing/partials/features.blade.php:4`
- Modify: `resources/views/landing/partials/team.blade.php:4`
- Modify: `resources/views/landing/partials/testimonials.blade.php:4`
- Modify: `resources/views/landing/partials/roadmap.blade.php:4`
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `.vx-sec-head` CSS class — used by this task's 8 partials; no later task depends on it, but don't remove it in later tasks.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_section_spacing_uses_responsive_clamp_and_sec_head_class(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('.vx-section{padding:clamp(48px,8vw,88px) 0}', false);
        $response->assertSee('.vx-sec-head{margin-bottom:clamp(28px,4vw,44px)}', false);
        $response->assertSee('class="vx-lead vx-sec-head"', false);
        $response->assertDontSee('style="margin-bottom:32px"', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_section_spacing_uses_responsive_clamp_and_sec_head_class`
Expected: FAIL — `.vx-section` still has flat `72px` padding, `.vx-sec-head` doesn't exist, old inline `margin-bottom` styles still present.

- [ ] **Step 3: Update the `.vx-section` padding and add `.vx-sec-head`**

In `resources/views/layouts/marketing.blade.php`, replace:

```css
  .vx-section{padding:72px 0;border-bottom:1px solid var(--line)}
```

with:

```css
  .vx-section{padding:clamp(48px,8vw,88px) 0;border-bottom:1px solid var(--line)}
  .vx-sec-head{margin-bottom:clamp(28px,4vw,44px)}
```

- [ ] **Step 4: Apply `.vx-sec-head` in the 8 partials**

In `resources/views/landing/partials/divisions.blade.php`, replace:

```blade
    <p class="vx-lead" style="margin-bottom:32px">Every VoxSign product exists to close a gap someone else's technology left open.</p>
```

with:

```blade
    <p class="vx-lead vx-sec-head">Every VoxSign product exists to close a gap someone else's technology left open.</p>
```

In `resources/views/landing/partials/pearledu.blade.php`, replace:

```blade
    <p class="vx-lead" style="margin-bottom:32px">Built for schools that need one system, not five disconnected ones.</p>
```

with:

```blade
    <p class="vx-lead vx-sec-head">Built for schools that need one system, not five disconnected ones.</p>
```

In `resources/views/landing/partials/accessibility.blade.php`, replace:

```blade
    <p class="vx-lead" style="margin-bottom:32px">Communication gaps shut people out — at school, at work, in everyday conversation. These two products close them from opposite directions.</p>
```

with:

```blade
    <p class="vx-lead vx-sec-head">Communication gaps shut people out — at school, at work, in everyday conversation. These two products close them from opposite directions.</p>
```

In `resources/views/landing/partials/how-it-works.blade.php`, replace:

```blade
    <h2 class="vx-h2" style="margin-bottom:28px">From spoken word to signed meaning, instantly.</h2>
```

with:

```blade
    <h2 class="vx-h2 vx-sec-head">From spoken word to signed meaning, instantly.</h2>
```

(Leave `<div class="vx-steps" style="margin-bottom:36px">` on the next line unchanged — that spacing is between the two step groups within the section, not a section-head boundary.)

In `resources/views/landing/partials/features.blade.php`, replace:

```blade
    <h2 class="vx-h2" style="margin-bottom:28px">Everything needed for real, everyday inclusion.</h2>
```

with:

```blade
    <h2 class="vx-h2 vx-sec-head">Everything needed for real, everyday inclusion.</h2>
```

In `resources/views/landing/partials/team.blade.php`, replace:

```blade
    <h2 class="vx-h2" style="margin-bottom:28px">The people behind VoxSign.</h2>
```

with:

```blade
    <h2 class="vx-h2 vx-sec-head">The people behind VoxSign.</h2>
```

In `resources/views/landing/partials/testimonials.blade.php`, replace:

```blade
    <h2 class="vx-h2" style="margin-bottom:28px">What future users are saying.</h2>
```

with:

```blade
    <h2 class="vx-h2 vx-sec-head">What future users are saying.</h2>
```

In `resources/views/landing/partials/roadmap.blade.php`, replace:

```blade
    <h2 class="vx-h2" style="margin-bottom:20px">The road ahead.</h2>
```

with:

```blade
    <h2 class="vx-h2 vx-sec-head">The road ahead.</h2>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_section_spacing_uses_responsive_clamp_and_sec_head_class`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/marketing.blade.php resources/views/landing/partials/divisions.blade.php resources/views/landing/partials/pearledu.blade.php resources/views/landing/partials/accessibility.blade.php resources/views/landing/partials/how-it-works.blade.php resources/views/landing/partials/features.blade.php resources/views/landing/partials/team.blade.php resources/views/landing/partials/testimonials.blade.php resources/views/landing/partials/roadmap.blade.php tests/Feature/LandingPageTest.php
git commit -m "Standardize section spacing with responsive clamp() and a .vx-sec-head utility"
```

---

### Task 2: Replace fonts with Clash Display + Satoshi

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php:6-8,14-15`
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `--display` now resolves to `'Clash Display'`, `--body` to `'Satoshi'` — every partial using `var(--display)`/`var(--body)` (all of them, via inherited `body`/`h1,h2,h3` rules) picks this up automatically; no partial-level changes needed.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_uses_clash_display_and_satoshi_fonts(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('api.fontshare.com', false);
        $response->assertSee('clash-display', false);
        $response->assertSee('satoshi', false);
        $response->assertSee("--display:'Clash Display',system-ui,sans-serif;", false);
        $response->assertSee("--body:'Satoshi',system-ui,sans-serif;", false);
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertDontSee('Bricolage', false);
        $response->assertDontSee('Atkinson', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_uses_clash_display_and_satoshi_fonts`
Expected: FAIL — Google Fonts link and old font names are still present.

- [ ] **Step 3: Replace the font links and CSS variables**

In `resources/views/layouts/marketing.blade.php`, replace:

```blade
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
```

with:

```blade
<link rel="preconnect" href="https://api.fontshare.com">
<link href="https://api.fontshare.com/v2/css?f[]=clash-display@400,500,600,700&f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
```

Then replace:

```css
    --display:'Bricolage Grotesque',system-ui,sans-serif;
    --body:'Atkinson Hyperlegible',system-ui,sans-serif;
```

with:

```css
    --display:'Clash Display',system-ui,sans-serif;
    --body:'Satoshi',system-ui,sans-serif;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_uses_clash_display_and_satoshi_fonts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Replace Google Fonts pairing with Clash Display + Satoshi via Fontshare"
```

---

### Task 3: Mobile hamburger navigation

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php` (CSS, nav markup, script)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-nav-links`, `.vx-nav-cta` from the existing nav markup.
- Produces: `.vx-nav-toggle` button and its toggle script — no later task depends on this.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_mobile_nav_has_hamburger_toggle(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('class="vx-nav-toggle"', false);
        $response->assertSee('aria-label="Menu"', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('.vx-nav-toggle{display:none', false);
        $response->assertSee('@media(max-width:860px)', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_mobile_nav_has_hamburger_toggle`
Expected: FAIL — no `.vx-nav-toggle` exists yet.

- [ ] **Step 3: Add the hamburger CSS**

In `resources/views/layouts/marketing.blade.php`, immediately after the `.vx-nav-cta{margin-left:auto}` rule, add:

```css
  .vx-nav-toggle{display:none;background:none;border:1.5px solid var(--line);border-radius:10px;padding:8px 10px;font-size:18px;cursor:pointer;margin-left:auto}
  @media(max-width:860px){
    .vx-nav-links{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;
                  background:var(--paper);border-bottom:1px solid var(--line);padding:16px 24px;gap:16px;margin:0}
    .vx-nav-links.open{display:flex}
    .vx-nav-cta{display:none}
    .vx-nav-toggle{display:block}
  }
```

No change is needed to the existing `.vx-nav` rule — `position:sticky` already establishes a containing block for absolutely-positioned descendants (the same way `position:relative` would), so the mobile dropdown menu's `position:absolute;top:100%` in Step 3 correctly anchors to `.vx-nav` as-is.

- [ ] **Step 4: Update the nav markup**

In `resources/views/layouts/marketing.blade.php`, replace:

```blade
  <div class="vx-nav">
    <a href="{{ url('/') }}"><img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign"></a>
    <div class="vx-nav-links">
      <a href="#pearledu">Institutions</a>
      <a href="#accessibility">Accessibility</a>
      <a href="#team">Team</a>
      <a href="#contact">Contact</a>
    </div>
    <div class="vx-nav-cta"><a href="#contact" class="vx-btn">Talk to us</a></div>
  </div>
```

with:

```blade
  <div class="vx-nav">
    <a href="{{ url('/') }}"><img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign"></a>
    <div class="vx-nav-links" id="vx-nav-links">
      <a href="#pearledu">Institutions</a>
      <a href="#accessibility">Accessibility</a>
      <a href="#team">Team</a>
      <a href="#contact">Contact</a>
    </div>
    <div class="vx-nav-cta"><a href="#contact" class="vx-btn">Talk to us</a></div>
    <button class="vx-nav-toggle" aria-label="Menu" aria-expanded="false" id="vx-nav-toggle">&#9776;</button>
  </div>
```

- [ ] **Step 5: Add the toggle script**

In `resources/views/layouts/marketing.blade.php`, in the existing `<script>` block, add before the closing `</script>`:

```js
    var navToggle = document.getElementById('vx-nav-toggle');
    var navLinks = document.getElementById('vx-nav-links');
    if (navToggle && navLinks) {
      navToggle.addEventListener('click', function(){
        var open = navLinks.classList.toggle('open');
        navToggle.setAttribute('aria-expanded', open);
      });
      navLinks.querySelectorAll('a').forEach(function(link){
        link.addEventListener('click', function(){
          navLinks.classList.remove('open');
          navToggle.setAttribute('aria-expanded', 'false');
        });
      });
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_mobile_nav_has_hamburger_toggle`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add mobile hamburger navigation menu"
```

---

### Task 4: Redesign avatar demo with hand-shape illustrations

**Files:**
- Modify: `resources/views/landing/partials/avatar-demo.blade.php` (full rewrite)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-band`, `.vx-eyebrow`, `.vx-h2`, `.vx-lead`, `.vx-sec-head`, `.vx-reveal` from earlier tasks.
- Removes: the old `.vx-arm-l`/`.vx-arm-r`/circle-head SVG structure and its keyframe animations — no other file references these classes.

- [ ] **Step 1: Write the failing test**

Replace `test_avatar_demo_is_labeled_as_concept_preview` in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_avatar_demo_uses_hand_shape_illustrations(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Concept preview', false);
        $response->assertSee('How are you?', false);
        $response->assertSee('What is your name?', false);
        $response->assertSee('vx-hand-1', false);
        $response->assertSee('vx-hand-2', false);
        $response->assertDontSee('vx-arm-l', false);
        $response->assertDontSee('vx-arm-r', false);
        $response->assertSee('not a verified Ugandan Sign Language', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_avatar_demo_uses_hand_shape_illustrations`
Expected: FAIL — `vx-hand-1`/`vx-hand-2` don't exist yet, `vx-arm-l`/`vx-arm-r` still present.

- [ ] **Step 3: Rewrite avatar-demo.blade.php**

Replace the full contents of `resources/views/landing/partials/avatar-demo.blade.php` with:

```blade
<section class="vx-section vx-band">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">See it in motion</div>
    <h2 class="vx-h2" style="color:#fff">The idea, previewed.</h2>
    <p class="vx-lead" style="margin:10px 0 8px">
      <span style="display:inline-block;background:var(--sign);color:#0B1020;font-family:var(--display);font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:4px 10px;border-radius:999px">Concept preview</span>
    </p>
    <p class="vx-lead vx-sec-head">
      An early look at how hand-shape signing could look — illustrative, not a verified Ugandan Sign Language rendering.
    </p>
    <div class="vx-avatar-demo" data-phrases="How are you?,What is your name?">
      <svg viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Hand-shape illustration">
        <g class="vx-hand vx-hand-1">
          <rect x="80" y="90" width="40" height="60" rx="18" fill="var(--sign)"/>
          <rect x="60" y="50" width="16" height="55" rx="8" fill="var(--sign)" transform="rotate(-8 68 78)"/>
          <rect x="80" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>
          <rect x="102" y="35" width="16" height="65" rx="8" fill="var(--sign)"/>
          <rect x="124" y="45" width="16" height="60" rx="8" fill="var(--sign)" transform="rotate(8 132 75)"/>
          <rect x="55" y="95" width="30" height="15" rx="7" fill="var(--sign)" transform="rotate(-35 70 102)"/>
        </g>
        <g class="vx-hand vx-hand-2">
          <rect x="80" y="100" width="42" height="55" rx="18" fill="var(--voice)"/>
          <rect x="90" y="40" width="18" height="70" rx="9" fill="var(--voice)"/>
          <rect x="60" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>
          <rect x="118" y="105" width="24" height="16" rx="8" fill="var(--voice)"/>
        </g>
      </svg>
      <p class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-hand{transition:opacity .5s ease}
  .vx-hand-2{opacity:0}
  .vx-avatar-demo.vx-phrase-2 .vx-hand-1{opacity:0}
  .vx-avatar-demo.vx-phrase-2 .vx-hand-2{opacity:1}
  @media(prefers-reduced-motion:reduce){.vx-hand{transition:none}}
</style>
<script>
  (function(){
    var demo = document.querySelector('.vx-avatar-demo');
    if (!demo) return;
    var phrases = demo.dataset.phrases.split(',');
    var caption = demo.querySelector('.vx-avatar-caption');
    var i = 0;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    setInterval(function(){
      i = (i + 1) % phrases.length;
      caption.textContent = phrases[i];
      demo.classList.toggle('vx-phrase-2', i === 1);
    }, 3200);
  })();
</script>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_avatar_demo_uses_hand_shape_illustrations`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/landing/partials/avatar-demo.blade.php tests/Feature/LandingPageTest.php
git commit -m "Redesign avatar demo: hand-shape illustrations replace generic circle-head figure"
```

---

### Task 5: Interaction polish — scroll-reveal easing, card hover, grid stagger

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php` (CSS only)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-reveal`, `.vx-card`, `.vx-grid` from earlier tasks/layout.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_interaction_polish_css_present(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('cubic-bezier(.16,1,.3,1)', false);
        $response->assertSee('.vx-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px;transition:transform .2s ease,box-shadow .2s ease}', false);
        $response->assertSee('.vx-card:hover{transform:translateY(-3px)', false);
        $response->assertSee('.vx-grid .vx-card:nth-child(1){transition-delay:0ms}', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_interaction_polish_css_present`
Expected: FAIL — none of this CSS exists yet.

- [ ] **Step 3: Update scroll-reveal easing**

In `resources/views/layouts/marketing.blade.php`, replace:

```css
  .vx-reveal{opacity:0;transform:translateY(14px);transition:opacity .6s ease,transform .6s ease}
```

with:

```css
  .vx-reveal{opacity:0;transform:translateY(14px);transition:opacity .7s cubic-bezier(.16,1,.3,1),transform .7s cubic-bezier(.16,1,.3,1)}
```

- [ ] **Step 4: Add card hover-lift**

Replace:

```css
  .vx-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px}
```

with:

```css
  .vx-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px;transition:transform .2s ease,box-shadow .2s ease}
  .vx-card:hover{transform:translateY(-3px);box-shadow:0 12px 24px -16px rgba(11,16,32,.25)}
  @media(prefers-reduced-motion:reduce){.vx-card{transition:none}.vx-card:hover{transform:none}}
```

- [ ] **Step 5: Add grid stagger**

Immediately after the `.vx-grid` rule, add:

```css
  .vx-grid .vx-card:nth-child(1){transition-delay:0ms}
  .vx-grid .vx-card:nth-child(2){transition-delay:50ms}
  .vx-grid .vx-card:nth-child(3){transition-delay:100ms}
  .vx-grid .vx-card:nth-child(4){transition-delay:150ms}
  .vx-grid .vx-card:nth-child(5){transition-delay:200ms}
  .vx-grid .vx-card:nth-child(n+6){transition-delay:250ms}
  @media(prefers-reduced-motion:reduce){.vx-grid .vx-card{transition-delay:0ms!important}}
```

Note: this `transition-delay` applies to `.vx-card`'s hover transition (Step 4), not to `.vx-reveal`'s scroll-in transition — `.vx-card` and `.vx-reveal` are applied to different elements in this codebase (`.vx-reveal` wraps a section's `.vx-wrap`, `.vx-card` is inside the grid), so staggering `.vx-card`'s own transition-delay doesn't affect scroll-reveal timing. This is a intentional, minor scope note: true scroll-reveal stagger (each card fading in individually with a delay) would require restructuring `.vx-reveal` to apply per-card rather than per-section-wrapper, which is a bigger change than this task's spec calls for. This task's stagger applies to hover-lift timing consistency across a grid, not entrance stagger — acceptable since the spec's given CSS for this step describes exactly this pattern.

- [ ] **Step 6: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_interaction_polish_css_present`
Expected: PASS

- [ ] **Step 7: Run the full LandingPageTest suite**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=LandingPageTest`
Expected: cannot execute due to the known local PostgreSQL limitation — verify via `artisan view:cache` instead and confirm no Blade syntax errors across all modified files.

- [ ] **Step 8: Commit**

```bash
git add resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add scroll-reveal easing, card hover-lift, and grid stagger for interaction polish"
```

---

### Task 6: Deploy to production and verify live

This task has no code changes — it deploys the branch built in Tasks 1–5 and verifies it live, following the same deploy pattern established for this project.

**Files:** None (deployment/verification only).

- [ ] **Step 1: Push to GitHub**

```bash
git push origin main
```

- [ ] **Step 2: Pull and deploy via cPanel Git Version Control**

In cPanel → Git Version Control → manage the Pearledu repository: click **"Update from Remote"**, then **"Deploy HEAD Commit"**.

- [ ] **Step 3: Verify the live page**

Visit `https://voxsign.co.ug/` in a browser and confirm:
- Clash Display and Satoshi fonts are loading (headings/body text visually distinct from the prior Bricolage/Atkinson pairing)
- Section spacing feels consistent and comfortable, not cramped, at both mobile and desktop widths
- On a narrow viewport (or browser dev tools mobile emulation), the hamburger icon appears, tapping it opens the nav menu, tapping a link closes it and scrolls to the section
- The avatar demo shows hand-shape illustrations (not the old circle-head stick figure), crossfading between two shapes in sync with the caption text, still labeled "Concept preview"
- Cards lift slightly on hover with a soft shadow
- Scrolling through sections feels smooth, not abrupt

Test with `prefers-reduced-motion` enabled (via OS accessibility settings or browser dev tools) and confirm: no jarring motion, avatar demo stays on the first hand shape, nav/cards still function without animation.
