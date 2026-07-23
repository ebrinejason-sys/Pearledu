@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">Announcements</h1>
    </div>
    <div class="page-header__actions"><a class="btn ghost" href="{{ route('app.portal.home', ['student_id' => $student->id]) }}">Portal home</a></div>
  </div>
  @include('app.portal._learner_switcher')
  @forelse($announcements as $a)
    <div class="card">
      <h3 style="margin:0 0 6px">{{ $a->title }}</h3>
      <p style="margin:0 0 8px;color:var(--muted);font-size:12px">{{ $a->created_at?->format('d M Y H:i') }} · {{ $a->audience }}</p>
      <p style="margin:0;white-space:pre-wrap">{{ $a->body }}</p>
    </div>
  @empty
    <div class="card"><p>No announcements for you yet.</p></div>
  @endforelse
@endsection
