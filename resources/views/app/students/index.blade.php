@extends('layouts.app')
@section('title', 'Students')
@section('content')
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:16px">
    <div>
      <h2 style="margin:0">Students</h2>
      <p style="color:var(--muted);margin:6px 0 0">Learner records for this school</p>
    </div>
      <a class="btn" href="{{ route('app.students.create') }}">Add student</a>
      <a class="btn ghost" href="{{ route('app.students.import') }}">Import CSV</a>
  </div>

  <div class="card" style="margin-bottom:16px">
    <form method="get" action="{{ route('app.students.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:220px">
        <label>Search</label>
        <input name="q" value="{{ $q }}" placeholder="Name or EMIS number">
      </div>
      <button class="btn" type="submit">Search</button>
      @if($q !== '')
        <a class="btn ghost" href="{{ route('app.students.index') }}">Clear</a>
      @endif
    </form>
  </div>

  <div class="card">
    @if($students->isEmpty())
      <p style="color:var(--muted);margin:0">No students found.</p>
    @else
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="text-align:left;color:var(--muted);font-size:13px">
            <th style="padding:8px 4px">Name</th>
            <th style="padding:8px 4px">EMIS</th>
            <th style="padding:8px 4px">Class</th>
            <th style="padding:8px 4px">Status</th>
            <th style="padding:8px 4px"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $student)
            <tr style="border-top:1px solid var(--border, #e5e7eb)">
              <td style="padding:10px 4px"><a href="{{ route('app.students.show', $student) }}">{{ $student->full_name }}</a></td>
              <td style="padding:10px 4px">{{ $student->emis_number ?: '—' }}</td>
              <td style="padding:10px 4px">{{ $student->schoolClass?->name ?: '—' }}</td>
              <td style="padding:10px 4px"><span class="pill">{{ $student->status }}</span></td>
              <td style="padding:10px 4px;text-align:right">
                <a href="{{ route('app.students.edit', $student) }}">Edit</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div style="margin-top:16px">{{ $students->links() }}</div>
    @endif
  </div>
@endsection
