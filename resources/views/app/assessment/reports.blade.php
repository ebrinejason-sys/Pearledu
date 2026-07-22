@extends('layouts.app')
@section('title','Report cards · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Assessment</p>
      <h2 class="page-header__title">Report cards</h2>
    </div>
  </div>

  <div class="card">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div>
        <label>Period</label>
        <select name="period_id" onchange="this.form.submit()">
          @foreach($periods as $p)<option value="{{ $p->id }}" @selected($periodId===$p->id)>{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div>
        <label>Class</label>
        <select name="class_id" onchange="this.form.submit()">
          @foreach($classes as $c)<option value="{{ $c->id }}" @selected($classId===$c->id)>{{ $c->name }}</option>@endforeach
        </select>
      </div>
    </form>
  </div>

  <div class="card">
    <table>
      <thead><tr><th>Position</th><th>Student</th><th>Average</th><th>Subjects</th></tr></thead>
      <tbody>
      @forelse($reports as $row)
        <tr>
          <td>{{ $row['position'] ?? '—' }}</td>
          <td>{{ $row['full_name'] }}</td>
          <td>{{ $row['average'] ?? '—' }}</td>
          <td>{{ $row['subject_count'] }}</td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No aggregates yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
