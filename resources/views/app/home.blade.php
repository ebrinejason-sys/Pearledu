@extends('layouts.app')
@section('title', $school?->name ?? 'Home')
@section('content')
  @if(!$school)
    <div class="card"><p>No active school context for this account.</p></div>
  @else
    <div class="page-header">
      <div>
        <p class="page-header__eyebrow">School dashboard</p>
        <h1 class="page-header__title">{{ data_get($workspace, 'greeting') ?? $school->name }}</h1>
        <p style="margin:6px 0 0;color:var(--muted);font-size:14px">{{ now()->timezone(config('app.timezone'))->format('l, j F Y') }}</p>
      </div>
      <div class="page-header__actions">
        @if(in_array('learners.manage', $permissions, true) || in_array('learners.view', $permissions, true))
          <a class="btn" href="{{ route('app.students.index') }}">Students</a>
        @endif
        @if(in_array('finance.manage', $permissions, true) || in_array('finance.view', $permissions, true))
          <a class="btn accent" href="{{ route('app.fees.index') }}">Fees</a>
        @endif
        @if(in_array('sms.send', $permissions, true))
          <a class="btn ghost" href="{{ route('app.sms') }}">Send SMS</a>
        @endif
      </div>
    </div>

    @if(!empty($emis))
      <div class="emis-kpis">
        <div class="emis-card emis-card--teal">
          <div class="emis-card__value">{{ number_format($emis['learners']['total']) }}</div>
          <div class="emis-card__label">Learners</div>
          <div class="emis-card__split">{{ $emis['learners']['male'] }} Male · {{ $emis['learners']['female'] }} Female</div>
        </div>
        <div class="emis-card emis-card--pink">
          <div class="emis-card__value">{{ number_format($emis['teaching']['male'] + $emis['teaching']['female'] + $emis['teaching']['unspecified']) }}</div>
          <div class="emis-card__label">Teaching staff</div>
          <div class="emis-card__split">{{ $emis['teaching']['male'] }} Male · {{ $emis['teaching']['female'] }} Female</div>
        </div>
        <div class="emis-card emis-card--navy">
          <div class="emis-card__value">{{ number_format($emis['non_teaching']['male'] + $emis['non_teaching']['female'] + $emis['non_teaching']['unspecified']) }}</div>
          <div class="emis-card__label">Non teaching staff</div>
          <div class="emis-card__split">{{ $emis['non_teaching']['male'] }} Male · {{ $emis['non_teaching']['female'] }} Female</div>
        </div>
      </div>
      <div class="grid g2" style="margin-bottom:16px">
        <div class="card">
          <h2 style="margin-top:0;font-size:18px">Enrollment by class &amp; sex</h2>
          @php
            $enrollMax = max(1, (int) collect($emis['enrollment'])->max('total'));
          @endphp
          @forelse($emis['enrollment'] as $row)
            @php
              $malePct = (int) round(100 * ((int) $row['male']) / $enrollMax);
              $femalePct = (int) round(100 * ((int) $row['female']) / $enrollMax);
            @endphp
            <div class="dash-bar" style="margin-bottom:10px">
              <div class="dash-bar__meta"><span>{{ $row['label'] }}</span><strong>{{ $row['male'] }}M / {{ $row['female'] }}F</strong></div>
              <div class="dash-bar__track dash-bar__track--split" title="{{ $row['total'] }} learners">
                <span class="m" style="width:{{ $malePct }}%"></span>
                <span class="f" style="width:{{ $femalePct }}%"></span>
              </div>
            </div>
          @empty
            <p style="color:var(--muted);margin:0">No enrollment yet.</p>
          @endforelse
        </div>
        <div class="card">
          <h2 style="margin-top:0;font-size:18px">NIN tracking</h2>
          <div class="workspace-kpis">
            <div class="dash-stat"><div class="dash-stat__value">{{ $emis['nin']['with'] }}</div><div class="dash-stat__label">Learners with NINs</div></div>
            <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $emis['nin']['without'] }}</div><div class="dash-stat__label">Learners without NINs</div></div>
          </div>
          <h3 style="font-size:15px;margin:18px 0 8px">Learner nationality</h3>
          @forelse($emis['nationality'] as $row)
            <p style="margin:0 0 6px">{{ $row['label'] }} · {{ $row['count'] }} ({{ $row['pct'] }}%)</p>
          @empty
            <p style="color:var(--muted);margin:0">No nationality data yet.</p>
          @endforelse
        </div>
      </div>
    @endif

    @if(!empty($workspace['teacher']))
      <div class="card" style="margin-bottom:16px">
        <div class="dash-chart-card__head">
          <h2 style="margin:0;font-size:18px">My classes</h2>
          <a class="btn ghost" href="{{ route('app.teaching.mine') }}">Open workspace</a>
        </div>
        <h3 style="font-size:14px;margin:14px 0 8px">Today’s lessons</h3>
        @forelse($workspace['teacher']['lessons'] as $lesson)
          <p style="margin:0 0 6px">{{ $lesson->period?->starts_at ?? $lesson->period?->name }} · {{ $lesson->subject?->name }} — {{ $lesson->schoolClass?->displayName() }}</p>
        @empty
          <p style="color:var(--muted);margin:0">No lessons on the timetable for today.</p>
        @endforelse
        <p style="margin:12px 0 0;display:flex;flex-wrap:wrap;gap:8px">
          <a class="btn" href="{{ route('app.attendance.index') }}">Take attendance</a>
          <a class="btn ghost" href="{{ route('app.assessment.marks') }}">Enter marks</a>
          @if(in_array('lms.manage', $permissions, true))
            <a class="btn ghost" href="{{ route('app.lms.index') }}">Learning materials</a>
          @endif
        </p>
      </div>
    @endif

    @if(!empty($workspace['homeroom']))
      <div class="card" style="margin-bottom:16px">
        <div class="dash-chart-card__head">
          <h2 style="margin:0;font-size:18px">My Class · {{ $workspace['homeroom']['class_name'] }}</h2>
          <a class="btn ghost" href="{{ route('app.teaching.homeroom') }}">Open class</a>
        </div>
        <div class="workspace-kpis" style="margin-top:12px">
          <div class="dash-stat"><div class="dash-stat__value">{{ $workspace['homeroom']['students'] }}</div><div class="dash-stat__label">Students</div></div>
          <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $workspace['homeroom']['present'] }}</div><div class="dash-stat__label">Present today</div></div>
          <div class="dash-stat dash-stat--warning"><div class="dash-stat__value">{{ $workspace['homeroom']['absent'] }}</div><div class="dash-stat__label">Absent</div></div>
          <div class="dash-stat"><div class="dash-stat__value">{{ $workspace['homeroom']['late'] }}</div><div class="dash-stat__label">Late</div></div>
          @if(!empty($workspace['homeroom']['gender']))
            <div class="dash-stat"><div class="dash-stat__value">{{ $workspace['homeroom']['gender']['male'] }}/{{ $workspace['homeroom']['gender']['female'] }}</div><div class="dash-stat__label">Class M/F</div></div>
          @endif
        </div>
      </div>
    @endif

    @if(!empty($workspace['bursar']))
      <div class="card" style="margin-bottom:16px">
        <div class="dash-chart-card__head">
          <h2 style="margin:0;font-size:18px">School fees</h2>
          <a class="btn ghost" href="{{ route('app.fees.index') }}">Open fees</a>
        </div>
        <div class="workspace-kpis" style="margin-top:12px">
          <div class="dash-stat"><div class="dash-stat__value">UGX {{ number_format($workspace['bursar']['expected'], 0) }}</div><div class="dash-stat__label">Expected</div></div>
          <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">UGX {{ number_format($workspace['bursar']['collected'], 0) }}</div><div class="dash-stat__label">Collected</div></div>
          <div class="dash-stat dash-stat--warning"><div class="dash-stat__value">UGX {{ number_format($workspace['bursar']['outstanding'], 0) }}</div><div class="dash-stat__label">Outstanding</div></div>
          <div class="dash-stat"><div class="dash-stat__value">{{ $workspace['bursar']['rate'] }}%</div><div class="dash-stat__label">Collection rate</div></div>
        </div>
        @if($workspace['bursar']['pending'] > 0)
          <p style="margin:12px 0 0"><a href="{{ route('app.fees.index') }}#payments">{{ $workspace['bursar']['pending'] }} payment(s) pending verification</a></p>
        @endif
      </div>
    @endif

    @if(!empty($workspace['academicLead']))
      <div class="card" style="margin-bottom:16px">
        <div class="dash-chart-card__head">
          <h2 style="margin:0;font-size:18px">Academic workflow</h2>
          <a class="btn ghost" href="{{ route('app.assessment.index') }}">Assessment periods</a>
        </div>
        <p style="color:var(--muted);font-size:14px">{{ $workspace['academicLead']['period'] ?? 'No assessment period yet' }}</p>
        <div class="workspace-kpis">
          <div class="dash-stat"><div class="dash-stat__value">{{ $workspace['academicLead']['submitted_pct'] }}%</div><div class="dash-stat__label">Marks submitted</div></div>
          <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $workspace['academicLead']['verified_pct'] }}%</div><div class="dash-stat__label">Marks verified</div></div>
          <div class="dash-stat dash-stat--warning"><div class="dash-stat__value">{{ $workspace['academicLead']['draft'] }}</div><div class="dash-stat__label">Still in draft</div></div>
        </div>
      </div>
    @endif

    @if(!empty($workspace['operationsLead']) || !empty($workspace['governance']))
      @php
        $ops = $workspace['operationsLead'] ?? $workspace['governance'];
      @endphp
      <div class="card" style="margin-bottom:16px">
        <h2 style="margin-top:0;font-size:18px">{{ !empty($workspace['governance']) ? 'School performance' : 'School overview' }}</h2>
        <div class="workspace-kpis">
          <div class="dash-stat"><div class="dash-stat__value">{{ number_format($ops['students']) }}</div><div class="dash-stat__label">Students</div></div>
          <div class="dash-stat"><div class="dash-stat__value">{{ number_format($ops['staff']) }}</div><div class="dash-stat__label">Staff</div></div>
          <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $ops['attendance_pct'] !== null ? $ops['attendance_pct'].'%' : '—' }}</div><div class="dash-stat__label">Attendance today</div></div>
          @if(!empty($ops['finance']))
            <div class="dash-stat"><div class="dash-stat__value">{{ $ops['finance']['rate'] }}%</div><div class="dash-stat__label">Fee collection</div></div>
          @endif
          @if($ops['academic_mean'] !== null)
            <div class="dash-stat"><div class="dash-stat__value">{{ $ops['academic_mean'] }}%</div><div class="dash-stat__label">Academic mean</div></div>
          @endif
          @if(!empty($ops['gender']))
            <div class="dash-stat"><div class="dash-stat__value">{{ $ops['gender']['learners']['male'] }}/{{ $ops['gender']['learners']['female'] }}</div><div class="dash-stat__label">Learners M/F</div></div>
            <div class="dash-stat"><div class="dash-stat__value">{{ $ops['gender']['staff']['male'] }}/{{ $ops['gender']['staff']['female'] }}</div><div class="dash-stat__label">Staff M/F</div></div>
          @endif
        </div>
      </div>
    @endif

    @if(empty($setupComplete) && isset($setupPercent) && in_array('school.manage', $permissions, true))
      <div class="card" style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
          <div>
            <strong>Welcome to PearlEdu</strong>
            <p style="margin:4px 0 0;color:var(--muted);font-size:14px">
              Setup is {{ $setupPercent }}% complete.
              @if($setupNext)
                Next: {{ $setupNext['label'] }}.
              @endif
            </p>
          </div>
          <a class="btn accent" href="{{ route('app.setup.index') }}">Continue setup</a>
        </div>
      </div>
    @endif

    <div class="card" style="margin-bottom:16px">
        <h3 style="margin-top:0">Needs your attention</h3>
        <ul style="list-style:none;padding:0;margin:0">
        @forelse($actionItems ?? [] as $item)
            <li style="padding:10px 0;border-top:1px solid var(--border,#e5e7eb)">
              <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                <div>
                  <span class="pill">{{ $item['priority'] }}</span>
                  <strong style="margin-left:6px">{{ $item['title'] }}</strong>
                  <div style="color:var(--muted);font-size:13px;margin-top:4px">{{ $item['description'] }}</div>
                </div>
                @if($item['action_url'])
                  <a class="btn ghost" href="{{ $item['action_url'] }}">Open</a>
                @endif
              </div>
            </li>
          @empty
            <li style="padding:10px 0;color:var(--muted)">You're caught up for now.</li>
          @endforelse
        </ul>
      </div>

    @if(!empty($stats))
      <div class="dash-stats">
        @foreach($stats as $stat)
          <div class="dash-stat dash-stat--{{ $stat['tone'] }}">
            <div class="dash-stat__value">{{ $stat['value'] }}</div>
            <div class="dash-stat__label">{{ $stat['label'] }}</div>
            @if(!empty($stat['hint']))
              <div class="dash-stat__hint">{{ $stat['hint'] }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    <div class="grid g2" style="margin-top:4px">
      <div class="card dash-chart-card">
        <div class="dash-chart-card__head">
          <h3 style="margin:0">Learners by class</h3>
          <span class="pill">Enrollment</span>
        </div>
        @if(empty($classChart))
          <p style="color:var(--muted);font-size:14px;margin:18px 0 0">No class enrollments yet.</p>
        @else
          <div class="dash-bars" role="img" aria-label="Active students by class">
            @foreach($classChart as $row)
              <div class="dash-bar">
                <div class="dash-bar__meta">
                  <span>{{ $row['label'] }}</span>
                  <strong>{{ number_format($row['count']) }}</strong>
                </div>
                <div class="dash-bar__track"><span style="width:{{ $row['pct'] }}%"></span></div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="card dash-chart-card">
        <div class="dash-chart-card__head">
          <h3 style="margin:0">Fee collections</h3>
          <span class="pill">Last 6 months</span>
        </div>
        @php
          $maxFee = max(1, ...array_map(fn ($r) => (float) $r['amount'], $feeChart ?: [['amount' => 1]]));
        @endphp
        @if(collect($feeChart)->sum('amount') <= 0)
          <p style="color:var(--muted);font-size:14px;margin:18px 0 0">No confirmed fee payments in this window yet.</p>
        @else
          <div class="dash-cols" role="img" aria-label="Confirmed fee collections by month">
            @foreach($feeChart as $row)
              <div class="dash-col">
                <div class="dash-col__bar" style="height:{{ max(4, $row['pct']) }}%" title="UGX {{ number_format($row['amount'], 0) }}"></div>
                <span class="dash-col__label">{{ $row['label'] }}</span>
              </div>
            @endforeach
          </div>
          <p style="margin:12px 0 0;font-size:12px;color:var(--muted)">Confirmed payments only · peak UGX {{ number_format($maxFee, 0) }}</p>
        @endif
      </div>
    </div>

    @if(!empty($shortcuts))
      <div class="card" style="margin-top:4px">
        <div class="dash-chart-card__head" style="margin-bottom:14px">
          <h3 style="margin:0">Quick access</h3>
          <span style="font-size:13px;color:var(--muted)">Jump into the modules you can use</span>
        </div>
        <div class="dash-tiles">
          @foreach($shortcuts as $tile)
            <a class="dash-tile" href="{{ $tile['url'] }}">
              <span class="dash-tile__glyph" aria-hidden="true">{{ strtoupper(substr($tile['label'], 0, 1)) }}</span>
              <span class="dash-tile__body">
                <strong>{{ $tile['label'] }}</strong>
                <span>{{ $tile['desc'] }}</span>
              </span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    <details class="card dash-access">
      <summary>
        <strong>Your access</strong>
        <span>{{ count($permissionLabels) }} permissions on this school</span>
      </summary>
      <div class="dash-access__list">
        @forelse($permissionLabels as $label)
          <span class="pill">{{ $label }}</span>
        @empty
          <span class="pill pill--muted">No permissions assigned</span>
        @endforelse
      </div>
    </details>
  @endif
@endsection

@section('head')
<style>
  .dash-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .dash-stat{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:14px 16px;position:relative;overflow:hidden}
  .dash-stat::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--brand)}
  .dash-stat--accent::before{background:var(--accent)}
  .dash-stat--warning::before{background:var(--warning)}
  .dash-stat__value{font-size:22px;font-weight:800;line-height:1.15;font-family:var(--font-display);color:var(--ink);letter-spacing:-.02em}
  .dash-stat__label{margin-top:6px;font-size:13px;font-weight:700;color:var(--brand)}
  .dash-stat__hint{margin-top:2px;font-size:12px;color:var(--muted)}
  .dash-chart-card__head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
  .dash-bars{display:flex;flex-direction:column;gap:10px;margin-top:16px}
  .dash-bar__meta{display:flex;justify-content:space-between;gap:10px;font-size:13px;margin-bottom:4px}
  .dash-bar__meta strong{font-variant-numeric:tabular-nums}
  .dash-bar__track{height:10px;border-radius:999px;background:var(--surface-2, #eef2f4);overflow:hidden}
  .dash-bar__track span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,var(--brand),color-mix(in srgb,var(--brand) 65%, var(--accent)))}
  .dash-cols{display:flex;align-items:flex-end;gap:10px;height:160px;margin-top:18px;padding:0 4px}
  .dash-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:8px;min-width:0}
  .dash-col__bar{width:100%;max-width:36px;border-radius:8px 8px 4px 4px;background:linear-gradient(180deg,var(--accent),color-mix(in srgb,var(--brand) 70%, var(--accent)));box-shadow:0 6px 14px color-mix(in srgb, var(--brand) 18%, transparent)}
  .dash-col__label{font-size:11px;color:var(--muted);font-weight:600}
  .dash-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
  .dash-tile{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--line);border-radius:var(--radius);background:color-mix(in srgb, var(--surface) 88%, var(--brand-soft));color:inherit;transition:border-color .15s, transform .12s, background .15s}
  .dash-tile:hover{border-color:color-mix(in srgb, var(--brand) 35%, var(--line));background:var(--brand-soft);transform:translateY(-1px)}
  .dash-tile__glyph{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--brand);color:var(--on-brand);font-weight:800;font-size:14px;flex-shrink:0}
  .dash-tile__body{display:flex;flex-direction:column;min-width:0;line-height:1.25}
  .dash-tile__body strong{font-size:14px;color:var(--ink)}
  .dash-tile__body span{font-size:12px;color:var(--muted);margin-top:2px}
  .dash-access summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .dash-access summary::-webkit-details-marker{display:none}
  .dash-access summary span{font-size:13px;color:var(--muted)}
  .dash-access__list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
  @@media(max-width:800px){
    .dash-stats{grid-template-columns:repeat(2,1fr)}
    .dash-cols{height:140px}
  }
</style>
@endsection
