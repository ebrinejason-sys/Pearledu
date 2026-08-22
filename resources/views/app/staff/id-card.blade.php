@extends('layouts.app')
@section('title', 'Staff ID · '.$staff->full_name)
@section('content')
  <div class="page-header no-print">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('app.staff.index') }}">Staff</a></p>
      <h2 class="page-header__title">Printable staff ID</h2>
    </div>
    <div class="page-header__actions"><button class="btn accent" type="button" onclick="window.print()">Print</button></div>
  </div>
  <div class="id-sheet">
    <div class="id-card id-card--front">
      <p class="id-card__school">{{ $school->name }}</p>
      @if($staff->avatarUrl())
        <img src="{{ $staff->avatarUrl() }}" alt="" class="id-card__photo">
      @else
        <div class="id-card__photo id-card__photo--empty">{{ $staff->avatarInitial() }}</div>
      @endif
      <h3>{{ $staff->full_name }}</h3>
      <p>{{ $roles->implode(' · ') }}</p>
      <p class="muted">EMIS {{ $school->emis_number ?: '—' }}</p>
      <p class="muted">{{ \App\Support\Gender::label($staff->gender) }}</p>
    </div>
    <div class="id-card id-card--back">
      <p class="id-card__school">Scan to clock</p>
      <div class="id-card__qr">{!! $qr !!}</div>
      <p class="id-card__code"><code>{{ $badge->code }}</code></p>
      <p class="muted">Present this side to the secretary’s barcode reader.</p>
    </div>
  </div>
@endsection
@section('head')
<style>
  .id-sheet{display:flex;gap:24px;flex-wrap:wrap}
  .id-card{width:320px;min-height:200px;border:1px solid var(--line,#d0d5dd);border-radius:12px;padding:16px;background:#fff}
  .id-card__school{font-weight:700;margin:0 0 10px}
  .id-card__photo{width:72px;height:72px;border-radius:8px;object-fit:cover}
  .id-card__photo--empty{display:flex;align-items:center;justify-content:center;background:#eef2f6;font-size:24px}
  .id-card__qr svg{width:140px;height:140px}
  .id-card__code{font-size:18px;letter-spacing:.04em}
  .muted{color:var(--muted);font-size:13px}
  @media print {
    .no-print, .sidebar, .topbar, nav { display:none !important; }
    .id-sheet{break-inside:avoid}
  }
</style>
@endsection
