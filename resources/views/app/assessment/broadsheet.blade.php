@extends('layouts.app')
@section('title','Broadsheet · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Assessment</p>
      <h2 class="page-header__title">Broadsheet</h2>
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

  <div class="card" style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          @foreach($matrix['subjects'] as $subject)<th>{{ $subject->code ?: $subject->name }}</th>@endforeach
        </tr>
      </thead>
      <tbody>
      @forelse($matrix['students'] as $student)
        <tr>
          <td>{{ $student->full_name }}</td>
          @foreach($matrix['subjects'] as $subject)
            <td>{{ $matrix['scores'][$student->id][$subject->id] ?? '—' }}</td>
          @endforeach
        </tr>
      @empty
        <tr><td style="color:var(--muted)">No marks for this class/period.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
