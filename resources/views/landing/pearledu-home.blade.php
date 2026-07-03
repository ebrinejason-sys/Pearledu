@extends('layouts.pearledu-landing')

@section('content')
  <section class="pe-section pe-hero" style="padding-top:56px">
    <div class="pe-hero-glow" aria-hidden="true"></div>
    <div class="pe-wrap">
      <div class="pe-eyebrow">School Management Platform</div>
      <h1 class="pe-h1">School management, <span class="pe-flow">without the spreadsheets.</span></h1>
      <p class="pe-lead">
        PearlEdu is a school management platform for institutions — attendance, grading, fees, and
        communication in one place. Built by VoxSign Technologies for schools that need one system,
        not five disconnected ones.
      </p>
      <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="#onboard" class="pe-btn">Onboard your school</a>
        <a href="{{ url('/login') }}" class="pe-btn-ghost">Login</a>
      </p>
    </div>
  </section>

  <section id="how-it-works" class="pe-section">
    <div class="pe-wrap">
      <div class="pe-eyebrow">How it works</div>
      <h2 class="pe-h2" style="margin-bottom:12px">Everything your school needs, in one system.</h2>
      <p class="pe-lead" style="margin-bottom:32px">
        PearlEdu replaces paper registers and disconnected spreadsheets with a single platform your
        staff, parents, and administrators can all rely on.
      </p>
      <div class="pe-grid">
        <div class="pe-card">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="M8 14l2.3 2.3L16 11"/>
            </svg>
          </span>
          <h3>Attendance</h3>
          <p>Track student attendance across classes without paper registers.</p>
        </div>
        <div class="pe-card">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 20V10M11 20V4M18 20v-7"/><path d="M3 20h18"/>
            </svg>
          </span>
          <h3>Grading</h3>
          <p>Record and report assessment results in one consistent system.</p>
        </div>
        <div class="pe-card">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="6.5" width="18" height="12" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14" r="1.2" fill="currentColor" stroke="none"/>
            </svg>
          </span>
          <h3>Fees</h3>
          <p>Manage school fee billing and payments, including mobile money.</p>
        </div>
        <div class="pe-card">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 5.5h16a1 1 0 0 1 1 1V16a1 1 0 0 1-1 1H8l-4 3.5V6.5a1 1 0 0 1 1-1Z"/>
            </svg>
          </span>
          <h3>Communication</h3>
          <p>Keep staff, parents, and administrators on the same page.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="onboard" class="pe-section">
    <div class="pe-wrap">
      <div class="pe-eyebrow">Get started</div>
      <h2 class="pe-h2" style="margin-bottom:12px">Onboard your school.</h2>
      <p class="pe-lead" style="margin-bottom:28px">
        Tell us about your school and we'll be in touch to get you set up on PearlEdu.
      </p>
      <form method="post" action="{{ route('pearledu.onboard') }}" style="max-width:480px;margin:0 auto;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:26px">
        @csrf
        <div style="position:absolute;left:-9999px"><input name="website" tabindex="-1" autocomplete="off"></div>
        <label class="pe-label">School name</label>
        <input class="pe-input" name="school_name" value="{{ old('school_name') }}" required>
        @error('school_name')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label">Your name</label>
        <input class="pe-input" name="contact_name" value="{{ old('contact_name') }}" required>
        @error('contact_name')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label">Email</label>
        <input class="pe-input" name="email" type="email" value="{{ old('email') }}" required>
        @error('email')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label">Phone</label>
        <input class="pe-input" name="phone" value="{{ old('phone') }}">
        @error('phone')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label">Tell us about your school</label>
        <textarea class="pe-input" name="message" rows="4">{{ old('message') }}</textarea>
        @error('message')<div class="pe-err">{{ $message }}</div>@enderror
        <button class="pe-btn" type="submit" style="width:100%;justify-content:center">Request onboarding</button>
      </form>
    </div>
  </section>
@endsection
