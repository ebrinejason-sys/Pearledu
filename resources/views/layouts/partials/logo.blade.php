@props([
    'height' => 30,
    'color' => 'currentColor',
    'label' => 'VoxSign',
])
@php
    $mask = asset('images/brand/voxsign-logo.svg');
@endphp
<span
  {{ $attributes->merge(['class' => 'vx-logo']) }}
  role="img"
  aria-label="{{ $label }}"
  style="--vx-logo-h:{{ (int) $height }}px;background:{{ $color }};-webkit-mask-image:url('{{ $mask }}');mask-image:url('{{ $mask }}');"
></span>
