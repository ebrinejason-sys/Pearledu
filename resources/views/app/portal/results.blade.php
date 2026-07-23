@extends('layouts.app')
@section('title', 'Results')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">Results</h1>
    </div>
    <div class="page-header__actions"><a class="btn ghost" href="{{ route('app.portal.home', ['student_id' => $student->id]) }}">Portal home</a></div>
  </div>
  @include('app.portal._learner_switcher')
  <div class="card">
    <table>
      <thead><tr><th>Period</th><th>Subject</th><th>Score</th><th>Grade</th><th>Comment</th></tr></thead>
      <tbody>
        @forelse($marks as $mark)
          <tr>
            <td>{{ $mark->period?->name ?? '—' }}</td>
            <td>{{ $mark->subject?->name ?? '—' }}</td>
            <td>{{ $mark->score }}</td>
            <td>{{ $mark->grade ?? '—' }}</td>
            <td>{{ $mark->comment ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5">No results yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
