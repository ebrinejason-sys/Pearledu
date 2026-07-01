@php
    $brandHref = $brandHref ?? (auth()->check()
        ? ((($nav['zone'] ?? '') === 'platform') ? route('platform.dashboard') : route('app.home'))
        : url('/'));
@endphp
<a href="{{ $brandHref }}" class="brand">
  <img src="{{ asset('images/brand/logo.png') }}" alt="" class="brand__mark" width="36" height="36">
  <span class="brand__wordmark">Pearl<b>Edu</b></span>
</a>
