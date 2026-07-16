@php
    $brandHref = $brandHref ?? (auth()->check()
        ? ((($nav['zone'] ?? '') === 'platform') ? route('platform.dashboard') : route('app.home'))
        : url('/'));
@endphp
<a href="{{ $brandHref }}" class="brand">
  @include('layouts.partials.logo', ['height' => 28, 'color' => 'currentColor', 'label' => 'PearlEdu'])
  <span class="brand__wordmark">Pearl<b>Edu</b></span>
</a>
