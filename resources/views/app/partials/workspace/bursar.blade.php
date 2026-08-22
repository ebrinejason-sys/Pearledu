@php
  $b = $workspace['bursar'];
  $compact = $compact ?? false;
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-bursar">
  <div class="dash-chart-card__head">
    <h2 id="ws-bursar" class="ws-block__title">School fees</h2>
    <a class="btn ghost ws-cta" href="{{ route('app.fees.index') }}">Open fees</a>
  </div>
  @if($compact)
    <p class="ws-strip-meta">{{ $b['rate'] }}% collected · {{ $b['pending'] }} pending</p>
  @else
    <div class="workspace-kpis">
      <div class="dash-stat"><div class="dash-stat__value">UGX {{ number_format($b['expected'], 0) }}</div><div class="dash-stat__label">Expected</div></div>
      <div class="dash-stat dash-stat--accent"><div class="dash-stat__value">UGX {{ number_format($b['collected'], 0) }}</div><div class="dash-stat__label">Collected</div></div>
      <div class="dash-stat dash-stat--warning"><div class="dash-stat__value">UGX {{ number_format($b['outstanding'], 0) }}</div><div class="dash-stat__label">Outstanding</div></div>
      <div class="dash-stat"><div class="dash-stat__value">{{ $b['rate'] }}%</div><div class="dash-stat__label">Collection rate</div></div>
    </div>
    @if($b['pending'] > 0)
      <p><a class="btn accent ws-cta" href="{{ route('app.fees.index') }}#payments">{{ $b['pending'] }} payment(s) pending verification</a></p>
    @endif
  @endif
</section>
