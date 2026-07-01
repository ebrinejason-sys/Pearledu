# VoxSign Landing Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the bare-scaffold VoxSign landing page (currently borrowing PearlEdu's internal app chrome) with a standalone, real-content marketing page at the `voxsign.co.ug` apex.

**Architecture:** A new standalone `layouts/marketing.blade.php` (Bold & Accessible palette, VoxSign-only nav/footer) replaces PearlEdu's `layouts/app.blade.php` for this one route only. `landing/home.blade.php` becomes a thin shell that `@include`s ten focused partials in order. `LandingController@index` builds structured PHP arrays (team, partners, testimonials, pricing) and passes them to the view — no new Eloquent models, no migrations, this is static marketing content.

**Tech Stack:** Laravel 13 Blade views, inline `<style>` (no new build tooling, matches the existing app's convention), PHPUnit feature tests hitting the landing route by host header.

## Global Constraints

- Source of truth for all content/structure decisions: `docs/superpowers/specs/2026-07-01-voxsign-landing-design.md`. Do not invent copy — every string in this plan is copied from that spec.
- Visual palette is fixed to the CSS variables from the spec: `--vx-bg:#0A0A0A; --vx-surface:#141414; --vx-ink:#FFFFFF; --vx-muted:#B3B3B3; --vx-accent:#FFD400; --vx-accent-ink:#0A0A0A; --vx-line:#2A2A2A; --vx-radius:4px`.
- Do NOT modify `config/themes.php`, `resources/views/layouts/app.blade.php`, or any tenant-facing view — this work is scoped entirely to the marketing/landing page.
- Do NOT add "Accessibility Statement" / "Privacy Policy" footer links (no such pages exist yet — explicitly out of scope per spec).
- Do NOT add a "Download the app" link (no confirmed app-store URL exists).
- Existing contact form validation/honeypot logic in `LandingController@contact` must remain byte-for-byte unchanged — only its visual wrapper changes.
- Real media assets already exist at `public/images/voxsign/` (committed in an earlier commit) — reference them by filename, do not re-download.
- Route wiring in `routes/web.php` (the `is_landing` attribute check) is already correct — do not change it.

---

### Task 1: Marketing layout + Hero section

**Files:**
- Create: `resources/views/layouts/marketing.blade.php`
- Create: `resources/views/landing/partials/hero.blade.php`
- Modify: `resources/views/landing/home.blade.php`
- Create: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: layout `layouts.marketing` with a `@yield('content')` section, usable by any future view via `@extends('layouts.marketing')`.
- Produces: CSS classes available to all later partials: `.vx-wrap`, `.vx-section`, `.vx-eyebrow`, `.vx-h1`, `.vx-lead`, `.vx-btn`, `.vx-btn-ghost`, `.vx-grid`, `.vx-card`, `.vx-steps`, `.vx-step`, `.vx-step-n`, `.vx-partners`, `.vx-partner-text`, `.vx-quote`, `.vx-table`, `.vx-input`, `.vx-label`, `.vx-err`, `.vx-status`, `.vx-footer`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LandingPageTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_hero_renders_new_voxsign_copy(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Speak the Future. See It Signed.');
        $response->assertSee('Record, Transcribe, Collaborate. Effortlessly with', false);
        $response->assertSee('Get in touch');
        $response->assertDontSee('Software, built deliberately.');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: FAIL — the current `landing/home.blade.php` doesn't contain "Speak the Future. See It Signed." (it still has the old "Software, built deliberately." pitch).

- [ ] **Step 3: Create the marketing layout**

Create `resources/views/layouts/marketing.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'VoxSign — Speak the Future. See It Signed.')</title>
<style>
  :root{
    --vx-bg:#0A0A0A; --vx-surface:#141414; --vx-ink:#FFFFFF; --vx-muted:#B3B3B3;
    --vx-accent:#FFD400; --vx-accent-ink:#0A0A0A; --vx-line:#2A2A2A; --vx-radius:4px;
  }
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:system-ui,'Segoe UI',sans-serif;background:var(--vx-bg);color:var(--vx-ink);line-height:1.6}
  a{color:inherit;text-decoration:none}
  .vx-wrap{max-width:1100px;margin:0 auto;padding:0 28px}
  .vx-nav{display:flex;align-items:center;padding:18px 28px;border-bottom:2px solid var(--vx-accent)}
  .vx-nav img{height:28px;display:block}
  .vx-nav-links{margin-left:auto;display:flex;gap:20px;font-size:14px;color:var(--vx-muted);flex-wrap:wrap}
  .vx-nav-links a:hover{color:var(--vx-ink)}
  .vx-section{padding:48px 0;border-bottom:1px solid var(--vx-line)}
  .vx-eyebrow{font-size:12px;letter-spacing:.15em;color:var(--vx-accent);font-weight:700;margin-bottom:14px;text-transform:uppercase}
  .vx-h1{font-size:clamp(28px,5vw,40px);font-weight:900;line-height:1.15;max-width:620px;margin:0 0 14px}
  .vx-h2{font-size:22px;font-weight:900;margin:0 0 4px}
  .vx-lead{color:var(--vx-muted);max-width:560px;font-size:16px}
  .vx-btn{display:inline-block;background:var(--vx-accent);color:var(--vx-accent-ink);border:0;border-radius:var(--vx-radius);padding:12px 22px;font-weight:800;font-size:14px;cursor:pointer}
  .vx-btn-ghost{color:var(--vx-muted);border-bottom:1px solid var(--vx-line);padding-bottom:2px;font-size:14px}
  .vx-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
  .vx-card{background:var(--vx-surface);border:1px solid var(--vx-line);padding:16px}
  .vx-card img{width:100%;aspect-ratio:1;object-fit:cover;margin-bottom:10px;border-radius:var(--vx-radius)}
  .vx-card h3{margin:0 0 4px;font-size:15px;font-weight:800}
  .vx-card p{margin:0;color:var(--vx-muted);font-size:13px}
  .vx-steps{display:flex;gap:20px;flex-wrap:wrap}
  .vx-step{flex:1;min-width:160px}
  .vx-step-n{font-size:22px;font-weight:900;color:var(--vx-accent)}
  .vx-step h4{margin:6px 0;font-size:15px}
  .vx-step p{margin:0;color:var(--vx-muted);font-size:13px}
  .vx-partners{display:flex;gap:28px;flex-wrap:wrap;align-items:center}
  .vx-partners img{max-height:48px;filter:grayscale(1) brightness(2);opacity:.85}
  .vx-partner-text{color:var(--vx-muted);font-size:13px;border:1px dashed var(--vx-line);padding:10px 14px}
  .vx-quote{background:var(--vx-surface);border:1px solid var(--vx-line);padding:18px;margin-bottom:14px}
  .vx-quote p{margin:0 0 10px;font-size:14px}
  .vx-quote cite{color:var(--vx-muted);font-size:13px;font-style:normal}
  table.vx-table{width:100%;border-collapse:collapse;font-size:14px}
  table.vx-table th,table.vx-table td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--vx-line)}
  table.vx-table th{color:var(--vx-muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em}
  .vx-input{width:100%;padding:10px;border:1px solid var(--vx-line);border-radius:var(--vx-radius);background:var(--vx-surface);color:var(--vx-ink);font:inherit;margin-bottom:10px}
  .vx-label{display:block;font-size:13px;color:var(--vx-muted);margin:0 0 4px}
  .vx-err{color:#FF6B6B;font-size:13px;margin:-6px 0 10px}
  .vx-status{background:#1A2E1A;border:1px solid #2E5E2E;padding:10px 14px;margin-bottom:14px;font-size:14px}
  .vx-footer{padding:22px 28px;color:var(--vx-muted);font-size:12px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
  @media(max-width:640px){.vx-footer{flex-direction:column}}
</style>
</head>
<body>
  <div class="vx-nav">
    <a href="{{ url('/') }}"><img src="{{ asset('images/voxsign/voxsign-logo.png') }}" alt="VoxSign"></a>
    <div class="vx-nav-links">
      <a href="#how-it-works">How it works</a>
      <a href="#team">Team</a>
      <a href="#partners">Partners</a>
      <a href="#pricing">Pricing</a>
      <a href="#contact">Contact</a>
    </div>
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
</body>
</html>
```

- [ ] **Step 4: Create the hero partial**

Create `resources/views/landing/partials/hero.blade.php`:

```blade
<section class="vx-section" style="border-bottom-color:var(--vx-accent);border-bottom-width:2px;padding-top:56px">
  <div class="vx-wrap">
    <div class="vx-eyebrow">AI &middot; Ugandan Sign Language</div>
    <h1 class="vx-h1">Speak the Future. See It Signed.</h1>
    <p class="vx-lead">Record, Transcribe, Collaborate. Effortlessly with VoxSign's AI avatar tool.</p>
    <p style="margin-top:22px">
      <a href="#contact" class="vx-btn">Get in touch</a>
      <a href="#how-it-works" class="vx-btn-ghost" style="margin-left:16px">See how it works ↓</a>
    </p>
  </div>
</section>
```

- [ ] **Step 5: Replace the home view with a thin shell**

Replace the full contents of `resources/views/landing/home.blade.php` with:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/marketing.blade.php resources/views/landing/partials/hero.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add standalone VoxSign marketing layout and hero section"
```

---

### Task 2: Mission, How it works, and Key features

**Files:**
- Create: `resources/views/landing/partials/mission.blade.php`
- Create: `resources/views/landing/partials/how-it-works.blade.php`
- Create: `resources/views/landing/partials/features.blade.php`
- Modify: `resources/views/landing/home.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `.vx-section`, `.vx-wrap`, `.vx-eyebrow`, `.vx-h2`, `.vx-lead`, `.vx-steps`, `.vx-step`, `.vx-step-n`, `.vx-grid`, `.vx-card` from Task 1's layout.

- [ ] **Step 1: Write the failing test**

Add this method to `tests/Feature/LandingPageTest.php` (inside the class, alongside the existing test method):

```php
    public function test_mission_how_it_works_and_features_render(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('inclusive learning, accessibility, and collaboration', false);
        $response->assertSee('Speech captured live or recorded', false);
        $response->assertSee('Download', false);
        $response->assertSee('Create account', false);
        $response->assertSee('Tap Listen', false);
        $response->assertSee('Automatic voice recognition that accommodates varied accents', false);
        $response->assertSee('Multi-Device Accessibility', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: FAIL — `test_mission_how_it_works_and_features_render` fails because none of this content exists yet.

- [ ] **Step 3: Create the mission partial**

Create `resources/views/landing/partials/mission.blade.php`:

```blade
<section class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Our mission</div>
    <p class="vx-lead" style="max-width:640px;color:#ddd">
      Communication gaps between hearing instructors and Deaf or hard-of-hearing
      learners shut students out of the classroom conversation across Uganda.
      VoxSign converts speech into Ugandan Sign Language using AI avatars,
      built for inclusive learning, accessibility, and collaboration.
    </p>
  </div>
</section>
```

- [ ] **Step 4: Create the how-it-works partial**

Create `resources/views/landing/partials/how-it-works.blade.php`:

```blade
<section id="how-it-works" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">How it works</div>
    <div class="vx-steps" style="margin-bottom:32px">
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

- [ ] **Step 5: Create the features partial**

Create `resources/views/landing/partials/features.blade.php`:

```blade
<section class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Key features</div>
    <div class="vx-grid">
      <div class="vx-card"><h3>Automatic voice recognition</h3><p>Automatic voice recognition that accommodates varied accents.</p></div>
      <div class="vx-card"><h3>Seamless collaboration</h3><p>Works alongside tools like YouTube and Zoom.</p></div>
      <div class="vx-card"><h3>Simple 3-step process</h3><p>Download, create an account, tap Listen.</p></div>
      <div class="vx-card"><h3>Multi-Device Accessibility</h3><p>Use it across the devices your classroom already has.</p></div>
    </div>
  </div>
</section>
```

- [ ] **Step 6: Include the new partials in the home view**

Modify `resources/views/landing/home.blade.php` to:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.mission')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
@endsection
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS (both test methods)

- [ ] **Step 8: Commit**

```bash
git add resources/views/landing/partials/mission.blade.php resources/views/landing/partials/how-it-works.blade.php resources/views/landing/partials/features.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign mission, how-it-works, and key features sections"
```

---

### Task 3: Team section

**Files:**
- Create: `resources/views/landing/partials/team.blade.php`
- Modify: `app/Http/Controllers/LandingController.php`
- Modify: `resources/views/landing/home.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `LandingController::team(): array` — a private method returning a list of `['name' => string, 'role' => string, 'photo' => string]`, where `photo` is a filename under `public/images/voxsign/`.
- Produces: view variable `$team` passed from `index()`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_team_section_renders_all_six_members(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('Tusuubira Victor');
        $response->assertSee('CEO/Founder');
        $response->assertSee('Kamanzi Ahmed');
        $response->assertSee('Muwanguzi Joan Najjingo');
        $response->assertSee('Muhumuza Alex');
        $response->assertSee('Naikambo Sandra');
        $response->assertSee('Oyoka Daniel');
        $response->assertSee('images/voxsign/team-victor.jpg', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: FAIL — `test_team_section_renders_all_six_members` fails, team content doesn't exist yet.

- [ ] **Step 3: Add the team array to the controller**

Modify `app/Http/Controllers/LandingController.php` — replace the `index()` method and add a private `team()` method:

```php
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
        ]);
    }

    private function team(): array {
        return [
            ['name' => 'Tusuubira Victor', 'role' => 'CEO/Founder', 'photo' => 'team-victor.jpg'],
            ['name' => 'Kamanzi Ahmed', 'role' => 'Head of Marketing and Operations', 'photo' => 'team-kamanzi.jpg'],
            ['name' => 'Muwanguzi Joan Najjingo', 'role' => 'Finance and Sales Manager', 'photo' => 'team-joan.jpg'],
            ['name' => 'Muhumuza Alex', 'role' => 'Head of Product Development', 'photo' => 'team-alex.jpg'],
            ['name' => 'Naikambo Sandra', 'role' => 'Sign Language Specialist and Consultant', 'photo' => 'team-sandra.jpg'],
            ['name' => 'Oyoka Daniel', 'role' => 'Machine Learning Expert and Developer', 'photo' => 'team-daniel.jpg'],
        ];
    }
```

The full file should now read:

```php
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller {
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
        ]);
    }

    private function team(): array {
        return [
            ['name' => 'Tusuubira Victor', 'role' => 'CEO/Founder', 'photo' => 'team-victor.jpg'],
            ['name' => 'Kamanzi Ahmed', 'role' => 'Head of Marketing and Operations', 'photo' => 'team-kamanzi.jpg'],
            ['name' => 'Muwanguzi Joan Najjingo', 'role' => 'Finance and Sales Manager', 'photo' => 'team-joan.jpg'],
            ['name' => 'Muhumuza Alex', 'role' => 'Head of Product Development', 'photo' => 'team-alex.jpg'],
            ['name' => 'Naikambo Sandra', 'role' => 'Sign Language Specialist and Consultant', 'photo' => 'team-sandra.jpg'],
            ['name' => 'Oyoka Daniel', 'role' => 'Machine Learning Expert and Developer', 'photo' => 'team-daniel.jpg'],
        ];
    }

    public function contact(Request $request) {
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'email'=>'required|email|max:160',
            'message'=>'required|string|max:2000',
            'website'=>'nullable|max:0',   // honeypot: must be empty
        ]);
        Mail::raw($data['message']."\n\nFrom: {$data['name']} <{$data['email']}>", function ($m) use ($data) {
            $m->to(config('mail.contact_inbox', env('CONTACT_INBOX')))
              ->replyTo($data['email'], $data['name'])
              ->subject('VoxSign contact form');
        });
        return back()->with('status', 'Thanks — we will be in touch shortly.');
    }
}
```

- [ ] **Step 4: Create the team partial**

Create `resources/views/landing/partials/team.blade.php`:

```blade
<section id="team" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Team</div>
    <div class="vx-grid">
      @foreach($team as $member)
        <div class="vx-card">
          <img src="{{ asset('images/voxsign/'.$member['photo']) }}" alt="{{ $member['name'] }}">
          <h3>{{ $member['name'] }}</h3>
          <p>{{ $member['role'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
```

- [ ] **Step 5: Include the team partial**

Modify `resources/views/landing/home.blade.php` to:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.mission')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS (all three test methods)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/LandingController.php resources/views/landing/partials/team.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign team section with real photos and roles"
```

---

### Task 4: Partners section

**Files:**
- Create: `resources/views/landing/partials/partners.blade.php`
- Modify: `app/Http/Controllers/LandingController.php`
- Modify: `resources/views/landing/home.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `LandingController::partners(): array` — a private method returning a list of `['name' => string, 'logo' => string|null]`. When `logo` is `null`, the partial renders a text-only credit instead of an image tile.
- Produces: view variable `$partners` passed from `index()`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_partners_section_renders_logos_and_text_credits(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('images/voxsign/partner-unad.png', false);
        $response->assertSee('images/voxsign/partner-kyu.png', false);
        $response->assertSee('images/voxsign/partner-youtube.webp', false);
        $response->assertSee('images/voxsign/partner-4.jpg', false);
        $response->assertSee('Makerere University');
        $response->assertSee('Makerere Innovation and Incubation Centre');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: FAIL — `test_partners_section_renders_logos_and_text_credits` fails.

- [ ] **Step 3: Add the partners array to the controller**

Modify `app/Http/Controllers/LandingController.php` — update `index()` and add a private `partners()` method:

```php
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
            'partners' => $this->partners(),
        ]);
    }

    private function partners(): array {
        return [
            ['name' => 'Uganda National Association of the Deaf', 'logo' => 'partner-unad.png'],
            ['name' => 'Kyambogo University — Faculty of Special Needs & Rehabilitation', 'logo' => 'partner-kyu.png'],
            ['name' => 'YouTube', 'logo' => 'partner-youtube.webp'],
            ['name' => 'TGN Systems', 'logo' => 'partner-4.jpg'],
            ['name' => 'Makerere University', 'logo' => null],
            ['name' => 'Makerere Innovation and Incubation Centre', 'logo' => null],
        ];
    }
```

- [ ] **Step 4: Create the partners partial**

Create `resources/views/landing/partials/partners.blade.php`:

```blade
<section id="partners" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Partners</div>
    <div class="vx-partners">
      @foreach($partners as $partner)
        @if($partner['logo'])
          <img src="{{ asset('images/voxsign/'.$partner['logo']) }}" alt="{{ $partner['name'] }}">
        @else
          <span class="vx-partner-text">{{ $partner['name'] }}</span>
        @endif
      @endforeach
    </div>
  </div>
</section>
```

- [ ] **Step 5: Include the partners partial**

Modify `resources/views/landing/home.blade.php` to:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.mission')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.partners')
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS (all four test methods)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/LandingController.php resources/views/landing/partials/partners.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign partners section with real logos and text credits"
```

---

### Task 5: Testimonials section

**Files:**
- Create: `resources/views/landing/partials/testimonials.blade.php`
- Modify: `app/Http/Controllers/LandingController.php`
- Modify: `resources/views/landing/home.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `LandingController::testimonials(): array` — a private method returning a list of `['quote' => string, 'name' => string, 'role' => string]`.
- Produces: view variable `$testimonials` passed from `index()`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_testimonials_render_in_anticipatory_tense(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee("I can't wait to try VoxSign!");
        $response->assertSee('Birabwa Jane Lydia');
        $response->assertSee("I'm really looking forward to VoxSign's launch.");
        $response->assertDontSee('I love using VoxSign');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: FAIL — `test_testimonials_render_in_anticipatory_tense` fails.

- [ ] **Step 3: Add the testimonials array to the controller**

Modify `app/Http/Controllers/LandingController.php` — update `index()` and add a private `testimonials()` method:

```php
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
            'partners' => $this->partners(),
            'testimonials' => $this->testimonials(),
        ]);
    }

    private function testimonials(): array {
        return [
            [
                'quote' => "I can't wait to try VoxSign! The idea of having an AI avatar translate speech into Ugandan Sign Language sounds life-changing for students like me.",
                'name' => 'Naikambo Sandra',
                'role' => 'Student, Makerere University',
            ],
            [
                'quote' => "As an educator, I'm excited about VoxSign's potential to make classrooms more inclusive. If it works as described, it will truly revolutionize how we teach Deaf students.",
                'name' => 'Birabwa Jane Lydia',
                'role' => 'Educator, Makerere University',
            ],
            [
                'quote' => "I'm really looking forward to VoxSign's launch. A tool that can help my child understand spoken words and communicate back would be a dream come true.",
                'name' => "Naikambo Sandra's Parent",
                'role' => 'Parent',
            ],
        ];
    }
```

- [ ] **Step 4: Create the testimonials partial**

Create `resources/views/landing/partials/testimonials.blade.php`:

```blade
<section class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Early feedback</div>
    @foreach($testimonials as $t)
      <div class="vx-quote">
        <p>&ldquo;{{ $t['quote'] }}&rdquo;</p>
        <cite>{{ $t['name'] }} — {{ $t['role'] }}</cite>
      </div>
    @endforeach
  </div>
</section>
```

- [ ] **Step 5: Include the testimonials partial**

Modify `resources/views/landing/home.blade.php` to:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.mission')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.partners')
  @include('landing.partials.testimonials')
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS (all five test methods)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/LandingController.php resources/views/landing/partials/testimonials.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign testimonials, kept in their honest anticipatory tense"
```

---

### Task 6: Pricing table

**Files:**
- Create: `resources/views/landing/partials/pricing.blade.php`
- Modify: `app/Http/Controllers/LandingController.php`
- Modify: `resources/views/landing/home.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Produces: `LandingController::pricingTiers(): array` — a private method returning a list of `['name' => string, 'price' => string, 'features' => string[]]`.
- Produces: view variable `$pricingTiers` passed from `index()`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_pricing_table_renders_all_four_tiers(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('UGX 0');
        $response->assertSee('3,000 words/day limit');
        $response->assertSee('UGX 50,000/month');
        $response->assertSee('UGX 50,000,000/year');
        $response->assertSee('UGX 500,000,000/year');
        $response->assertSee('Government/NGOs');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: FAIL — `test_pricing_table_renders_all_four_tiers` fails.

- [ ] **Step 3: Add the pricing array to the controller**

Modify `app/Http/Controllers/LandingController.php` — update `index()` and add a private `pricingTiers()` method:

```php
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
            'partners' => $this->partners(),
            'testimonials' => $this->testimonials(),
            'pricingTiers' => $this->pricingTiers(),
        ]);
    }

    private function pricingTiers(): array {
        return [
            ['name' => 'Free', 'price' => 'UGX 0', 'features' => ['3,000 words/day limit']],
            ['name' => 'Premium', 'price' => 'UGX 50,000/month', 'features' => ['Unlimited translations', 'Ad-free', 'Collaboration tools']],
            ['name' => 'Enterprise', 'price' => 'UGX 50,000,000/year', 'features' => ['Custom branding', 'Dedicated support']],
            ['name' => 'Government/NGOs', 'price' => 'UGX 500,000,000/year', 'features' => ['Full suite', 'Chatbot', 'Analytics']],
        ];
    }
```

- [ ] **Step 4: Create the pricing partial**

Create `resources/views/landing/partials/pricing.blade.php`:

```blade
<section id="pricing" class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Pricing</div>
    <table class="vx-table">
      <thead>
        <tr><th>Plan</th><th>Cost</th><th>Key features</th></tr>
      </thead>
      <tbody>
        @foreach($pricingTiers as $tier)
          <tr>
            <td>{{ $tier['name'] }}</td>
            <td>{{ $tier['price'] }}</td>
            <td>{{ implode(', ', $tier['features']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>
```

- [ ] **Step 5: Include the pricing partial**

Modify `resources/views/landing/home.blade.php` to:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.mission')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.partners')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS (all six test methods)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/LandingController.php resources/views/landing/partials/pricing.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign pricing table with real tier figures"
```

---

### Task 7: Roadmap, Contact, and final assembly

**Files:**
- Create: `resources/views/landing/partials/roadmap.blade.php`
- Create: `resources/views/landing/partials/contact.blade.php`
- Modify: `resources/views/landing/home.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: existing route `contact` (named route already registered in `routes/web.php`, unchanged) and existing `LandingController@contact` validation/honeypot logic (unchanged).

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/LandingPageTest.php`:

```php
    public function test_roadmap_and_contact_section_render(): void
    {
        $response = $this->get('http://voxsign.co.ug/');

        $response->assertStatus(200);
        $response->assertSee('expansion across Africa', false);
        $response->assertSee('+256 770 680769');
        $response->assertSee('voxsign3@gmail.com');
        $response->assertSee('Makerere Innovation and Incubation Centre');
        $response->assertSee('name="website"', false); // honeypot field preserved
        $response->assertDontSee('Accessibility Statement');
        $response->assertDontSee('Privacy Policy');
    }

    public function test_contact_form_still_validates_and_submits(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello VoxSign',
            'website' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LandingPageTest`
Expected: `test_roadmap_and_contact_section_render` FAILS (roadmap/contact content doesn't exist in the new layout yet). `test_contact_form_still_submits` should already PASS since the controller logic is untouched — confirm it does (this is a regression check, not new behavior).

- [ ] **Step 3: Create the roadmap partial**

Create `resources/views/landing/partials/roadmap.blade.php`:

```blade
<section class="vx-section">
  <div class="vx-wrap">
    <div class="vx-eyebrow">What's next</div>
    <p class="vx-lead" style="max-width:640px;color:#ddd">
      Expansion across Africa to support multiple sign languages, integration
      with government and education systems, offline and wearable translation
      devices, a global multilingual sign-language AI model, and community
      programs employing Deaf individuals.
    </p>
  </div>
</section>
```

- [ ] **Step 4: Create the contact partial**

Create `resources/views/landing/partials/contact.blade.php` — this preserves the existing form fields, CSRF token, honeypot, and validation error rendering exactly as they were in the original `landing/home.blade.php`, restyled with the new `vx-*` classes:

```blade
<section id="contact" class="vx-section" style="border-bottom:0">
  <div class="vx-wrap">
    <div class="vx-eyebrow">Get in touch</div>
    <p class="vx-lead" style="margin-bottom:18px">
      +256 770 680769 &middot; voxsign3@gmail.com &middot; Makerere Innovation and Incubation Centre
    </p>
    <form method="post" action="{{ route('contact') }}" style="max-width:480px">
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
      <button class="vx-btn" type="submit">Send</button>
    </form>
  </div>
</section>
```

- [ ] **Step 5: Final assembly of the home view**

Replace `resources/views/landing/home.blade.php` with the complete, final version:

```blade
@extends('layouts.marketing')
@section('content')
  @include('landing.partials.hero')
  @include('landing.partials.mission')
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.partners')
  @include('landing.partials.testimonials')
  @include('landing.partials.pricing')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LandingPageTest`
Expected: PASS (all eight test methods)

- [ ] **Step 7: Run the full test suite to confirm no regressions elsewhere**

Run: `php artisan test`
Expected: All tests pass, including `TenantIsolationTest`, `AccountDeletionTest`, and `SmsCreditTest` — this change only touches the landing/marketing layer and must not affect tenant-facing code.

- [ ] **Step 8: Commit**

```bash
git add resources/views/landing/partials/roadmap.blade.php resources/views/landing/partials/contact.blade.php resources/views/landing/home.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add VoxSign roadmap and contact sections, complete landing page redesign"
```

- [ ] **Step 9: Manual verification (per the spec's Testing section)**

With `TENANCY_LANDING_HOSTS` including `localhost` in `.env` (per README), run `php artisan serve` and manually confirm in a browser:
1. Every section renders in order: nav, hero, mission, how-it-works, features, team (6 photos load), partners (4 logos + 2 text credits), testimonials, pricing table, roadmap, contact form, footer.
2. Submit the contact form and confirm the existing success message and validation still work.
3. Resize to mobile width (device toolbar, ~375px) and confirm the team grid, partner row, and pricing table reflow without horizontal scroll or unreadable contrast.
4. Visit `pearledu.voxsign.co.ug` (or the equivalent tenant/platform host locally) and confirm the PearlEdu app's own layout, login, and dashboard are visually unaffected — `layouts/app.blade.php` and `config/themes.php` were never touched.
