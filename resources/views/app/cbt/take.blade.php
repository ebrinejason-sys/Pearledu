@extends('layouts.app')
@section('title',$attempt->exam->title.' · CBT')
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">CBT</p><h2 class="page-header__title">{{ $attempt->exam->title }}</h2></div></div>
  <p style="color:var(--muted)">Duration {{ $attempt->exam->duration_minutes }} minutes · started {{ $attempt->started_at?->format('Y-m-d H:i') }}</p>
  <form method="post" action="{{ route('app.cbt.attempts.submit', $attempt) }}">@csrf
    @foreach($attempt->exam->questions as $i => $q)
      <div class="card" style="margin-bottom:12px">
        <p style="margin-top:0"><strong>Q{{ $i + 1 }}.</strong> {{ $q->prompt }} <span style="color:var(--muted)">({{ $q->points }} pts)</span></p>
        @foreach($q->choices ?? [] as $key => $label)
          <label style="display:block;margin:6px 0">
            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" required>
            {{ strtoupper($key) }}) {{ $label }}
          </label>
        @endforeach
      </div>
    @endforeach
    @if($attempt->exam->questions->isEmpty())
      <p style="color:var(--muted)">No questions yet.</p>
    @else
      <p><button class="btn" type="submit">Submit answers</button></p>
    @endif
  </form>
@endsection
