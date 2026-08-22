@php
  $g = $workspace['governance'];
  $compact = $compact ?? false;
  $emis = $g['emis'] ?? $emis ?? null;
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-gov">
  <div class="dash-chart-card__head">
    <div>
      <p class="page-header__eyebrow">Governance</p>
      <h2 id="ws-gov" class="ws-block__title">School pulse</h2>
    </div>
  </div>

  @if($compact)
    <p class="ws-strip-meta">Attendance {{ $g['attendance_pct'] !== null ? $g['attendance_pct'].'%' : '—' }} · collection {{ $g['finance']['rate'] ?? '—' }}%</p>
  @else
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
        <div>
          <h3 class="ws-sub">Enrollment by class &amp; sex</h3>
          @php $enrollMax = max(1, (int) collect($emis['enrollment'])->max('total')); @endphp
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
            <p class="ws-hint">No enrollment yet.</p>
          @endforelse
        </div>
        <div class="ws-pulse">
          <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $g['attendance_pct'] !== null ? $g['attendance_pct'].'%' : '—' }}</div><div class="dash-stat__label">Attendance today</div></div>
          <div class="dash-stat"><div class="dash-stat__value">{{ $g['academic_mean'] !== null ? $g['academic_mean'].'%' : '—' }}</div><div class="dash-stat__label">Published mean</div></div>
          <div class="dash-stat"><div class="dash-stat__value">{{ $g['finance']['rate'] ?? 0 }}%</div><div class="dash-stat__label">Collection rate</div></div>
        </div>
      </div>
    @endif

    @if(!empty($g['clock_summary']))
      <p class="ws-hint">Staff clock today: {{ $g['clock_summary']['in'] }} in / {{ $g['clock_summary']['out'] }} out of {{ $g['clock_summary']['staff'] }} (view only).</p>
    @endif

    @if(!empty($g['exceptions']))
      <h3 class="ws-sub">Exception alerts</h3>
      <ul class="attn-queue">
        @foreach($g['exceptions'] as $item)
          <li class="attn-queue__item attn-queue__item--{{ $item['priority'] }}">
            <div>
              <span class="pill pill--{{ $item['priority'] === 'danger' ? 'danger' : 'warning' }}">{{ $item['owner'] }}</span>
              <strong>{{ $item['title'] }}</strong>
              <p>{{ $item['description'] }}</p>
            </div>
            @if(!empty($item['action_url']))
              <a class="btn ghost ws-cta" href="{{ $item['action_url'] }}">View</a>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
  @endif
</section>
