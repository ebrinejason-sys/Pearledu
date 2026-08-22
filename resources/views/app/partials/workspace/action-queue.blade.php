@php
  $compact = $compact ?? false;
@endphp
<section class="ws-block {{ $compact ? 'ws-block--strip' : '' }}" aria-labelledby="ws-attn">
  <div class="dash-chart-card__head">
    <h2 id="ws-attn" class="ws-block__title">Needs your attention</h2>
  </div>
  <ul class="attn-queue">
    @forelse($actionItems ?? [] as $item)
      @php
        $tone = match ($item['priority'] ?? 'medium') {
          'high' => 'danger',
          'low' => 'muted',
          default => 'warning',
        };
      @endphp
      <li class="attn-queue__item attn-queue__item--{{ $tone }}">
        <div>
          <span class="pill pill--{{ $tone === 'muted' ? 'muted' : $tone }}">{{ $item['priority'] }}</span>
          <strong>{{ $item['title'] }}</strong>
          <p>{{ $item['description'] }}</p>
        </div>
        @if(!empty($item['action_url']))
          <a class="btn {{ $tone === 'danger' ? 'accent' : 'ghost' }} ws-cta" href="{{ $item['action_url'] }}">Open</a>
        @endif
      </li>
    @empty
      <li class="attn-queue__empty">You’re caught up for now.</li>
    @endforelse
  </ul>
</section>
