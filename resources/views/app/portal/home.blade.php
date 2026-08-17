@extends('layouts.app')
@section('title', 'My portal')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">{{ $student?->full_name ? 'Good day, '.explode(' ', $student->full_name)[0] : 'My portal' }}</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Results, attendance, fees and school notices for your linked learner{{ $learners->count() > 1 ? 's' : '' }}.</p>
    </div>
  </div>

  @if($learners->count() > 1)
    <h2 style="font-size:16px;margin:0 0 10px">My children</h2>
    <div class="child-cards">
      @foreach($learners as $learner)
        <a class="child-card" href="{{ route('app.portal.home', ['student_id' => $learner->id]) }}" @if((int)$student?->id === (int)$learner->id) aria-current="true" @endif>
          <strong>{{ $learner->full_name }}</strong>
          <span>{{ $learner->schoolClass?->displayName() ?? 'Class not set' }}</span>
        </a>
      @endforeach
    </div>
  @elseif($student)
    <p class="page-header__eyebrow" style="margin-bottom:12px">{{ $student->full_name }}@if($student->schoolClass) · {{ $student->schoolClass->displayName() }}@endif</p>
  @endif

  @if(!$student)
    <div class="card"><p>No linked learner yet. Ask the school to link your account to a student.</p></div>
  @else
    <div class="grid g2">
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Quick links</h2>
        <p style="display:flex;flex-wrap:wrap;gap:8px">
          <a class="btn" href="{{ route('app.portal.results', ['student_id' => $student->id]) }}">Results</a>
          @if(!empty($canViewAttendance))
            <a class="btn ghost" href="{{ route('app.portal.attendance', ['student_id' => $student->id]) }}">Attendance</a>
          @endif
          @if(!empty($canViewFees))
            <a class="btn ghost" href="{{ route('app.portal.fees', ['student_id' => $student->id]) }}">Fees</a>
          @endif
          <a class="btn ghost" href="{{ route('app.portal.timetable', ['student_id' => $student->id]) }}">Timetable</a>
          <a class="btn ghost" href="{{ route('app.portal.announcements', ['student_id' => $student->id]) }}">Announcements</a>
        </p>
      </div>
      @if(!empty($canViewFees))
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Fee snapshot</h2>
        @forelse($invoices as $inv)
          <p style="margin:0 0 8px;display:flex;justify-content:space-between;gap:12px">
            <span>{{ $inv->reference }} <span class="pill">{{ $inv->status }}</span></span>
            <strong>UGX {{ number_format((float)$inv->balance, 0) }}</strong>
          </p>
        @empty
          <p style="color:var(--muted);margin:0">No invoices yet.</p>
        @endforelse
      </div>
      @endif
    </div>

    <div class="grid g2" style="margin-top:8px">
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Recent results</h2>
        @forelse($resultsPreview as $mark)
          <p style="margin:0 0 8px">{{ $mark->subject?->name ?? 'Subject' }} — <strong>{{ $mark->score }}</strong> @if($mark->grade)({{ $mark->grade }})@endif</p>
        @empty
          <p style="color:var(--muted);margin:0">No marks published yet.</p>
        @endforelse
      </div>
      <div class="card">
        <h2 style="margin-top:0;font-size:18px">Attendance</h2>
        @if(empty($canViewAttendance))
          <p style="color:var(--muted);margin:0">Attendance is not available on this account.</p>
        @else
          @forelse($attendancePreview ?? [] as $row)
            <p style="margin:0 0 8px">{{ $row->attended_on?->format('j M') }} — <span class="pill">{{ $row->status }}</span></p>
          @empty
            <p style="color:var(--muted);margin:0">No recent attendance.</p>
          @endforelse
          <p style="margin:12px 0 0"><a href="{{ route('app.portal.attendance', ['student_id' => $student->id]) }}">View all attendance</a></p>
        @endif
      </div>
    </div>

    <div class="card" style="margin-top:8px">
      <h2 style="margin-top:0;font-size:18px">Announcements</h2>
      @forelse($announcements as $a)
        <p style="margin:0 0 10px"><strong>{{ $a->title }}</strong><br><span style="color:var(--muted);font-size:13px">{{ \Illuminate\Support\Str::limit($a->body, 120) }}</span></p>
      @empty
        <p style="color:var(--muted);margin:0">No announcements.</p>
      @endforelse
    </div>
  @endif
@endsection
