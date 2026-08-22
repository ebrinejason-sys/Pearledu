@php
  $a = $workspace['academicLead'];
  $compact = $compact ?? false;
  $funnel = max(1, (int) ($a['funnel_total'] ?? 1));
  $draftPct = (int) round((($a['draft'] ?? 0) / $funnel) * 100);
  $subPct = (int) round((($a['submitted'] ?? 0) / $funnel) * 100);
  $verPct = (int) round((($a['verified'] ?? 0) / $funnel) * 100);
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-academic">
  <div class="dash-chart-card__head">
    <div>
      <p class="page-header__eyebrow">Academic OS</p>
      <h2 id="ws-academic" class="ws-block__title">{{ $a['period'] ?? 'No assessment period yet' }}</h2>
    </div>
    @if(!$compact)
      <a class="btn ghost ws-cta" href="{{ route('app.assessment.index') }}">Periods</a>
    @endif
  </div>

  @if($compact)
    <p class="ws-strip-meta">{{ $a['submitted_pct'] }}% submitted · {{ $a['draft'] }} still draft</p>
  @else
    <div class="funnel" role="img" aria-label="Marksheet funnel: {{ $a['draft'] }} draft, {{ $a['submitted'] }} submitted, {{ $a['verified'] }} verified">
      <div class="funnel__step"><span class="pill pill--warning">Draft</span><strong>{{ $a['draft'] }}</strong><div class="dash-bar__track"><span style="width:{{ $draftPct }}%"></span></div></div>
      <div class="funnel__step"><span class="pill pill--active">Submitted</span><strong>{{ $a['submitted'] }}</strong><div class="dash-bar__track"><span style="width:{{ $subPct }}%"></span></div></div>
      <div class="funnel__step"><span class="pill pill--success">Verified</span><strong>{{ $a['verified'] }}</strong><div class="dash-bar__track"><span style="width:{{ $verPct }}%"></span></div></div>
      <div class="funnel__step"><span class="pill pill--muted">Published</span><strong>{{ $a['published'] }}</strong><p class="ws-hint">{{ str_replace('_', ' ', $a['period_status'] ?? '—') }}</p></div>
    </div>

    @if(!empty($a['late_drafts']))
      <h3 class="ws-sub">Teachers with late drafts</h3>
      <div class="face-row">
        @foreach($a['late_drafts'] as $row)
          <div class="face-chip">
            @include('app.partials.person-face', ['url' => $row['photo'], 'initial' => $row['initial'], 'name' => $row['name']])
            <span>{{ $row['name'] }}</span>
            <span class="pill pill--warning">{{ $row['subject'] }} · {{ $row['class'] }}</span>
          </div>
        @endforeach
      </div>
      <p><a href="{{ route('app.assessment.index') }}">Return a sheet to draft</a> while mark entry is still open.</p>
    @endif

    @if(!empty($a['occupancy']))
      <h3 class="ws-sub">Teaching occupancy</h3>
      <p class="ws-hint">{{ $a['occupancy']['teachers'] }} teachers · {{ $a['occupancy']['collisions'] }} shared cells
        @if(!empty($a['occupancy']['url'])) · <a href="{{ $a['occupancy']['url'] }}">Full matrix</a>@endif
      </p>
      <div class="teach-matrix-wrap">
        <table class="teach-matrix">
          <thead>
            <tr>
              <th>Subject</th>
              @foreach($a['occupancy']['classes'] as $className)
                <th>{{ $className }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($a['occupancy']['cells'] as $row)
              <tr>
                <td>{{ $row['subject'] }}</td>
                @foreach($row['row'] as $cell)
                  <td class="{{ $cell['collision'] ? 'is-collision' : ($cell['count'] ? 'has-load' : 'is-empty') }}">
                    {{ $cell['count'] ? $cell['label'] : '' }}
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    @if(!empty($a['grade_bands']))
      <h3 class="ws-sub">Grade-band view (published, no names)</h3>
      @foreach($a['grade_bands'] as $band)
        <div class="dash-bar" style="margin-bottom:10px">
          <div class="dash-bar__meta"><span>{{ $band['subject'] }}</span><strong>{{ $band['total'] }} scores</strong></div>
          <div class="dash-bar__track dash-bar__track--split" role="img" aria-label="{{ $band['subject'] }}: U {{ $band['bands']['U'] }}, D {{ $band['bands']['D'] }}, C {{ $band['bands']['C'] }}, B {{ $band['bands']['B'] }}, A {{ $band['bands']['A'] }}">
            <span class="u" style="width:{{ $band['pct']['U'] }}%;background:var(--danger)"></span>
            <span class="d" style="width:{{ $band['pct']['D'] }}%;background:var(--warning)"></span>
            <span class="c" style="width:{{ $band['pct']['C'] }}%;background:var(--accent)"></span>
            <span class="b" style="width:{{ $band['pct']['B'] }}%;background:color-mix(in srgb, var(--success) 70%, var(--accent))"></span>
            <span class="a" style="width:{{ $band['pct']['A'] }}%;background:var(--success)"></span>
          </div>
        </div>
      @endforeach
    @endif
  @endif
</section>
