@php
  $url = $url ?? null;
  $initial = $initial ?? '?';
  $name = $name ?? '';
  $size = $size ?? 'md';
@endphp
<span class="face face--{{ $size }}" title="{{ $name }}">
  @if($url)
    <img src="{{ $url }}" alt="" width="40" height="40">
  @else
    <span aria-hidden="true">{{ $initial }}</span>
  @endif
</span>
