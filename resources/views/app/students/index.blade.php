@extends('layouts.app')
@section('title', 'Students')
@section('content')
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:16px">
    <div>
      <h2 style="margin:0">Students</h2>
      <p style="color:var(--muted);margin:6px 0 0">Learner records for this school</p>
    </div>
    @if(!empty($canManageLearners))
      <a class="btn" href="{{ route('app.students.create') }}">Add student</a>
      <a class="btn ghost" href="{{ route('app.students.import') }}">Import CSV</a>
    @endif
  </div>

  <div class="card" style="margin-bottom:16px">
    <form method="get" action="{{ route('app.students.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:220px">
        <label>Search</label>
        <input name="q" value="{{ $q }}" placeholder="Name or EMIS number">
      </div>
      <div>
        <label>Class</label>
        <select name="class_id">
          <option value="">All classes</option>
          @foreach(($classes ?? []) as $class)
            <option value="{{ $class->id }}" @selected((int) ($classFilter ?? 0) === (int) $class->id)>{{ $class->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Gender</label>
        <select name="gender">
          <option value="">All</option>
          <option value="male" @selected(($genderFilter ?? '') === 'male')>Male</option>
          <option value="female" @selected(($genderFilter ?? '') === 'female')>Female</option>
        </select>
      </div>
      <button class="btn" type="submit">Search</button>
      @if($q !== '' || !empty($classFilter) || !empty($genderFilter))
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
            <th style="padding:8px 4px">Gender</th>
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
              <td style="padding:10px 4px">{{ \App\Support\Gender::label($student->gender) }}</td>
              <td style="padding:10px 4px">{{ $student->emis_number ?: '—' }}</td>
              <td style="padding:10px 4px">{{ $student->schoolClass?->name ?: '—' }}</td>
              <td style="padding:10px 4px"><span class="pill">{{ $student->status }}</span></td>
              <td style="padding:10px 4px;text-align:right">
                @if(!empty($canManageLearners))
                  <a href="{{ route('app.students.edit', $student) }}">Edit</a>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div style="margin-top:16px">{{ $students->links() }}</div>
    @endif
  </div>
@endsection
