@php
  $size = $size ?? 'md';
  $px = $size === 'lg' ? 72 : ($size === 'sm' ? 40 : 56);
  $fs = $size === 'lg' ? 22 : ($size === 'sm' ? 13 : 16);
@endphp
<div class="school-badge" style="display:flex;align-items:center;gap:14px">
  @if($school->logoUrl())
    <img src="{{ $school->logoUrl() }}" alt="{{ $school->name }} crest" style="width:{{ $px }}px;height:{{ $px }}px;object-fit:contain;border-radius:12px;border:1px solid var(--line);background:var(--surface)">
  @else
    <div aria-hidden="true" style="width:{{ $px }}px;height:{{ $px }}px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:{{ $fs }}px;letter-spacing:.04em;color:var(--on-brand);background:var(--brand);border:3px solid var(--accent);flex-shrink:0">
      {{ $school->badgeLabel() }}
    </div>
  @endif
  <div style="min-width:0">
    <div style="font-family:var(--font-display);font-weight:800;font-size:{{ $size === 'lg' ? '22px' : '16px' }};color:var(--ink);line-height:1.2">{{ $school->name }}</div>
    @if(!empty($showMotto) && $school->motto)
      <div style="color:var(--muted);font-size:13px;margin-top:4px;font-style:italic">{{ $school->motto }}</div>
    @endif
  </div>
</div>
