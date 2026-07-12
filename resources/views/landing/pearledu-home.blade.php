@extends('layouts.pearledu-landing')

@section('content')
  {{-- ============ HERO ============ --}}
  <section class="pe-section pe-band pe-hero">
    <div class="pe-wrap">
      <div style="text-align:center;max-width:820px;margin:0 auto">
        <div class="pe-eyebrow">School Management Platform</div>
        <h1 class="pe-h1" style="margin-left:auto;margin-right:auto">School management, <span class="pe-flow">without the spreadsheets.</span></h1>
        <p class="pe-lead" style="margin:0 auto">
          PearlEdu is a school management platform for institutions — attendance, grading, fees, and
          communication in one place. Built by VoxSign Technologies for schools that need one system,
          not five disconnected ones.
        </p>
        <p class="pe-cta-row" style="margin-top:30px">
          <a href="#onboard" class="pe-btn-grad">Onboard your school</a>
          <a href="{{ url('/login') }}" class="pe-btn-ghost">Login</a>
        </p>
      </div>

      <div class="pe-mock pe-reveal" style="max-width:880px;margin:clamp(36px,6vw,56px) auto 0" aria-hidden="true">
        <div class="pe-mock-bar"><i></i><i></i><i></i><span class="pe-mock-url">pearledu.voxsign.co.ug/dashboard</span></div>
        <div class="pe-mock-body">
          <div class="pe-mock-stats">
            <div class="pe-mock-stat"><b>1,248</b><span>Students enrolled</span></div>
            <div class="pe-mock-stat"><b>96.4%</b><span>Attendance today</span></div>
            <div class="pe-mock-stat"><b>UGX 48.2M</b><span>Fees collected this term</span></div>
          </div>
          <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--sign)">P4</span> Primary Four — morning register submitted <span class="pe-mock-chip pe-chip-ok">Complete</span></div>
          <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--copper)">S2</span> Senior Two — mid-term results entered <span class="pe-mock-chip pe-chip-ok">Published</span></div>
          <div class="pe-mock-row"><span class="pe-mock-dot" style="background:#5D6473">S1</span> Senior One — 14 fee reminders queued <span class="pe-mock-chip pe-chip-warn">Sending</span></div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============ FEATURE DEEP-DIVES ============ --}}
  <section id="how-it-works" class="pe-section">
    <div class="pe-wrap">
      <div class="pe-reveal" style="text-align:center">
        <div class="pe-eyebrow">How it works</div>
        <h2 class="pe-h2" style="margin-bottom:12px">Everything your school needs, in one system.</h2>
        <p class="pe-lead pe-sec-head" style="margin-left:auto;margin-right:auto">
          PearlEdu replaces paper registers and disconnected spreadsheets with a single platform your
          staff, parents, and administrators can all rely on.
        </p>
      </div>

      <div class="pe-feature-row pe-reveal">
        <div class="pe-feature-copy">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="M8 14l2.3 2.3L16 11"/>
            </svg>
          </span>
          <h3>Attendance</h3>
          <p>Track student attendance across classes without paper registers. Teachers mark a class in
             seconds, and administrators see school-wide attendance the moment registers are submitted.</p>
        </div>
        <div class="pe-feature-mock">
          <div class="pe-mock" aria-hidden="true">
            <div class="pe-mock-bar"><i></i><i></i><i></i><span class="pe-mock-url">Attendance — Primary Four</span></div>
            <div class="pe-mock-body">
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--sign)">AN</span> Achan Naume <span class="pe-mock-chip pe-chip-ok">Present</span></div>
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--copper)">BK</span> Bwambale Kule <span class="pe-mock-chip pe-chip-ok">Present</span></div>
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:#5D6473">CM</span> Chandiru Mercy <span class="pe-mock-chip pe-chip-warn">Absent</span></div>
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--sign)">DO</span> Draru Onen <span class="pe-mock-chip pe-chip-ok">Present</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="pe-feature-row pe-reveal">
        <div class="pe-feature-mock">
          <div class="pe-mock" aria-hidden="true">
            <div class="pe-mock-bar"><i></i><i></i><i></i><span class="pe-mock-url">Results — Senior Two, Mid-term</span></div>
            <div class="pe-mock-body">
              <div class="pe-mock-bars">
                <i style="height:62%"></i><i class="alt" style="height:78%"></i><i style="height:54%"></i>
                <i class="alt" style="height:88%"></i><i style="height:70%"></i><i class="alt" style="height:47%"></i>
              </div>
              <div class="pe-mock-labels"><span>Eng</span><span>Math</span><span>Bio</span><span>Chem</span><span>Hist</span><span>Geo</span></div>
            </div>
          </div>
        </div>
        <div class="pe-feature-copy">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 20V10M11 20V4M18 20v-7"/><path d="M3 20h18"/>
            </svg>
          </span>
          <h3>Grading</h3>
          <p>Record and report assessment results in one consistent system. Enter marks once and PearlEdu
             handles report cards, class averages, and term-on-term progress for every student.</p>
        </div>
      </div>

      <div class="pe-feature-row pe-reveal">
        <div class="pe-feature-copy">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="6.5" width="18" height="12" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14" r="1.2" fill="currentColor" stroke="none"/>
            </svg>
          </span>
          <h3>Fees</h3>
          <p>Manage school fee billing and payments, including mobile money. Bill by class or student,
             track balances in real time, and reconcile every payment without a single ledger book.</p>
        </div>
        <div class="pe-feature-mock">
          <div class="pe-mock" aria-hidden="true">
            <div class="pe-mock-bar"><i></i><i></i><i></i><span class="pe-mock-url">Fees — Term II balances</span></div>
            <div class="pe-mock-body">
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--sign)">P7</span> Tuition — UGX 850,000 <span class="pe-mock-chip pe-chip-ok">Paid · MTN MoMo</span></div>
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:var(--copper)">S3</span> Boarding — UGX 620,000 <span class="pe-mock-chip pe-chip-warn">UGX 120,000 due</span></div>
              <div class="pe-mock-row"><span class="pe-mock-dot" style="background:#5D6473">S5</span> Tuition — UGX 900,000 <span class="pe-mock-chip pe-chip-ok">Paid · Airtel Money</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="pe-feature-row pe-reveal">
        <div class="pe-feature-mock">
          <div class="pe-mock" aria-hidden="true">
            <div class="pe-mock-bar"><i></i><i></i><i></i><span class="pe-mock-url">Messages — Parents, Primary Six</span></div>
            <div class="pe-mock-body">
              <div class="pe-bubble pe-bubble-out">Reminder: mid-term exams begin Monday 9th. Full details on the parent portal.</div>
              <div class="pe-bubble pe-bubble-in">Thank you — received. Will the timetable be shared as well?</div>
              <div class="pe-bubble pe-bubble-out">Yes, it is already available under Primary Six → Exams.</div>
            </div>
          </div>
        </div>
        <div class="pe-feature-copy">
          <span class="pe-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 5.5h16a1 1 0 0 1 1 1V16a1 1 0 0 1-1 1H8l-4 3.5V6.5a1 1 0 0 1 1-1Z"/>
            </svg>
          </span>
          <h3>Communication</h3>
          <p>Keep staff, parents, and administrators on the same page. Send SMS and announcements to the
             right classes and guardians directly from the platform — no separate messaging tool needed.</p>
        </div>
      </div>
    </div>
  </section>

  {{-- ============ PRICING ============ --}}
  <section id="pricing" class="pe-section pe-tint">
    <div class="pe-wrap">
      <div class="pe-reveal" style="text-align:center">
        <div class="pe-eyebrow">Pricing</div>
        <h2 class="pe-h2" style="margin-bottom:12px">Plans that fit your school.</h2>
        <p class="pe-lead pe-sec-head" style="margin-left:auto;margin-right:auto">
          Simple per-term pricing, no hidden charges. Every plan includes onboarding support to get your
          school up and running.
        </p>
      </div>
      <div class="pe-pricing-grid pe-reveal" style="padding-top:14px">
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
            <a href="#onboard" class="{{ $plan->is_highlighted ? 'pe-btn-grad' : 'pe-btn-ghost' }}" style="justify-content:center">Onboard your school</a>
          </div>
        @empty
          <p class="pe-lead" style="text-align:center;margin:0 auto">
            Pricing is being finalised — <a href="#onboard" style="text-decoration:underline">talk to us</a> and
            we'll put together a plan for your school.
          </p>
        @endforelse
      </div>
    </div>
  </section>

  {{-- ============ TESTIMONIALS ============ --}}
  <section class="pe-section">
    <div class="pe-wrap">
      <div class="pe-reveal" style="text-align:center">
        <div class="pe-eyebrow">Early feedback</div>
        <h2 class="pe-h2 pe-sec-head">What school leaders tell us.</h2>
      </div>
      <div class="pe-grid pe-reveal" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
        <div class="pe-quote">
          <p>&ldquo;One system for registers, marks, and fees is exactly what we have been asking for.
             The less time my teachers spend on paperwork, the more they teach.&rdquo;</p>
          <cite>Head teacher — pilot school, Kampala</cite>
        </div>
        <div class="pe-quote">
          <p>&ldquo;Fee tracking with mobile money reconciliation would remove our biggest end-of-term
             headache. Everything in one place, visible to the bursar and the director.&rdquo;</p>
          <cite>School bursar — pilot school, Wakiso</cite>
        </div>
        <div class="pe-quote">
          <p>&ldquo;Parents ask for updates constantly. Sending class-level SMS from the same system that
             holds the marks and attendance is a big step up for us.&rdquo;</p>
          <cite>Director of studies — pilot school, Mukono</cite>
        </div>
      </div>
    </div>
  </section>

  {{-- ============ FAQ ============ --}}
  <section id="faq" class="pe-section pe-tint">
    <div class="pe-wrap">
      <div class="pe-reveal" style="text-align:center">
        <div class="pe-eyebrow">FAQ</div>
        <h2 class="pe-h2 pe-sec-head">Questions schools ask us.</h2>
      </div>
      <div class="pe-faq pe-reveal">
        <details>
          <summary>How long does it take to onboard a school?</summary>
          <p>Most schools are up and running within days. We set up your classes and levels with you,
             help import your student list, and train your staff — onboarding support is included in
             every plan.</p>
        </details>
        <details>
          <summary>Can we import our existing student records?</summary>
          <p>Yes. If your records live in spreadsheets or paper registers, we help you bring them into
             PearlEdu during onboarding so you start with your real data, not an empty system.</p>
        </details>
        <details>
          <summary>Does PearlEdu work with mobile money?</summary>
          <p>Yes — fee payments can be received and reconciled through mobile money alongside bank and
             cash payments, so the bursar sees one complete picture of every student's balance.</p>
        </details>
        <details>
          <summary>How do parents receive communication?</summary>
          <p>Through SMS sent directly from the platform. Messages go to the guardians of exactly the
             classes or students you choose, so parents stay informed without staff juggling phone lists.</p>
        </details>
        <details>
          <summary>Is our school's data secure and private?</summary>
          <p>Each school's data is strictly isolated at the database level — your records are visible
             only to your school's authorised staff. Access is controlled by roles you assign.</p>
        </details>
      </div>
    </div>
  </section>

  {{-- ============ FINAL CTA + ONBOARDING ============ --}}
  <section id="onboard" class="pe-section pe-band">
    <div class="pe-wrap pe-reveal" style="text-align:center">
      <div class="pe-eyebrow">Get started</div>
      <h2 class="pe-h2" style="color:#fff;margin-bottom:12px">Onboard your school.</h2>
      <p class="pe-lead" style="margin:0 auto 28px">
        Tell us about your school and we'll be in touch to get you set up on PearlEdu.
      </p>
      <form method="post" action="{{ route('pearledu.onboard') }}" class="pe-form-card">
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
        <button class="pe-btn-grad" type="submit" style="width:100%;justify-content:center">Request onboarding</button>
      </form>
    </div>
  </section>
@endsection
