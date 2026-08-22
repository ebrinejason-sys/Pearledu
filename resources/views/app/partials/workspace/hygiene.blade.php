@php
  $hy = $workspace['hygiene'];
  $compact = $compact ?? false;
  $pct = (int) ($hy['percent'] ?? 0);
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-hygiene">
  <div class="dash-chart-card__head">
    <div>
      <p class="page-header__eyebrow">School admin</p>
      <h2 id="ws-hygiene" class="ws-block__title">Setup &amp; data hygiene</h2>
    </div>
    @if(!empty($hy['setup_url']))
      <a class="btn ghost ws-cta" href="{{ $hy['setup_url'] }}">Setup wizard</a>
    @endif
  </div>

  @if($compact)
    <p class="ws-strip-meta">Setup {{ $pct }}% complete</p>
  @else
    <div class="ws-hero">
      <div class="ws-ring" role="img" aria-label="Setup {{ $pct }} percent complete" style="background:conic-gradient(var(--accent) 0 {{ $pct }}%, var(--surface-2) {{ $pct }}% 100%)">
        <div class="ws-ring__hole">
          <strong>{{ $pct }}%</strong>
          <span>complete</span>
        </div>
      </div>
      <div>
        <p>Keep the engine running. Marks stay with teachers and DOS. Fees stay with the bursar. You remain break-glass.</p>
        <ul class="ws-checks">
          @foreach($hy['checks'] as $check)
            <li class="{{ $check['done'] ? 'is-done' : 'is-open' }}">
              <span class="pill {{ $check['done'] ? 'pill--success' : 'pill--warning' }}">{{ $check['done'] ? 'done' : 'needed' }}</span>
              @if(!empty($check['url']) && ! $check['done'])
                <a href="{{ $check['url'] }}">{{ $check['label'] }}</a>
              @else
                {{ $check['label'] }}
              @endif
            </li>
          @endforeach
        </ul>
      </div>
    </div>

    <div class="hygiene-tiles">
      @foreach($hy['tiles'] as $tile)
        <a class="hygiene-tile hygiene-tile--{{ $tile['tone'] }}" href="{{ $tile['url'] ?? '#' }}">
          <strong>{{ $tile['count'] }}</strong>
          <span>{{ $tile['label'] }}</span>
          <span class="ws-hint">{{ $tile['hint'] }}</span>
        </a>
      @endforeach
    </div>
  @endif
</section>
