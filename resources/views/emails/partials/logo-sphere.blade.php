@props([
    'height' => 28,
    'color' => '#9FE7F5',
])
{{-- $color kept for call-site compatibility --}}
<img
  src="{{ asset('images/brand/logo-mark.png') }}"
  alt="{{ config('app.name') }} logo"
  style="height:{{ (int) $height }}px;width:auto;display:block"
  width="{{ (int) $height }}"
  height="{{ (int) $height }}"
/>
