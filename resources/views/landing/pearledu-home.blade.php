@extends('layouts.pearledu-landing')

@section('content')
  {{-- EMIS-style two-column hero --}}
  <section class="pe-hero">
    <div class="pe-wrap pe-hero-grid">
      <div class="pe-hero-copy">
        <h1>School Management Platform (PearlEdu)</h1>
        <p class="pe-hero-lead">
          Providing timely school operations data for planning, budgeting, and evidence-based
          management — attendance, grading, fees, and parent communication in one place.
        </p>
        <p class="pe-cta-row">
          <a href="{{ url('/login') }}" class="pe-btn-solid">Staff login</a>
          <a href="#onboard" class="pe-btn-ghost">Onboard school</a>
        </p>
        <p class="pe-hero-note">New to PearlEdu? <a href="#onboard">Request onboarding</a> and we’ll set up your school.</p>
      </div>
      <div class="pe-hero-visual" aria-hidden="true">
        <svg class="pe-hero-art" viewBox="0 0 480 520" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="peArtSky" x1="80" y1="40" x2="420" y2="480" gradientUnits="userSpaceOnUse">
              <stop stop-color="#9FE7F5" stop-opacity=".35"/>
              <stop offset="1" stop-color="#053F5C" stop-opacity=".15"/>
            </linearGradient>
            <linearGradient id="peArtCard" x1="120" y1="90" x2="360" y2="380" gradientUnits="userSpaceOnUse">
              <stop stop-color="#FFFFFF"/>
              <stop offset="1" stop-color="#E8F7FA"/>
            </linearGradient>
          </defs>
          <ellipse cx="240" cy="460" rx="150" ry="22" fill="rgba(5,63,92,.25)"/>
          <rect x="70" y="70" width="340" height="380" rx="28" fill="url(#peArtSky)" stroke="rgba(255,255,255,.35)" stroke-width="2"/>
          <rect x="108" y="108" width="264" height="300" rx="18" fill="url(#peArtCard)" stroke="rgba(5,63,92,.08)"/>
          <rect x="128" y="132" width="120" height="14" rx="7" fill="#053F5C"/>
          <rect x="128" y="156" width="180" height="8" rx="4" fill="#429EBD" opacity=".55"/>
          <rect x="128" y="188" width="224" height="64" rx="12" fill="#053F5C"/>
          <rect x="144" y="206" width="72" height="10" rx="5" fill="#9FE7F5"/>
          <rect x="144" y="224" width="112" height="8" rx="4" fill="rgba(255,255,255,.45)"/>
          <circle cx="320" cy="220" r="18" fill="#F27F0C"/>
          <rect x="128" y="272" width="100" height="72" rx="12" fill="rgba(66,158,189,.18)"/>
          <rect x="240" y="272" width="100" height="72" rx="12" fill="rgba(242,127,12,.16)"/>
          <rect x="144" y="288" width="48" height="8" rx="4" fill="#429EBD"/>
          <rect x="144" y="304" width="68" height="6" rx="3" fill="#053F5C" opacity=".35"/>
          <rect x="256" y="288" width="48" height="8" rx="4" fill="#F27F0C"/>
          <rect x="256" y="304" width="68" height="6" rx="3" fill="#053F5C" opacity=".35"/>
          <rect x="128" y="360" width="224" height="22" rx="11" fill="#429EBD"/>
          <path d="M168 420c28-48 56-72 84-72s56 24 84 72" stroke="#9FE7F5" stroke-width="10" stroke-linecap="round" opacity=".7"/>
          <circle cx="252" cy="330" r="36" fill="#053F5C" opacity=".08"/>
        </svg>
      </div>
    </div>
    <svg class="pe-hero-wave" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true">
      <path fill="currentColor" d="M0,24 C360,56 1080,0 1440,28 L1440,56 L0,56 Z"/>
    </svg>
  </section>

  {{-- Modules (EMIS feature-row style) --}}
  <section id="modules" class="pe-section pe-modules">
    <div class="pe-wrap">
      <div class="pe-modules-head pe-reveal">
        <h2>What PearlEdu manages for your school</h2>
        <p>Core modules for day-to-day school operations — built for Ugandan institutions that need one reliable system.</p>
      </div>
      <div class="pe-module-grid pe-reveal">
        <article class="pe-module">
          <div class="pe-module-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="M8 14l2.3 2.3L16 11"/>
            </svg>
          </div>
          <div>
            <h3>Attendance</h3>
            <p>Mark class registers in seconds. Administrators see school-wide attendance as soon as teachers submit — no paper books to chase.</p>
          </div>
        </article>
        <article class="pe-module">
          <div class="pe-module-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 20V10M11 20V4M18 20v-7"/><path d="M3 20h18"/>
            </svg>
          </div>
          <div>
            <h3>Grading &amp; results</h3>
            <p>Capture assessment marks once. PearlEdu produces report cards, class averages, and term progress for every learner.</p>
          </div>
        </article>
        <article class="pe-module">
          <div class="pe-module-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="6.5" width="18" height="12" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14" r="1.2" fill="currentColor" stroke="none"/>
            </svg>
          </div>
          <div>
            <h3>Fees &amp; payments</h3>
            <p>Bill by class or student, track balances in real time, and reconcile mobile money alongside bank and cash payments.</p>
          </div>
        </article>
        <article class="pe-module">
          <div class="pe-module-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 5.5h16a1 1 0 0 1 1 1V16a1 1 0 0 1-1 1H8l-4 3.5V6.5a1 1 0 0 1 1-1Z"/>
            </svg>
          </div>
          <div>
            <h3>Parent communication</h3>
            <p>Send SMS and announcements to the right classes and guardians from the same system that holds marks and attendance.</p>
          </div>
        </article>
        <article class="pe-module">
          <div class="pe-module-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="8" r="3.2"/><circle cx="16" cy="9.5" r="2.5"/><path d="M3.5 19c.6-3 2.9-4.8 5.5-4.8S14 16 14.6 19"/><path d="M14.2 16.2c1.4-.5 3-.3 4.3.8"/>
            </svg>
          </div>
          <div>
            <h3>Staff &amp; roles</h3>
            <p>Assign teachers, bursars, directors, and deputies with clear permissions so every account only sees what it should.</p>
          </div>
        </article>
        <article class="pe-module">
          <div class="pe-module-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 3l8 3.5v5.2c0 4.4-3.2 7.8-8 9.3-4.8-1.5-8-4.9-8-9.3V6.5L12 3Z"/><path d="M9.5 12.2l1.8 1.8 3.5-3.8"/>
            </svg>
          </div>
          <div>
            <h3>Secure school data</h3>
            <p>Each school’s records are isolated at the database level. Your learners, fees, and staff stay visible only to authorised users.</p>
          </div>
        </article>
      </div>
    </div>
  </section>

  {{-- Stats band with count-up --}}
  <section class="pe-stats" aria-label="PearlEdu at a glance">
    <div class="pe-wrap">
      <div class="pe-stats-grid pe-reveal">
        <div class="pe-stat"><b data-count="1">0</b><span>Unified school system</span></div>
        <div class="pe-stat"><b data-count="4">0</b><span>Core operations modules</span></div>
        <div class="pe-stat"><b data-count="24" data-suffix="/7">0</b><span>Access for staff &amp; leaders</span></div>
        <div class="pe-stat"><b data-count="100" data-suffix="%">0</b><span>School-isolated data</span></div>
      </div>
    </div>
  </section>

  {{-- Pricing --}}
  <section id="pricing" class="pe-section pe-tint">
    <div class="pe-wrap">
      <div class="pe-reveal" style="text-align:center">
        <p class="pe-sec-label">Pricing</p>
        <h2 class="pe-h2">Plans that fit your school</h2>
        <p class="pe-lead pe-sec-head" style="margin-left:auto;margin-right:auto">
          Simple per-term pricing. Every plan includes onboarding support to get your school running.
        </p>
      </div>
      <div class="pe-pricing-grid pe-reveal">
        @forelse($plans as $plan)
          <div class="pe-price-card {{ $plan->is_highlighted ? 'pe-price-card--hot' : '' }}">
            @if($plan->is_highlighted)<span class="pe-price-badge">Most popular</span>@endif
            <h3>{{ $plan->name }}</h3>
            <p class="pe-price-tagline">{{ $plan->tagline }}</p>
            @if(is_null($plan->price))
              <div class="pe-price-amount">Contact us</div>
              <p class="pe-price-period">tailored to your school</p>
            @else
              <div class="pe-price-amount">{{ $plan->currency }} {{ number_format($plan->price) }}</div>
              <p class="pe-price-period">{{ $plan->billing_period }}</p>
            @endif
            <ul>
              @foreach($plan->features ?? [] as $feature)
                <li>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                  {{ $feature }}
                </li>
              @endforeach
            </ul>
            <a href="#onboard" class="{{ $plan->is_highlighted ? 'pe-btn-grad' : 'pe-btn' }}">Onboard your school</a>
          </div>
        @empty
          <p class="pe-lead" style="text-align:center;margin:0 auto">
            Pricing is being finalised — <a href="#onboard" style="color:var(--sign);text-decoration:underline">talk to us</a>
            and we’ll put together a plan for your school.
          </p>
        @endforelse
      </div>
    </div>
  </section>

  {{-- FAQ --}}
  <section id="faq" class="pe-section">
    <div class="pe-wrap">
      <div class="pe-faq-wrap pe-reveal">
        <h2 class="pe-h2">Have questions? Look here.</h2>
        <p class="pe-lead">Below are helpful answers for schools getting started with PearlEdu.</p>
        <div class="pe-faq">
          <details open>
            <summary>How long does it take to onboard a school?</summary>
            <p>Most schools are up and running within days. We set up your classes and levels with you,
               help import your student list, and train your staff — onboarding support is included in every plan.</p>
          </details>
          <details>
            <summary>How do I create a staff account?</summary>
            <p>After your school is onboarded, your school administrator invites staff by role. Each person
               receives a secure invite link to set their password and sign in at the PearlEdu login page.</p>
          </details>
          <details>
            <summary>Can we import existing student records?</summary>
            <p>Yes. If your records live in spreadsheets or paper registers, we help you bring them into
               PearlEdu during onboarding so you start with your real data.</p>
          </details>
          <details>
            <summary>Does PearlEdu work with mobile money?</summary>
            <p>Yes — fee payments can be received and reconciled through mobile money alongside bank and
               cash payments, so the bursar sees one complete picture of every student’s balance.</p>
          </details>
          <details>
            <summary>Is our school’s data secure and private?</summary>
            <p>Each school’s data is strictly isolated at the database level — your records are visible
               only to your school’s authorised staff. Access is controlled by roles you assign.</p>
          </details>
        </div>
        <p class="pe-faq-mail">Still have a question? Mail us at <a href="mailto:info@voxsign.co.ug">info@voxsign.co.ug</a></p>
      </div>
    </div>
  </section>

  {{-- Onboard --}}
  <section id="onboard" class="pe-section pe-onboard">
    <div class="pe-wrap pe-reveal" style="text-align:center">
      <p class="pe-sec-label">Get started</p>
      <h2 class="pe-h2">Onboard your school</h2>
      <p class="pe-lead" style="margin:0 auto 28px">
        Tell us about your school and we’ll be in touch to get you set up on PearlEdu.
      </p>
      <form method="post" action="{{ route('pearledu.onboard') }}" class="pe-form-card">
        @csrf
        <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
        <label class="pe-label" for="onboard-school">School name</label>
        <input id="onboard-school" class="pe-input" name="school_name" value="{{ old('school_name') }}" required autocomplete="organization">
        @error('school_name')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label" for="onboard-name">Your name</label>
        <input id="onboard-name" class="pe-input" name="contact_name" value="{{ old('contact_name') }}" required autocomplete="name">
        @error('contact_name')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label" for="onboard-email">Email</label>
        <input id="onboard-email" class="pe-input" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        @error('email')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label" for="onboard-phone">Phone</label>
        <input id="onboard-phone" class="pe-input" name="phone" value="{{ old('phone') }}" autocomplete="tel">
        @error('phone')<div class="pe-err">{{ $message }}</div>@enderror
        <label class="pe-label" for="onboard-message">Tell us about your school</label>
        <textarea id="onboard-message" class="pe-input" name="message" rows="4">{{ old('message') }}</textarea>
        @error('message')<div class="pe-err">{{ $message }}</div>@enderror
        @include('partials.turnstile', ['errorClass' => 'pe-err'])
        <button class="pe-btn-grad" type="submit">Request onboarding</button>
      </form>
    </div>
  </section>
@endsection
