@extends('layouts.app')
@section('title','Clinic · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Operations</p><h2 class="page-header__title">Clinic</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Record visit</h3>
      <form method="post" action="{{ route('app.clinic.visits.store') }}">@csrf
        <label>Student</label>
        <select name="student_id" required>@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach</select>
        <label>Visited at</label><input type="datetime-local" name="visited_at">
        <label>Complaint</label><input name="complaint">
        <label>Notes</label><textarea name="notes" rows="3"></textarea>
        <p style="margin-top:14px"><button class="btn" type="submit">Save</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Visits</h3>
      <table>
        <thead><tr><th>When</th><th>Student</th><th>Complaint</th></tr></thead>
        <tbody>
        @forelse($visits as $v)
          <tr>
            <td>{{ $v->visited_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $v->student?->full_name }}</td>
            <td>{{ $v->complaint ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">None.</td></tr>
        @endforelse
        </tbody>
      </table>
      {{ $visits->links() }}
    </div>
  </div>
@endsection
