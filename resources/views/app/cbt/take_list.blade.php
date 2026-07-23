@extends('layouts.app')
@section('title','CBT exams · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Learning</p><h2 class="page-header__title">Available exams</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <p style="color:var(--muted);margin-top:0">Signed in as {{ $student->full_name }}</p>
  <div class="card">
    <table>
      <thead><tr><th>Exam</th><th>Duration</th><th>Questions</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($exams as $e)
        @php $att = $attempts->get($e->id); @endphp
        <tr>
          <td>{{ $e->title }}</td>
          <td>{{ $e->duration_minutes }}m</td>
          <td>{{ $e->questions_count }}</td>
          <td>
            @if($att?->status === 'submitted')
              Score {{ $att->score }} / {{ $att->max_score }}
            @elseif($att?->status === 'in_progress')
              In progress
            @else
              Not started
            @endif
          </td>
          <td>
            @if($att?->status === 'submitted')
              <a class="btn ghost" href="{{ route('app.cbt.attempts.result', $att) }}">View result</a>
            @elseif($att?->status === 'in_progress')
              <a class="btn" href="{{ route('app.cbt.attempts.take', $att) }}">Continue</a>
            @else
              <form method="post" action="{{ route('app.cbt.exams.start', $e) }}">@csrf
                <button class="btn" type="submit">Start</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--muted)">No published exams.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
