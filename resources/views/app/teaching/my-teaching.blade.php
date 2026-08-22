@extends('layouts.app')
@section('title','My classes · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h1 class="page-header__title">My classes</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Subjects you teach, by class. Open marks from the class card so entry stays on your assignment.</p>
    </div>
    <div class="page-header__actions">
      <a class="btn" href="{{ route('app.attendance.index') }}">Take attendance</a>
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

    <div class="grid g2">
      @forelse($workspace['classes'] as $row)
        <div class="card">
          <h2 style="margin-top:0;font-size:18px">{{ $row['class'] }}</h2>
          <ul style="margin:0;padding-left:18px">
            @foreach($row['subjects'] as $subject)
              <li style="margin-bottom:6px">{{ $subject }}</li>
            @endforeach
          </ul>
          <p style="margin:12px 0 0;display:flex;flex-wrap:wrap;gap:8px">
            <a class="btn accent" href="{{ route('app.assessment.marks', ['class_id' => $row['class_id']]) }}">Enter marks</a>
            <a class="btn ghost" href="{{ route('app.attendance.index', ['class_id' => $row['class_id']]) }}">Attendance</a>
          </p>
        </div>
      @empty
        <div class="card"><p style="color:var(--muted);margin:0">No assigned classes this term.</p></div>
      @endforelse
    </div>
    <p style="margin:12px 0 0;display:flex;flex-wrap:wrap;gap:8px">
      <a class="btn ghost" href="{{ route('app.lms.index') }}">Upload material</a>
      <a class="btn ghost" href="{{ route('app.cbt.index') }}">CBT exams</a>
    </p>
  @endif
@endsection
