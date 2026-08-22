@php
  $ops = $workspace['operationsLead'];
  $compact = $compact ?? false;
  $mode = $ops['mode'] ?? 'ops';
  $isLogistics = $mode === 'logistics';
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-ops">
  <div class="dash-chart-card__head">
    <div>
      <p class="page-header__eyebrow">{{ $isLogistics ? 'Daily logistics' : 'Approvals' }}</p>
      <h2 id="ws-ops" class="ws-block__title">{{ $isLogistics ? 'Today’s board' : 'Operations' }}</h2>
    </div>
  </div>

  @if($compact)
    <p class="ws-strip-meta">{{ number_format($ops['students']) }} learners · attendance {{ $ops['attendance_pct'] !== null ? $ops['attendance_pct'].'%' : '—' }}</p>
  @else
    <div class="workspace-kpis ws-kpis-thin">
      <div class="dash-stat"><div class="dash-stat__value">{{ number_format($ops['students']) }}</div><div class="dash-stat__label">Learners</div></div>
      <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">{{ $ops['attendance_pct'] !== null ? $ops['attendance_pct'].'%' : '—' }}</div><div class="dash-stat__label">Attendance today</div></div>
      @if(!empty($ops['finance']))
        <div class="dash-stat"><div class="dash-stat__value">{{ $ops['finance']['rate'] }}%</div><div class="dash-stat__label">Fee collection</div></div>
      @endif
      @if($ops['academic_mean'] !== null)
        <div class="dash-stat"><div class="dash-stat__value">{{ $ops['academic_mean'] }}%</div><div class="dash-stat__label">Published mean</div></div>
      @endif
    </div>

    @if(!$isLogistics)
      <div class="ops-cols">
        <div class="ops-col">
          <h3>Promotions pending</h3>
          @forelse($ops['promotions_pending'] ?? [] as $batch)
            <p>{{ $batch['from'] ?? 'Year' }} → {{ $batch['to'] ?? 'Year' }} · {{ $batch['items'] }} learners</p>
          @empty
            <p class="ws-hint">No batches waiting. DOS prepares; you commit.</p>
          @endforelse
          @if(!empty($ops['promotions_url']))
            <a class="btn accent ws-cta" href="{{ $ops['promotions_url'] }}">Commit promotions</a>
          @endif
        </div>
        <div class="ops-col">
          <h3>Helpdesk escalations</h3>
          @forelse($ops['helpdesk_open'] ?? [] as $ticket)
            <p><strong>{{ $ticket['subject'] }}</strong><span class="ws-hint"> · {{ $ticket['from'] ?? 'Staff' }}</span></p>
          @empty
            <p class="ws-hint">No open tickets.</p>
          @endforelse
          @if(!empty($ops['helpdesk_url']))
            <a class="btn ghost ws-cta" href="{{ $ops['helpdesk_url'] }}">Open helpdesk</a>
          @endif
        </div>
        <div class="ops-col">
          <h3>Attendance gaps</h3>
          @forelse($ops['attendance_gaps'] ?? [] as $gap)
            <p>{{ $gap['class'] }} · unmarked</p>
          @empty
            <p class="ws-hint">Every class has a register today.</p>
          @endforelse
          <a class="btn ghost ws-cta" href="{{ route('app.attendance.index') }}">Attendance</a>
        </div>
      </div>
    @else
      @if(!empty($ops['clock']))
        <h3 class="ws-sub">Staff clock</h3>
        <p>{{ $ops['clock']['in'] }} in · {{ $ops['clock']['out'] }} out · {{ $ops['clock']['staff'] }} on staff list
          @if(!empty($ops['clock_url'])) · <a href="{{ $ops['clock_url'] }}">Clock desk</a>@endif
        </p>
        <div class="face-row">
          @foreach($ops['clock']['people'] as $person)
            <div class="face-chip">
              @include('app.partials.person-face', ['url' => $person['photo'], 'initial' => $person['initial'], 'name' => $person['name']])
              <span>{{ $person['name'] }}</span>
              <span class="pill {{ $person['direction'] === 'in' ? 'pill--success' : 'pill--muted' }}">{{ $person['direction'] }} {{ $person['at'] }}</span>
            </div>
          @endforeach
        </div>
      @endif

      <h3 class="ws-sub">Classes with a slot and no teacher</h3>
      @forelse($ops['uncovered'] ?? [] as $slot)
        <p>{{ $slot['period'] ?? 'Period' }} · {{ $slot['class'] }} · {{ $slot['subject'] ?? '—' }}</p>
      @empty
        <p class="ws-hint">Every timed lesson has a teacher on today’s grid.</p>
      @endforelse
      @if(!empty($ops['timetable_url']))
        <a class="btn ghost ws-cta" href="{{ $ops['timetable_url'] }}">Open timetable</a>
      @endif

      <h3 class="ws-sub">Absence heatmap</h3>
      <div class="heat" role="img" aria-label="Today’s attendance by class">
        @forelse($ops['heatmap'] ?? [] as $cell)
          <a class="heat__cell heat__cell--{{ $cell['tone'] }}" href="{{ route('app.attendance.index', ['class_id' => $cell['class_id']]) }}">
            <strong>{{ $cell['class'] }}</strong>
            <span>{{ $cell['pct'] === null ? 'unmarked' : $cell['pct'].'%' }}</span>
          </a>
        @empty
          <p class="ws-hint">No classes yet.</p>
        @endforelse
      </div>

      <h3 class="ws-sub">Escalated tickets</h3>
      @forelse($ops['helpdesk_open'] ?? [] as $ticket)
        <p><strong>{{ $ticket['subject'] }}</strong><span class="ws-hint"> · {{ $ticket['from'] ?? 'Staff' }}</span></p>
      @empty
        <p class="ws-hint">Nothing in the helpdesk queue.</p>
      @endforelse
    @endif
  @endif
</section>
