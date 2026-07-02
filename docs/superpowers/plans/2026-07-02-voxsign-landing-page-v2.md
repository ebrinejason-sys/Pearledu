# VoxSign Landing Page v2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild `voxsign.co.ug`'s landing page in a light-mode visual system, restructure its content around VoxSign's two divisions (PearlEdu for institutions, VoxSign Accessibility for its two assistive products), remove pricing, add a stylized sign-language avatar demo, update the team roster, and fix the contact form's email flow.

**Architecture:** Same Laravel Blade structure as the existing page (`layouts/marketing.blade.php` + `landing/home.blade.php` + `landing/partials/*.blade.php`, driven by `LandingController`). No new dependencies, no JS build step — plain CSS/JS only. Contact form gains two Laravel Mailables replacing a raw `Mail::raw()` call.

**Tech Stack:** Laravel 13, Blade, plain CSS/JS (CSS custom properties, `IntersectionObserver` for scroll-reveal, CSS keyframe animations), PHPUnit feature tests.

## Global Constraints

- Light-mode is the site-wide theme. 1–2 dark navy (`--ink`) contrast bands are allowed (not a toggle, not a site-wide dark theme).
- Palette: `--ink:#0B1020; --paper:#FBFAF7; --surface:#FFFFFF; --voice:#FF6A3D; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC; --grad: linear-gradient(100deg,var(--voice),var(--sign));`
- Fonts: `Bricolage Grotesque` (display/headings), `Atkinson Hyperlegible` (body), loaded via Google Fonts.
- No pricing anywhere on the page (nav, sections, CTAs).
- No invented marketing copy beyond what's in this plan or the existing `project_voxsign_marketing_content` facts — real content only.
- Avatar demo must display a "Concept preview" label and must not claim linguistic accuracy — this is a hard requirement, not optional styling.
- `prefers-reduced-motion` must be respected by the partners marquee and the avatar demo animation.
- Contact form must send exactly two emails per successful submission (admin notification to `config('mail.contact_inbox')`, confirmation to the submitter) and must not regress the existing honeypot/validation/reply-to behavior.
- `CONTACT_INBOX` changes to `tusuubiravictor@gmail.com` (replaces `hello@voxsign.co.ug`).
- Reuse existing real assets in `public/images/voxsign/*` where photos exist; use an initials-fallback avatar only for team members without a photo.
- Test host for landing-page feature tests is `http://voxsign.co.ug/` (matches existing test file and `config/tenancy.php`'s default `landing_hosts`).

---

### Task 1: Rewrite the marketing layout to the new light-mode visual system

**Files:**
- Modify: `resources/views/layouts/marketing.blade.php` (full rewrite)
- Test: `tests/Feature/LandingPageTest.php` (new test method)

**Interfaces:**
- Produces: CSS custom properties `--ink`, `--paper`, `--surface`, `--voice`, `--sign`, `--muted`, `--line`, `--grad` and utility classes `vx-wrap`, `vx-nav`, `vx-section`, `vx-eyebrow`, `vx-h1`, `vx-h2`, `vx-lead`, `vx-btn`, `vx-btn-ghost`, `vx-grid`, `vx-card`, `vx-band` (new: dark contrast band), `vx-reveal` (new: scroll-reveal animation hook) — every later task's partials use these class names.
- Produces: global scroll-reveal script (`IntersectionObserver` on `.vx-reveal`) that later partials rely on by adding the `vx-reveal` class to elements they want to animate in.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`, replacing the existing `test_hero_renders_new_voxsign_copy` test body's dark-mode assertion (keep the method, change its content — this task doesn't touch hero copy yet, only the layout shell):

```php
    public function test_layout_uses_light_mode_palette_and_fonts(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('--paper:#FBFAF7', false);
        $response->assertSee('--voice:#FF6A3D', false);
        $response->assertSee('--sign:#12B3A6', false);
        $response->assertSee('Bricolage+Grotesque', false);
        $response->assertSee('Atkinson+Hyperlegible', false);
        $response->assertDontSee('--vx-bg:#0A0A0A', false);
        $response->assertDontSee('#pricing', false);
    }
```

Add this as a new method in the `LandingPageTest` class (don't remove any existing method yet — later tasks will update the others).

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_layout_uses_light_mode_palette_and_fonts`
Expected: FAIL — the old dark palette (`--vx-bg:#0A0A0A`) is still present, new values are not.

- [ ] **Step 3: Rewrite the layout file**

Replace the full contents of `resources/views/layouts/marketing.blade.php` with:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'VoxSign — Speak the Future. See It Signed.')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#0B1020; --paper:#FBFAF7; --surface:#FFFFFF;
    --voice:#FF6A3D; --sign:#12B3A6; --muted:#5D6473; --line:#E7E4DC;
    --grad:linear-gradient(100deg,var(--voice),var(--sign));
    --display:'Bricolage Grotesque',system-ui,sans-serif;
    --body:'Atkinson Hyperlegible',system-ui,sans-serif;
  }
  *{box-sizing:border-box} html,body{margin:0}
  html{scroll-behavior:smooth}
  @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  body{font-family:var(--body);background:var(--paper);color:var(--ink);line-height:1.6;-webkit-font-smoothing:antialiased}
  h1,h2,h3{font-family:var(--display);line-height:1.08;letter-spacing:-.02em;margin:0}
  a{color:inherit;text-decoration:none}
  .vx-wrap{max-width:1120px;margin:0 auto;padding:0 24px}
  :focus-visible{outline:3px solid var(--sign);outline-offset:3px;border-radius:4px}

  .vx-nav{position:sticky;top:0;z-index:50;display:flex;align-items:center;gap:20px;padding:16px 24px;
          background:rgba(251,250,247,.86);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
  .vx-nav img{height:28px;display:block}
  .vx-nav-links{margin-left:14px;display:flex;gap:22px;font-size:15px;color:var(--muted);flex-wrap:wrap}
  .vx-nav-links a:hover{color:var(--ink)}
  .vx-nav-cta{margin-left:auto}

  .vx-section{padding:72px 0;border-bottom:1px solid var(--line)}
  .vx-section:last-of-type{border-bottom:0}
  .vx-band{background:var(--ink);color:#fff}
  .vx-band .vx-eyebrow{color:var(--sign)}
  .vx-band .vx-lead{color:#aeb4c2}

  .vx-eyebrow{font-family:var(--display);font-size:12px;letter-spacing:.15em;color:var(--voice);font-weight:700;margin-bottom:14px;text-transform:uppercase}
  .vx-h1{font-size:clamp(32px,5.5vw,56px);font-weight:800;line-height:1.05;max-width:680px;margin:0 0 16px}
  .vx-h1 .vx-flow{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
  .vx-h2{font-size:clamp(24px,3.6vw,36px);font-weight:700;margin:0 0 6px}
  .vx-lead{color:var(--muted);max-width:620px;font-size:17px}

  .vx-btn{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
          background:var(--ink);color:#fff;border:1.5px solid var(--ink);border-radius:999px;padding:12px 22px;cursor:pointer;
          transition:transform .15s ease,box-shadow .2s ease}
  .vx-btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px rgba(11,16,32,.5)}
  .vx-btn-ghost{display:inline-flex;align-items:center;gap:8px;font-family:var(--display);font-weight:600;font-size:15px;
                background:transparent;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:12px 22px;cursor:pointer}
  .vx-btn-ghost:hover{border-color:var(--ink)}

  .vx-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .vx-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px}
  .vx-card img{width:100%;aspect-ratio:1;object-fit:cover;margin-bottom:12px;border-radius:12px}
  .vx-card h3{margin:0 0 6px;font-size:18px;font-weight:700}
  .vx-card p{margin:0;color:var(--muted);font-size:14px}

  .vx-steps{display:flex;gap:22px;flex-wrap:wrap}
  .vx-step{flex:1;min-width:180px}
  .vx-step-n{font-family:var(--display);font-size:24px;font-weight:800;color:var(--voice)}
  .vx-step h4{margin:8px 0;font-size:17px}
  .vx-step p{margin:0;color:var(--muted);font-size:14px}

  .vx-partners{overflow:hidden;position:relative}
  .vx-partner-text{color:var(--muted);font-size:13px;border:1px dashed var(--line);padding:10px 14px;border-radius:10px}
  .vx-quote{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px}
  .vx-quote p{margin:0 0 12px;font-size:16px}
  .vx-quote cite{color:var(--muted);font-size:13px;font-style:normal}

  .vx-input{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:inherit;margin-bottom:12px}
  .vx-input:focus{border-color:var(--sign);outline:none}
  .vx-label{display:block;font-family:var(--display);font-weight:600;font-size:13px;color:var(--ink);margin:0 0 6px}
  .vx-err{color:#D0392B;font-size:13px;margin:-8px 0 12px}
  .vx-status{background:#E9F7F5;border:1px solid var(--sign);color:#0B1020;padding:12px 16px;margin-bottom:16px;border-radius:12px;font-size:15px}

  .vx-footer{background:var(--ink);color:#c7cdda;padding:32px 24px;font-size:13px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px}
  .vx-footer a{color:#c7cdda}.vx-footer a:hover{color:#fff}
  @media(max-width:640px){.vx-footer{flex-direction:column}}

  .vx-reveal{opacity:0;transform:translateY(14px);transition:opacity .6s ease,transform .6s ease}
  .vx-reveal.in{opacity:1;transform:none}
  @media(prefers-reduced-motion:reduce){.vx-reveal{opacity:1;transform:none;transition:none}}
</style>
</head>
<body>
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
  @if(session('status'))
    <div class="vx-wrap" style="padding-top:20px"><div class="vx-status">{{ session('status') }}</div></div>
  @endif
  @yield('content')
  <div class="vx-footer">
    <span>&copy; {{ date('Y') }} VoxSign, Uganda</span>
    <span>+256 770 680769 &middot; voxsign3@gmail.com</span>
    <a href="https://pearledu.{{ config('tenancy.base_domain') }}">PearlEdu — school management →</a>
  </div>
  <script>
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, {threshold: .12});
    document.querySelectorAll('.vx-reveal').forEach(function(el){ io.observe(el); });
  </script>
</body>
</html>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_layout_uses_light_mode_palette_and_fonts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/marketing.blade.php tests/Feature/LandingPageTest.php
git commit -m "Rewrite marketing layout: light-mode palette, new fonts, scroll-reveal shell"
```

---

### Task 2: Rewrite hero to platform-level copy, remove mission partial

**Files:**
- Modify: `resources/views/landing/partials/hero.blade.php`
- Delete: `resources/views/landing/partials/mission.blade.php`
- Modify: `resources/views/landing/home.blade.php:4` (remove the mission include line)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-eyebrow`, `.vx-h1`, `.vx-flow`, `.vx-lead`, `.vx-btn`, `.vx-btn-ghost`, `.vx-reveal` from Task 1.

- [ ] **Step 1: Write the failing test**

Replace the existing `test_hero_renders_new_voxsign_copy` method in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_hero_renders_platform_level_copy(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Technology built to include everyone.', false);
        $response->assertSee('PearlEdu', false);
        $response->assertSee('VoxSign Accessibility', false);
        $response->assertSee('Talk to us');
        $response->assertDontSee('Communication gaps between hearing instructors', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_hero_renders_platform_level_copy`
Expected: FAIL — old hero copy ("Speak the Future. See It Signed.") is still present, new copy is not.

- [ ] **Step 3: Rewrite hero.blade.php**

Replace the full contents of `resources/views/landing/partials/hero.blade.php` with:

```blade
<section class="vx-section" style="padding-top:56px">
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

- [ ] **Step 4: Delete the mission partial**

```bash
rm resources/views/landing/partials/mission.blade.php
```

- [ ] **Step 5: Remove the mission include from home.blade.php**

In `resources/views/landing/home.blade.php`, remove this line:

```blade
  @include('landing.partials.mission')
```

- [ ] **Step 6: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_hero_renders_platform_level_copy`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add resources/views/landing/partials/hero.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git rm resources/views/landing/partials/mission.blade.php
git commit -m "Rewrite hero to platform-level copy naming both divisions, remove mission partial"
```

---

### Task 3: Convert partners into an animated marquee, move it to right after the hero

**Files:**
- Modify: `resources/views/landing/partials/partners.blade.php`
- Modify: `resources/views/landing/home.blade.php` (move the partners include to directly after hero)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `$partners` array from `LandingController::partners()` — unchanged shape (`['name' => ..., 'logo' => ...|null]`).
- Produces: `.vx-marquee` / `.vx-marquee-track` classes — no later task depends on these.

- [ ] **Step 1: Write the failing test**

Replace `test_partners_section_renders_logos_and_text_credits` in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_partners_marquee_renders_logos_and_text_credits(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('vx-marquee', false);
        $response->assertSee('images/voxsign/partner-unad.png', false);
        $response->assertSee('images/voxsign/partner-kyu.png', false);
        $response->assertSee('images/voxsign/partner-youtube.webp', false);
        $response->assertSee('images/voxsign/partner-4.jpg', false);
        $response->assertSee('Makerere University');
        $response->assertSee('Makerere Innovation and Incubation Centre');
    }

    public function test_partners_section_appears_directly_after_hero(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $heroPos = strpos($response->getContent(), 'Technology built to');
        $partnersPos = strpos($response->getContent(), 'vx-marquee');
        $howItWorksPos = strpos($response->getContent(), 'How it works');

        $this->assertNotFalse($heroPos);
        $this->assertNotFalse($partnersPos);
        $this->assertGreaterThan($heroPos, $partnersPos);
        $this->assertLessThan($howItWorksPos, $partnersPos);
    }
```

Remove the old `test_partners_section_renders_logos_and_text_credits` method (replaced above).

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=LandingPageTest`
Expected: FAIL on both new methods — `vx-marquee` class doesn't exist yet, and partners still sits after team, not right after hero.

- [ ] **Step 3: Rewrite partners.blade.php as a marquee**

Replace the full contents of `resources/views/landing/partials/partners.blade.php` with:

```blade
<div id="partners" style="padding:26px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="vx-wrap" style="margin-bottom:10px">
    <div class="vx-eyebrow" style="text-align:center">Working with and supported by</div>
  </div>
  <div class="vx-marquee">
    <div class="vx-marquee-track">
      @for($i = 0; $i < 2; $i++)
        <div class="vx-marquee-group">
          @foreach($partners as $partner)
            @if($partner['logo'])
              <img src="{{ asset('images/voxsign/'.$partner['logo']) }}" alt="{{ $partner['name'] }}">
            @else
              <span class="vx-partner-text">{{ $partner['name'] }}</span>
            @endif
          @endforeach
        </div>
      @endfor
    </div>
  </div>
</div>
<style>
  .vx-marquee{overflow:hidden;width:100%}
  .vx-marquee-track{display:flex;align-items:center;width:max-content;animation:vx-scroll 32s linear infinite}
  .vx-marquee-group{display:flex;align-items:center;gap:44px}
  .vx-marquee-track img{height:38px;width:auto;opacity:.8;filter:grayscale(1);transition:opacity .2s,filter .2s;flex:0 0 auto}
  .vx-marquee:hover .vx-marquee-track{animation-play-state:paused}
  .vx-marquee-track img:hover{opacity:1;filter:none}
  @keyframes vx-scroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}
  @media(prefers-reduced-motion:reduce){
    .vx-marquee-track{animation:none;flex-wrap:wrap;justify-content:center}
    .vx-marquee-track .vx-marquee-group:nth-child(2){display:none}
  }
</style>
```

The logo list is duplicated once (`@for($i = 0; $i < 2; $i++)`, each pass wrapped in its own `.vx-marquee-group`) so the CSS animation (`translateX(-50%)`) can loop seamlessly from the first copy into the identical second copy. `.vx-marquee{overflow:hidden}` clips the doubled track into a scrolling strip instead of an overflowing double-wide row. Under `prefers-reduced-motion: reduce`, the second `.vx-marquee-group` is hidden so non-animated users see each partner's logo/name exactly once, not duplicated. The gap lives only on `.vx-marquee-group`, not `.vx-marquee-track` — with no gap between the two groups, the track is exactly `2 × groupWidth`, so `translateX(-50%)` shifts by exactly one group's width for a truly seamless loop (a gap on the track itself would offset the loop restart point).

- [ ] **Step 4: Move the partners include to right after hero in home.blade.php**

In `resources/views/landing/home.blade.php`, remove the `@include('landing.partials.partners')` line from its current position (after `team`), and add it back immediately after `@include('landing.partials.hero')`. The file should read:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.partners')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=LandingPageTest`
Expected: PASS on all `LandingPageTest` methods run so far.

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/partners.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Convert partners strip into an animated marquee, move it directly after hero"
```

---

### Task 4: Add the "Two Divisions" intro section

**Files:**
- Create: `resources/views/landing/partials/divisions.blade.php`
- Modify: `resources/views/landing/home.blade.php` (insert include after partners, before how-it-works)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-wrap`, `.vx-eyebrow`, `.vx-h2`, `.vx-grid`, `.vx-card`, `.vx-btn-ghost`, `.vx-reveal` from Task 1.
- Produces: anchor targets `#pearledu` and `#accessibility` (as `<a href>` targets on the cards) — Tasks 5 and 6 give those IDs to their own `<section>` elements, which these links point to.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_two_divisions_section_introduces_pearledu_and_accessibility(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Two divisions, one mission', false);
        $response->assertSee('school management platform', false);
        $response->assertSee('href="#pearledu"', false);
        $response->assertSee('href="#accessibility"', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_two_divisions_section_introduces_pearledu_and_accessibility`
Expected: FAIL — `divisions.blade.php` doesn't exist yet, so this content isn't rendered.

- [ ] **Step 3: Create divisions.blade.php**

```blade
<section class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">What VoxSign is</div>
    <h2 class="vx-h2">Two divisions, one mission.</h2>
    <p class="vx-lead" style="margin-bottom:32px">Every VoxSign product exists to close a gap someone else's technology left open.</p>
    <div class="vx-grid" style="grid-template-columns:repeat(2,1fr)">
      <div class="vx-card">
        <h3>PearlEdu</h3>
        <p>A school management platform for institutions — attendance, grading, fees, and communication in one place.</p>
        <p style="margin-top:14px"><a href="#pearledu" class="vx-btn-ghost">Learn more ↓</a></p>
      </div>
      <div class="vx-card">
        <h3>VoxSign Accessibility</h3>
        <p>AI tools for Deaf, hard-of-hearing, and speech-impaired users — Ugandan Sign Language translation and assistive speech recognition.</p>
        <p style="margin-top:14px"><a href="#accessibility" class="vx-btn-ghost">Learn more ↓</a></p>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 4: Insert the include in home.blade.php**

In `resources/views/landing/home.blade.php`, insert `@include('landing.partials.divisions')` immediately after `@include('landing.partials.partners')` and before `@include('landing.partials.how-it-works')`:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.partners')
  @include('landing.partials.divisions')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_two_divisions_section_introduces_pearledu_and_accessibility`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/divisions.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add Two Divisions intro section linking to PearlEdu and VoxSign Accessibility"
```

---

### Task 5: Add the PearlEdu section

**Files:**
- Create: `resources/views/landing/partials/pearledu.blade.php`
- Modify: `resources/views/landing/home.blade.php` (insert include after divisions, before how-it-works)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-band`, `.vx-eyebrow`, `.vx-h2`, `.vx-lead`, `.vx-grid`, `.vx-card`, `.vx-reveal` from Task 1.
- Produces: `<section id="pearledu">` — the anchor target for Task 4's "Learn more ↓" link.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_pearledu_section_describes_institution_features(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('id="pearledu"', false);
        $response->assertSee('Attendance', false);
        $response->assertSee('Grading', false);
        $response->assertSee('Fees', false);
        $response->assertSee('Communication', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_pearledu_section_describes_institution_features`
Expected: FAIL — `pearledu.blade.php` doesn't exist yet.

- [ ] **Step 3: Create pearledu.blade.php**

```blade
<section id="pearledu" class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">For institutions</div>
    <h2 class="vx-h2">PearlEdu — school management, without the spreadsheets.</h2>
    <p class="vx-lead" style="margin-bottom:32px">Built for schools that need one system, not five disconnected ones.</p>
    <div class="vx-grid">
      <div class="vx-card"><h3>Attendance</h3><p>Track student attendance across classes without paper registers.</p></div>
      <div class="vx-card"><h3>Grading</h3><p>Record and report assessment results in one consistent system.</p></div>
      <div class="vx-card"><h3>Fees</h3><p>Manage school fee billing and payments, including mobile money.</p></div>
      <div class="vx-card"><h3>Communication</h3><p>Keep staff, parents, and administrators on the same page.</p></div>
    </div>
  </div>
</section>
```

- [ ] **Step 4: Insert the include in home.blade.php**

In `resources/views/landing/home.blade.php`, insert `@include('landing.partials.pearledu')` immediately after `@include('landing.partials.divisions')` and before `@include('landing.partials.how-it-works')`:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.partners')
  @include('landing.partials.divisions')
  @include('landing.partials.pearledu')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_pearledu_section_describes_institution_features`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/pearledu.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add PearlEdu section describing the institutions-facing product"
```

---

### Task 6: Add the VoxSign Accessibility section (two product sub-cards)

**Files:**
- Create: `resources/views/landing/partials/accessibility.blade.php`
- Modify: `resources/views/landing/home.blade.php` (insert include after pearledu, before how-it-works)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-eyebrow`, `.vx-h2`, `.vx-lead`, `.vx-grid`, `.vx-card`, `.vx-reveal` from Task 1.
- Produces: `<section id="accessibility">` — the anchor target for Task 4's "Learn more ↓" link.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_accessibility_section_describes_both_products(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('id="accessibility"', false);
        $response->assertSee('Ugandan Sign Language', false);
        $response->assertSee('non-standard speech', false);
        $response->assertSee('Whisper', false);
        $response->assertSee('speech impairments', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_accessibility_section_describes_both_products`
Expected: FAIL — `accessibility.blade.php` doesn't exist yet.

- [ ] **Step 3: Create accessibility.blade.php**

```blade
<section id="accessibility" class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">VoxSign Accessibility</div>
    <h2 class="vx-h2">Two products, one job: make sure everyone is understood.</h2>
    <p class="vx-lead" style="margin-bottom:32px">Communication gaps shut people out — at school, at work, in everyday conversation. These two products close them from opposite directions.</p>
    <div class="vx-grid" style="grid-template-columns:repeat(2,1fr)">
      <div class="vx-card">
        <h3>Ugandan Sign Language app</h3>
        <p>Converts spoken words into Ugandan Sign Language through a lifelike AI avatar, in real time — for classrooms, calls, and videos.</p>
      </div>
      <div class="vx-card">
        <h3>Assistive Speech Recognition</h3>
        <p>VoxSign's speech recognition tool helps people with speech impairments be understood. It's built by training OpenAI's Whisper speech-recognition model on non-standard speech patterns, so it can accurately recognize what someone is saying even when their speech doesn't follow typical patterns.</p>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 4: Insert the include in home.blade.php**

In `resources/views/landing/home.blade.php`, insert `@include('landing.partials.accessibility')` immediately after `@include('landing.partials.pearledu')` and before `@include('landing.partials.how-it-works')`:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.partners')
  @include('landing.partials.divisions')
  @include('landing.partials.pearledu')
  @include('landing.partials.accessibility')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_accessibility_section_describes_both_products`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/accessibility.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign Accessibility section covering both USL app and assistive ASR"
```

---

### Task 7: Add the sign-language avatar demo (stylized concept preview)

**Files:**
- Create: `resources/views/landing/partials/avatar-demo.blade.php`
- Modify: `resources/views/landing/home.blade.php` (insert include after accessibility, before how-it-works)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-band`, `.vx-eyebrow`, `.vx-h2`, `.vx-lead`, `.vx-reveal` from Task 1.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_avatar_demo_is_labeled_as_concept_preview(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Concept preview', false);
        $response->assertSee('How are you?', false);
        $response->assertSee('What is your name?', false);
        $response->assertSee('vx-avatar-demo', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_avatar_demo_is_labeled_as_concept_preview`
Expected: FAIL — `avatar-demo.blade.php` doesn't exist yet.

- [ ] **Step 3: Create avatar-demo.blade.php**

```blade
<section class="vx-section vx-band">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">See it in motion</div>
    <h2 class="vx-h2" style="color:#fff">The idea, previewed.</h2>
    <p class="vx-lead" style="margin:10px 0 8px">
      <span style="display:inline-block;background:var(--sign);color:#0B1020;font-family:var(--display);font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:4px 10px;border-radius:999px">Concept preview</span>
    </p>
    <p class="vx-lead" style="margin-bottom:32px">
      An early look at how a signing avatar could move — illustrative, not a verified Ugandan Sign Language rendering.
    </p>
    <div class="vx-avatar-demo" data-phrases="How are you?,What is your name?">
      <svg viewBox="0 0 200 200" width="180" height="180" role="img" aria-label="Animated avatar preview">
        <circle cx="100" cy="52" r="26" fill="none" stroke="var(--sign)" stroke-width="5"/>
        <line x1="100" y1="78" x2="100" y2="140" stroke="var(--sign)" stroke-width="6" stroke-linecap="round"/>
        <g class="vx-arm-l" style="transform-origin:100px 92px">
          <line x1="100" y1="92" x2="60" y2="130" stroke="var(--voice)" stroke-width="6" stroke-linecap="round"/>
        </g>
        <g class="vx-arm-r" style="transform-origin:100px 92px">
          <line x1="100" y1="92" x2="140" y2="130" stroke="var(--voice)" stroke-width="6" stroke-linecap="round"/>
        </g>
      </svg>
      <p class="vx-avatar-caption" style="font-family:var(--display);font-weight:700;font-size:20px;margin-top:14px">How are you?</p>
    </div>
  </div>
</section>
<style>
  .vx-avatar-demo{text-align:center}
  .vx-arm-l{animation:vx-arm-l 2.4s ease-in-out infinite}
  .vx-arm-r{animation:vx-arm-r 2.4s ease-in-out infinite}
  @keyframes vx-arm-l{0%,100%{transform:rotate(0deg)}50%{transform:rotate(-35deg)}}
  @keyframes vx-arm-r{0%,100%{transform:rotate(0deg)}50%{transform:rotate(35deg)}}
  @media(prefers-reduced-motion:reduce){.vx-arm-l,.vx-arm-r{animation:none}}
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
    }, 3200);
  })();
</script>
```

- [ ] **Step 4: Insert the include in home.blade.php**

In `resources/views/landing/home.blade.php`, insert `@include('landing.partials.avatar-demo')` immediately after `@include('landing.partials.accessibility')` and before `@include('landing.partials.how-it-works')`:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.partners')
  @include('landing.partials.divisions')
  @include('landing.partials.pearledu')
  @include('landing.partials.accessibility')
  @include('landing.partials.avatar-demo')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_avatar_demo_is_labeled_as_concept_preview`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/avatar-demo.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add stylized sign-language avatar demo, labeled as a concept preview"
```

---

### Task 8: Restyle How It Works and Features to the light-mode classes

**Files:**
- Modify: `resources/views/landing/partials/how-it-works.blade.php`
- Modify: `resources/views/landing/partials/features.blade.php`
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-eyebrow`, `.vx-h2`, `.vx-steps`, `.vx-step`, `.vx-step-n`, `.vx-grid`, `.vx-card`, `.vx-reveal` from Task 1. No class or content changes needed beyond adding `.vx-reveal` and a `.vx-h2` heading — these classes already existed pre-Task-1 with different values, so no HTML restructuring is required, only wrapping in `.vx-reveal` and adding a heading each partial was previously missing.

- [ ] **Step 1: Write the failing test**

Replace `test_mission_how_it_works_and_features_render` in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_how_it_works_and_features_render_with_headings(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('From spoken word to signed meaning', false);
        $response->assertSee('Speech captured live or recorded', false);
        $response->assertSee('Download', false);
        $response->assertSee('Create account', false);
        $response->assertSee('Tap Listen', false);
        $response->assertSee('Everything needed for real, everyday inclusion', false);
        $response->assertSee('Automatic voice recognition that accommodates varied accents', false);
        $response->assertSee('Multi-Device Accessibility', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_how_it_works_and_features_render_with_headings`
Expected: FAIL — the new heading copy ("From spoken word to signed meaning", "Everything needed for real, everyday inclusion") isn't present yet.

- [ ] **Step 3: Update how-it-works.blade.php**

Replace the full contents of `resources/views/landing/partials/how-it-works.blade.php` with:

```blade
<section id="how-it-works" class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">How it works</div>
    <h2 class="vx-h2" style="margin-bottom:28px">From spoken word to signed meaning, instantly.</h2>
    <div class="vx-steps" style="margin-bottom:36px">
      <div class="vx-step">
        <div class="vx-step-n">1</div>
        <h4>Speech in</h4>
        <p>Speech captured live or recorded from a teacher or speaker talking normally in class.</p>
      </div>
      <div class="vx-step">
        <div class="vx-step-n">2</div>
        <h4>AI translates</h4>
        <p>Automatic Speech Recognition transcribes the speech, then maps it to Ugandan Sign Language gestures.</p>
      </div>
      <div class="vx-step">
        <div class="vx-step-n">3</div>
        <h4>Signed output</h4>
        <p>Gestures are rendered as an animated avatar in real time for students to follow.</p>
      </div>
    </div>
    <div class="vx-eyebrow">How you'll use it</div>
    <div class="vx-steps">
      <div class="vx-step">
        <div class="vx-step-n">1</div>
        <h4>Download</h4>
      </div>
      <div class="vx-step">
        <div class="vx-step-n">2</div>
        <h4>Create account</h4>
      </div>
      <div class="vx-step">
        <div class="vx-step-n">3</div>
        <h4>Tap Listen</h4>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 4: Update features.blade.php**

Replace the full contents of `resources/views/landing/partials/features.blade.php` with:

```blade
<section class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Features</div>
    <h2 class="vx-h2" style="margin-bottom:28px">Everything needed for real, everyday inclusion.</h2>
    <div class="vx-grid">
      <div class="vx-card"><h3>Automatic voice recognition</h3><p>Automatic voice recognition that accommodates varied accents.</p></div>
      <div class="vx-card"><h3>Seamless collaboration</h3><p>Works alongside tools like YouTube and Zoom.</p></div>
      <div class="vx-card"><h3>Simple 3-step process</h3><p>Download, create an account, tap Listen.</p></div>
      <div class="vx-card"><h3>Multi-Device Accessibility</h3><p>Use VoxSign across phone, tablet, and desktop.</p></div>
    </div>
  </div>
</section>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_how_it_works_and_features_render_with_headings`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/how-it-works.blade.php resources/views/landing/partials/features.blade.php tests/Feature/LandingPageTest.php
git commit -m "Restyle How It Works and Features with light-mode headings"
```

---

### Task 9: Update team roster and add initials-fallback avatars

**Files:**
- Modify: `app/Http/Controllers/LandingController.php:16-25` (the `team()` method)
- Modify: `resources/views/landing/partials/team.blade.php`
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `team()` array items may now have `'photo' => null` — `team.blade.php` must handle both cases.

- [ ] **Step 1: Write the failing test**

Replace `test_team_section_renders_all_six_members` in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_team_section_renders_updated_roster(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Tusuubira Victor');
        $response->assertSee('CEO/Founder');
        $response->assertSee('Kamanzi Ahmed');
        $response->assertSee('Muwanguzi Joan Najjingo');
        $response->assertSee('Muhumuza Alex');
        $response->assertSee('Naikambo Sandra');
        $response->assertSee('Aaron Marshall Taremwa');
        $response->assertSee('Ebrine Tushabe');
        $response->assertSee('Product Development Expert');
        $response->assertSee('vx-avatar-initials', false);
        $response->assertSee('images/voxsign/team-victor.jpg', false);
        $response->assertDontSee('Oyoka Daniel');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_team_section_renders_updated_roster`
Expected: FAIL — Oyoka Daniel is still present, the two new members are not, `vx-avatar-initials` doesn't exist yet.

- [ ] **Step 3: Update LandingController::team()**

In `app/Http/Controllers/LandingController.php`, replace the `team()` method (currently lines 16-25) with:

```php
    private function team(): array {
        return [
            ['name' => 'Tusuubira Victor', 'role' => 'CEO/Founder', 'photo' => 'team-victor.jpg'],
            ['name' => 'Kamanzi Ahmed', 'role' => 'Head of Marketing and Operations', 'photo' => 'team-kamanzi.jpg'],
            ['name' => 'Muwanguzi Joan Najjingo', 'role' => 'Finance and Sales Manager', 'photo' => 'team-joan.jpg'],
            ['name' => 'Muhumuza Alex', 'role' => 'Head of Product Development', 'photo' => 'team-alex.jpg'],
            ['name' => 'Naikambo Sandra', 'role' => 'Sign Language Specialist and Consultant', 'photo' => 'team-sandra.jpg'],
            ['name' => 'Aaron Marshall Taremwa', 'role' => 'Product Development Expert', 'photo' => null],
            ['name' => 'Ebrine Tushabe', 'role' => 'Product Development Expert', 'photo' => null],
        ];
    }
```

- [ ] **Step 4: Update team.blade.php with an initials fallback**

Replace the full contents of `resources/views/landing/partials/team.blade.php` with:

```blade
<section id="team" class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Team</div>
    <h2 class="vx-h2" style="margin-bottom:28px">The people behind VoxSign.</h2>
    <div class="vx-grid">
      @foreach($team as $member)
        <div class="vx-card">
          @if($member['photo'])
            <img src="{{ asset('images/voxsign/'.$member['photo']) }}" alt="{{ $member['name'] }}">
          @else
            <div class="vx-avatar-initials" aria-hidden="true" style="aspect-ratio:1;border-radius:12px;margin-bottom:12px;background:linear-gradient(160deg,#e9efee,#dfe7e6);display:grid;place-items:center;font-family:var(--display);font-weight:800;font-size:36px;color:var(--sign)">
              {{ collect(explode(' ', $member['name']))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
            </div>
          @endif
          <h3>{{ $member['name'] }}</h3>
          <p>{{ $member['role'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_team_section_renders_updated_roster`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LandingController.php resources/views/landing/partials/team.blade.php tests/Feature/LandingPageTest.php
git commit -m "Update team roster: remove Oyoka Daniel, add Aaron Marshall Taremwa and Ebrine Tushabe"
```

---

### Task 10: Restyle Testimonials and Roadmap to light-mode classes

**Files:**
- Modify: `resources/views/landing/partials/testimonials.blade.php`
- Modify: `resources/views/landing/partials/roadmap.blade.php`
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-eyebrow`, `.vx-h2`, `.vx-quote`, `.vx-reveal` from Task 1. No content changes — same real testimonial/roadmap copy, only class/heading additions.

- [ ] **Step 1: Write the failing test**

Update `test_testimonials_render_in_anticipatory_tense` in place, add a new
`test_roadmap_section_renders` method, and rename
`test_roadmap_and_contact_section_render` to `test_contact_section_renders`
(removing only its now-redundant `expansion across Africa` assertion — that
method also covers contact-section content, i.e. phone/email/honeypot/
legal-page-absence, that lives nowhere else in this file, so it must be
trimmed, not deleted):

```php
    public function test_testimonials_render_in_anticipatory_tense(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('What future users are saying', false);
        $response->assertSee("I can't wait to try VoxSign!");
        $response->assertSee('Birabwa Jane Lydia');
        $response->assertSee("I'm really looking forward to VoxSign's launch.");
        $response->assertDontSee('I love using VoxSign');
    }

    public function test_roadmap_section_renders(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('The road ahead', false);
        $response->assertSee('expansion across Africa', false);
    }

    public function test_contact_section_renders(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('+256 770 680769');
        $response->assertSee('voxsign3@gmail.com');
        $response->assertSee('Makerere Innovation and Incubation Centre');
        $response->assertSee('name="website"', false); // honeypot field preserved
        $response->assertDontSee('Accessibility Statement');
        $response->assertDontSee('Privacy Policy');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=LandingPageTest`
Expected: FAIL on the two new/changed methods — the new headings aren't present yet.

- [ ] **Step 3: Update testimonials.blade.php**

Replace the full contents of `resources/views/landing/partials/testimonials.blade.php` with:

```blade
<section class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Early voices</div>
    <h2 class="vx-h2" style="margin-bottom:28px">What future users are saying.</h2>
    @foreach($testimonials as $t)
      <div class="vx-quote">
        <p>&ldquo;{{ $t['quote'] }}&rdquo;</p>
        <cite>{{ $t['name'] }} — {{ $t['role'] }}</cite>
      </div>
    @endforeach
  </div>
</section>
```

- [ ] **Step 4: Update roadmap.blade.php**

Replace the full contents of `resources/views/landing/partials/roadmap.blade.php` with:

```blade
<section class="vx-section">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Where we're headed</div>
    <h2 class="vx-h2" style="margin-bottom:20px">The road ahead.</h2>
    <p class="vx-lead" style="max-width:640px">expansion across Africa to support multiple sign languages, integration with government and education systems, offline and wearable translation devices, a global multilingual sign-language AI model, and community programs employing Deaf individuals.</p>
  </div>
</section>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=LandingPageTest`
Expected: PASS on all methods run so far.

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/partials/testimonials.blade.php resources/views/landing/partials/roadmap.blade.php tests/Feature/LandingPageTest.php
git commit -m "Restyle Testimonials and Roadmap with light-mode headings"
```

---

### Task 11: Remove pricing entirely

**Files:**
- Delete: `resources/views/landing/partials/pricing.blade.php`
- Modify: `resources/views/landing/home.blade.php` (remove the pricing include)
- Modify: `app/Http/Controllers/LandingController.php` (remove `pricingTiers()` method and its use in `index()`)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Removes: `pricingTiers()` — no other task depends on it (verified: no other partial/controller references `pricingTiers`).

- [ ] **Step 1: Write the failing test**

Replace `test_pricing_table_renders_all_four_tiers` in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_pricing_is_not_present_anywhere_on_the_page(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertDontSee('Pricing');
        $response->assertDontSee('UGX 0');
        $response->assertDontSee('UGX 50,000');
        $response->assertDontSee('#pricing', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_pricing_is_not_present_anywhere_on_the_page`
Expected: FAIL — pricing content is still present.

- [ ] **Step 3: Delete pricing.blade.php and remove its include**

```bash
rm resources/views/landing/partials/pricing.blade.php
```

In `resources/views/landing/home.blade.php`, remove this line:

```blade
  @include('landing.partials.pricing')
```

The file should now read:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.partners')
  @include('landing.partials.divisions')
  @include('landing.partials.pearledu')
  @include('landing.partials.accessibility')
  @include('landing.partials.avatar-demo')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 4: Remove pricingTiers() from LandingController**

In `app/Http/Controllers/LandingController.php`:

1. Remove the `pricingTiers()` private method entirely (the method returning the 4-tier array).
2. In `index()`, remove the `'pricingTiers' => $this->pricingTiers(),` line from the array passed to the view.

`index()` should read:

```php
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
            'partners' => $this->partners(),
            'testimonials' => $this->testimonials(),
        ]);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_pricing_is_not_present_anywhere_on_the_page`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/landing/home.blade.php app/Http/Controllers/LandingController.php tests/Feature/LandingPageTest.php
git rm resources/views/landing/partials/pricing.blade.php
git commit -m "Remove pricing section and pricingTiers() entirely"
```

---

### Task 12: Fix contact form email flow (admin notification + submitter confirmation) and restyle the form

**Files:**
- Create: `app/Mail/ContactFormReceived.php`
- Create: `app/Mail/ContactFormConfirmation.php`
- Create: `resources/views/emails/contact-form-received.blade.php`
- Create: `resources/views/emails/contact-form-confirmation.blade.php`
- Modify: `app/Http/Controllers/LandingController.php:67-80` (the `contact()` method)
- Modify: `resources/views/landing/partials/contact.blade.php`
- Modify: `phpunit.xml` (add `CONTACT_INBOX` test env var)
- Test: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `App\Mail\ContactFormReceived::__construct(string $name, string $email, string $message)`, `App\Mail\ContactFormConfirmation::__construct(string $name)` — no other task depends on these.

- [ ] **Step 1: Add CONTACT_INBOX to the test environment**

In `phpunit.xml`, add this line inside the `<php>` block (after the `MAIL_MAILER` line):

```xml
        <env name="CONTACT_INBOX" value="tusuubiravictor@gmail.com"/>
```

Without this, `config('mail.contact_inbox')` is `null` in tests, which is exactly the bug already fixed for production — the new Mailable-based test below would otherwise crash the same way.

- [ ] **Step 2: Write the failing test**

Replace `test_contact_form_still_validates_and_submits` in `tests/Feature/LandingPageTest.php` with:

```php
    public function test_contact_form_sends_admin_notification_and_submitter_confirmation(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello VoxSign',
            'website' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactFormReceived::class, function ($mail) {
            return $mail->hasTo('tusuubiravictor@gmail.com')
                && $mail->name === 'Test User'
                && $mail->email === 'test@example.com'
                && $mail->message === 'Hello VoxSign';
        });

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactFormConfirmation::class, function ($mail) {
            return $mail->hasTo('test@example.com') && $mail->name === 'Test User';
        });
    }

    public function test_contact_form_still_validates_required_fields(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'message' => '',
            'website' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }
```

- [ ] **Step 3: Run test to verify it fails**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=test_contact_form_sends_admin_notification_and_submitter_confirmation`
Expected: FAIL — `App\Mail\ContactFormReceived` and `App\Mail\ContactFormConfirmation` don't exist yet.

- [ ] **Step 4: Create the admin notification Mailable**

Create `app/Mail/ContactFormReceived.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'VoxSign contact form',
            replyTo: [new Address($this->email, $this->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-received',
            // Laravel's Mailer unconditionally injects its own $message view
            // variable (an Illuminate\Mail\Message wrapper) into every mail
            // view, which would otherwise shadow this Mailable's own $message
            // string property. Remap it so the Blade view can render the
            // actual contact form message.
            with: ['messageBody' => $this->message],
        );
    }
}
```

- [ ] **Step 5: Create the submitter confirmation Mailable**

Create `app/Mail/ContactFormConfirmation.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your message — VoxSign',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-confirmation',
        );
    }
}
```

- [ ] **Step 6: Create the two email Blade views**

Create `resources/views/emails/contact-form-received.blade.php`:

```blade
<p>New message from the VoxSign contact form:</p>
<p><strong>Name:</strong> {{ $name }}<br>
<strong>Email:</strong> {{ $email }}</p>
<p>{{ $messageBody }}</p>
```

Create `resources/views/emails/contact-form-confirmation.blade.php`:

```blade
<p>Hi {{ $name }},</p>
<p>Thanks for reaching out to VoxSign — your message has been delivered and we'll be in touch with you soon.</p>
<p>On behalf of VoxSign,<br>The VoxSign team</p>
```

- [ ] **Step 7: Update LandingController::contact()**

In `app/Http/Controllers/LandingController.php`, replace the `contact()` method with:

```php
    public function contact(Request $request) {
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'email'=>'required|email|max:160',
            'message'=>'required|string|max:2000',
            'website'=>'nullable|max:0',   // honeypot: must be empty
        ]);
        Mail::to(config('mail.contact_inbox'))
            ->send(new \App\Mail\ContactFormReceived($data['name'], $data['email'], $data['message']));
        Mail::to($data['email'])
            ->send(new \App\Mail\ContactFormConfirmation($data['name']));
        return back()->with('status', 'Thanks — we will be in touch shortly.');
    }
```

- [ ] **Step 8: Restyle contact.blade.php**

Replace the full contents of `resources/views/landing/partials/contact.blade.php` with:

```blade
<section id="contact" class="vx-section vx-band" style="border-bottom:0">
  <div class="vx-wrap vx-reveal">
    <div class="vx-eyebrow">Get in touch</div>
    <h2 class="vx-h2" style="color:#fff;margin-bottom:12px">Talk to us.</h2>
    <p class="vx-lead" style="margin-bottom:24px">
      +256 770 680769 &middot; voxsign3@gmail.com &middot; Makerere Innovation and Incubation Centre
    </p>
    <form method="post" action="{{ route('contact') }}" style="max-width:480px;background:#fff;border-radius:20px;padding:26px">
      @csrf
      <div style="position:absolute;left:-9999px"><input name="website" tabindex="-1" autocomplete="off"></div>
      <label class="vx-label">Name</label>
      <input class="vx-input" name="name" required>
      @error('name')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label">Email</label>
      <input class="vx-input" name="email" type="email" required>
      @error('email')<div class="vx-err">{{ $message }}</div>@enderror
      <label class="vx-label">Message</label>
      <textarea class="vx-input" name="message" rows="4" required></textarea>
      @error('message')<div class="vx-err">{{ $message }}</div>@enderror
      <button class="vx-btn" type="submit" style="width:100%;justify-content:center">Send message</button>
    </form>
  </div>
</section>
```

- [ ] **Step 9: Run test to verify it passes**

Run: `"/c/Users/ebrin/Desktop/pearledu-repo/.php84/php.exe" artisan test --filter=LandingPageTest`
Expected: PASS on all `LandingPageTest` methods.

- [ ] **Step 10: Commit**

```bash
git add app/Mail/ContactFormReceived.php app/Mail/ContactFormConfirmation.php resources/views/emails/contact-form-received.blade.php resources/views/emails/contact-form-confirmation.blade.php app/Http/Controllers/LandingController.php resources/views/landing/partials/contact.blade.php phpunit.xml tests/Feature/LandingPageTest.php
git commit -m "Add admin notification + submitter confirmation emails to contact form, restyle form"
```

---

### Task 13: Deploy to production and verify live

This task has no code changes — it deploys the branch built in Tasks 1–12 and verifies it live, following the same deploy pattern established for this project (see `project_deployment_setup` memory).

**Files:** None (deployment/verification only).

- [ ] **Step 1: Push to GitHub**

```bash
git push origin main
```

- [ ] **Step 2: Update CONTACT_INBOX in the server's .env**

In cPanel Terminal:

```
sed -i 's/^CONTACT_INBOX=.*/CONTACT_INBOX="tusuubiravictor@gmail.com"/' /home/voxsign/pearledu-app/.env
```

If the `sed` command reports no match (the line doesn't exist yet), instead append it:

```
echo 'CONTACT_INBOX="tusuubiravictor@gmail.com"' >> /home/voxsign/pearledu-app/.env
```

- [ ] **Step 3: Pull and deploy via cPanel Git Version Control**

In cPanel → Git Version Control → manage the Pearledu repository: click **"Update from Remote"**, then **"Deploy HEAD Commit"** (this re-runs `.cpanel.yml`'s copy + composer install + cache rebuild steps).

- [ ] **Step 4: Rebuild caches on the server (in case the deploy step's cache rebuild ran before the .env edit)**

In cPanel Terminal:

```
cd /home/voxsign/pearledu-app && /opt/alt/php84/usr/bin/php artisan config:clear && /opt/alt/php84/usr/bin/php artisan config:cache
```

- [ ] **Step 5: Verify the live page**

Visit `https://voxsign.co.ug/` in a browser and confirm:
- Light-mode page loads (paper background, orange/teal accents, Bricolage Grotesque headings)
- No pricing section or nav link anywhere
- "Two divisions" section links to working PearlEdu and VoxSign Accessibility sections
- Avatar demo section shows the "Concept preview" label and animates (or shows a static frame with `prefers-reduced-motion` enabled)
- Team section shows Aaron Marshall Taremwa and Ebrine Tushabe with initials avatars, no Oyoka Daniel
- Partners logos scroll in a loop and pause on hover

Submit the contact form with a real, reachable test email address and confirm:
- The page shows the "Thanks — we will be in touch shortly" status message
- `tusuubiravictor@gmail.com` receives the admin notification email
- The test email address receives the confirmation email
