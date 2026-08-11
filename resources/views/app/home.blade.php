@extends('layouts.app')
@section('title', $school?->name ?? 'Home')
@section('content')
  @if(!$school)
    <div class="card"><p>No active school context for this account.</p></div>
  @else
    <div class="page-header">
      <div>
        <p class="page-header__eyebrow">School dashboard</p>
        <h1 class="page-header__title">{{ $school->name }}</h1>
        <p style="margin:6px 0 0;color:var(--muted);font-size:14px">{{ now()->timezone(config('app.timezone'))->format('l, j F Y') }}</p>
      </div>
      <div class="page-header__actions">
        @if(in_array('learners.manage', $permissions, true))
          <a class="btn" href="{{ route('app.students.index') }}">Students</a>
        @endif
        @if(in_array('finance.manage', $permissions, true))
          <a class="btn accent" href="{{ route('app.fees.index') }}">Fees</a>
        @endif
        @if(in_array('sms.send', $permissions, true))
          <a class="btn ghost" href="{{ route('app.sms') }}">Send SMS</a>
        @endif
      </div>
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
  @media(max-width:800px){
    .dash-stats{grid-template-columns:repeat(2,1fr)}
    .dash-cols{height:140px}
  }
</style>
@endsection
