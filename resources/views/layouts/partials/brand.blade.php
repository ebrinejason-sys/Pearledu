@php
    $brandHref = $brandHref ?? (auth()->check()
        ? ((($nav['zone'] ?? '') === 'platform') ? route('platform.dashboard') : route('app.home'))
        : url('/'));
    $showTagline = $showTagline ?? false;
    $logoHeight = $logoHeight ?? ($showTagline ? 36 : 28);
@endphp
<a href="{{ $brandHref }}" class="brand{{ $showTagline ? ' brand--stacked' : '' }}">
  @include('layouts.partials.logo', ['height' => $logoHeight, 'color' => 'currentColor', 'label' => 'PearlEdu'])
  <span class="brand__copy">
    <span class="brand__wordmark">Pearl<b>Edu</b></span>
    @if($showTagline)
      <span class="brand__tagline">developed by Voxsign Technologies</span>
    @endif
  </span>
</a>

