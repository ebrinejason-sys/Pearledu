@props([
    'height' => 28,
    'color' => '#9FE7F5',
])
@include('layouts.partials.logo', [
    'height' => $height,
    'color' => $color,
    'label' => config('app.name').' logo',
])
