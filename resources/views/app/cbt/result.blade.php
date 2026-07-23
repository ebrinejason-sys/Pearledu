@extends('layouts.app')
@section('title','Result · '.$attempt->exam->title)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">CBT</p><h2 class="page-header__title">{{ $attempt->exam->title }}</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="card">
    <p style="margin-top:0;font-size:1.25rem"><strong>{{ $attempt->score }}</strong> / {{ $attempt->max_score }}</p>
    <p style="color:var(--muted)">Submitted {{ $attempt->submitted_at?->format('Y-m-d H:i') }}</p>
    <p><a class="btn ghost" href="{{ route('app.cbt.index') }}">Back to exams</a></p>
  </div>
@endsection
