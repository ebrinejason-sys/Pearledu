@extends('layouts.app')
@section('title', 'My portal')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">My portal</h1>
    </div>
  </div>

  @include('app.portal._learner_switcher')

  @if(!$student)
    <div class="card"><p>No linked learner yet. Ask the school to link your account to a student.</p></div>
  @else
    <div class="grid g2">
      <div class="card">
        <h3 style="margin-top:0">Quick links</h3>
        <p style="display:flex;flex-wrap:wrap;gap:8px">
          <a class="btn" href="{{ route('app.portal.results', ['student_id' => $student->id]) }}">Results</a>
          @if(!empty($canViewFees))
            <a class="btn ghost" href="{{ route('app.portal.fees', ['student_id' => $student->id]) }}">Fees</a>
          @endif
          <a class="btn ghost" href="{{ route('app.portal.timetable', ['student_id' => $student->id]) }}">Timetable</a>
          <a class="btn ghost" href="{{ route('app.portal.announcements', ['student_id' => $student->id]) }}">Announcements</a>
        </p>
      </div>
      @if(!empty($canViewFees))
      <div class="card">
        <h3 style="margin-top:0">Fee snapshot</h3>
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
        <h3 style="margin-top:0">Recent results</h3>
        @forelse($resultsPreview as $mark)
          <p style="margin:0 0 8px">{{ $mark->subject?->name ?? 'Subject' }} — <strong>{{ $mark->score }}</strong> @if($mark->grade)({{ $mark->grade }})@endif</p>
        @empty
          <p style="color:var(--muted);margin:0">No marks published yet.</p>
        @endforelse
      </div>
      <div class="card">
        <h3 style="margin-top:0">Announcements</h3>
        @forelse($announcements as $a)
          <p style="margin:0 0 10px"><strong>{{ $a->title }}</strong><br><span style="color:var(--muted);font-size:13px">{{ \Illuminate\Support\Str::limit($a->body, 120) }}</span></p>
        @empty
          <p style="color:var(--muted);margin:0">No announcements.</p>
        @endforelse
      </div>
    </div>
  @endif
@endsection
