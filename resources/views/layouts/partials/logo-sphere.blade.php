@props([
    'height' => 30,
    'color' => 'currentColor',
    'showWordmark' => false,
    'wordmark' => 'VoxSign',
    'wordmarkColor' => null,
])
@php
    $wordmarkColor = $wordmarkColor ?? $color;
    $label = $showWordmark ? '' : ($wordmark.' logo');
@endphp
<span {{ $attributes->merge(['class' => 'vx-brand-lockup']) }} style="display:inline-flex;align-items:center;gap:10px">
  <img
    src="{{ asset('images/brand/logo-mark.png') }}"
    @if($label) alt="{{ $label }}" @else alt="" aria-hidden="true" @endif
    style="height:{{ (int) $height }}px;width:auto;display:block;flex-shrink:0"
    width="{{ (int) $height }}"
    height="{{ (int) $height }}"
    decoding="async"
  />
  @if($showWordmark)
    <span style="font-family:var(--display,'Google Sans',system-ui,sans-serif);font-weight:600;font-size:{{ max(14, round($height * 0.58)) }}px;color:{{ $wordmarkColor }};line-height:1">{{ $wordmark }}</span>
  @endif
</span>
