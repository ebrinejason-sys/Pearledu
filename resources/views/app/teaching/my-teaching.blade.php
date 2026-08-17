@extends('layouts.app')
@section('title','My Teaching · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">{{ $greeting }}</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Your teaching for today — assigned classes and subjects only.</p>
    </div>
    <div class="page-header__actions">
      <a class="btn" href="{{ route('app.attendance.index') }}">Take attendance</a>
      <a class="btn ghost" href="{{ route('app.assessment.marks') }}">Enter marks</a>
    </div>
  </div>

  @if(empty($workspace))
    <div class="card">
      <p style="margin:0;color:var(--muted)">No current teaching assignments. Ask the Director of Studies to assign you a class and subject.</p>
    </div>
  @else
    <div class="card">
      <h2 style="margin-top:0;font-size:18px">Today’s lessons</h2>
      @forelse($workspace['lessons'] as $lesson)
        <p style="margin:0 0 8px">
          <strong>{{ $lesson->period?->name ?? 'Period' }}</strong>
          @if($lesson->period?->starts_at) · {{ $lesson->period->starts_at }}@endif
          — {{ $lesson->subject?->name ?? 'Subject' }}
          · {{ $lesson->schoolClass?->displayName() ?? 'Class' }}
          @if($lesson->room) · {{ $lesson->room->name }}@endif
        </p>
      @empty
        <p style="color:var(--muted);margin:0">Nothing on the timetable for today.</p>
      @endforelse
    </div>

    <div class="card">
      <h2 style="margin-top:0;font-size:18px">My classes</h2>
      @forelse($workspace['classes'] as $row)
        <p style="margin:0 0 8px"><strong>{{ $row['class'] }}</strong> — {{ implode(', ', $row['subjects']) }}</p>
      @empty
        <p style="color:var(--muted);margin:0">No assigned classes this term.</p>
      @endforelse
      <p style="margin:12px 0 0;display:flex;flex-wrap:wrap;gap:8px">
        <a class="btn ghost" href="{{ route('app.lms.index') }}">Upload material</a>
        <a class="btn ghost" href="{{ route('app.cbt.index') }}">CBT exams</a>
      </p>
    </div>
  @endif
@endsection
