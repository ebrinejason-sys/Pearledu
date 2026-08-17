@extends('layouts.app')
@section('title', 'Attendance · My portal')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school?->name }}</p>
      <h1 class="page-header__title">Attendance</h1>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">Recent attendance for the selected learner.</p>
    </div>
  </div>

  @include('app.portal._learner_switcher')

  @if(!$student)
    <div class="card"><p>No linked learner yet.</p></div>
  @else
    <div class="card">
      <table>
        <thead><tr><th>Date</th><th>Status</th><th>Reason</th></tr></thead>
        <tbody>
        @forelse($records as $row)
          <tr>
            <td>{{ $row->attended_on?->format('D, j M Y') }}</td>
            <td><span class="pill pill--{{ $row->status === 'present' ? 'success' : ($row->status === 'late' ? 'warning' : 'danger') }}">{{ $row->status }}</span></td>
            <td>{{ $row->reason ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No attendance records yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  @endif
@endsection
