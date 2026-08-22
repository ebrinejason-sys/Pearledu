@extends('layouts.app')
@section('title','Enrollments · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Learners</p>
      <h2 class="page-header__title">Enrollments</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="card">
    <h3 style="margin-top:0">Enroll student</h3>
    <form method="post" action="{{ route('app.enrollments.store') }}">
      @csrf
      <label>Student</label>
      <select name="student_id" required>
        @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach
      </select>
      <label>Class</label>
      <select name="class_id" required>
        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
      </select>
      <label>Academic year</label>
      <select name="academic_year_id" required>
        @foreach($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
      </select>
      <p style="margin-top:14px"><button class="btn" type="submit">Enroll</button></p>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Records</h3>
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Year</th><th>Status</th></tr></thead>
      <tbody>
      @forelse($enrollments as $e)
        <tr>
          <td>{{ $e->student?->full_name }}</td>
          <td>{{ $e->schoolClass?->name }}</td>
          <td>{{ $e->academicYear?->name }}</td>
          <td><span class="pill">{{ $e->status }}</span></td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No enrollments.</td></tr>
      @endforelse
      </tbody>
    </table>
    {{ $enrollments->links() }}
  </div>
@endsection
