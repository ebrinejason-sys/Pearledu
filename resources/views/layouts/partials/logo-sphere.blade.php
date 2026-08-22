@props([
    'height' => 30,
    'color' => 'currentColor',
    'showWordmark' => false,
    'wordmark' => 'PearlEdu',
    'wordmarkColor' => null,
])
@php
    $wordmarkColor = $wordmarkColor ?? $color;
@endphp
<span {{ $attributes->merge(['class' => 'vx-brand-lockup']) }} style="display:inline-flex;align-items:center;gap:10px">
  @include('layouts.partials.logo', [
      'height' => $height,
      'color' => $color,
      'label' => $showWordmark ? '' : ($wordmark.' logo'),
  ])
  @if($showWordmark)
    <span style="font-family:var(--display,'Google Sans',system-ui,sans-serif);font-weight:700;font-size:{{ max(14, (int) round($height * 0.55)) }}px;color:{{ $wordmarkColor }};line-height:1;letter-spacing:-.02em">{{ $wordmark }}</span>
  @endif
</span>
