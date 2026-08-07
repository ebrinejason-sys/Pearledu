@props([
    'height' => 30,
    'color' => 'currentColor',
    'label' => 'PearlEdu',
])
{{-- $color kept for call-site compatibility; raster logo uses its own brand colors --}}
<img
  src="{{ asset('images/brand/logo-mark.png') }}"
  alt="{{ $label }}"
  {{ $attributes->merge(['class' => 'vx-logo']) }}
  style="--vx-logo-h:{{ (int) $height }}px;height:{{ (int) $height }}px;width:auto;display:block;flex-shrink:0"
  width="{{ (int) $height }}"
  height="{{ (int) $height }}"
  decoding="async"
/>
