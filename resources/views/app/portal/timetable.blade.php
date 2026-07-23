@extends('layouts.app')
@section('title', 'Timetable')
@section('content')
  @php
    $days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
  @endphp
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">Timetable</h1>
    </div>
    <div class="page-header__actions"><a class="btn ghost" href="{{ route('app.portal.home', ['student_id' => $student->id]) }}">Portal home</a></div>
  </div>
  @include('app.portal._learner_switcher')
  <div class="card">
    <table>
      <thead><tr><th>Day</th><th>Period</th><th>Subject</th><th>Teacher</th><th>Room</th></tr></thead>
      <tbody>
        @forelse($slots as $slot)
          <tr>
            <td>{{ $days[$slot->day_of_week] ?? $slot->day_of_week }}</td>
            <td>{{ $slot->period?->name ?? $slot->period_id }}</td>
            <td>{{ $slot->subject?->name ?? '—' }}</td>
            <td>{{ $slot->teacher?->full_name ?? '—' }}</td>
            <td>{{ $slot->room?->name ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5">No timetable slots for this class yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
