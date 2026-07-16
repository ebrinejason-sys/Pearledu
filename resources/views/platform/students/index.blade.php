@extends('layouts.app')
@section('title','Students · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.workspace') }}">{{ $school->name }}</a> · Data entry</p>
      <h2 class="page-header__title">Students</h2>
    </div>
    <div class="page-header__actions">
      <a class="btn" href="{{ route('platform.students.create') }}">Add student</a>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <form method="get" action="{{ route('platform.students.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:220px">
        <label>Search</label>
        <input name="q" value="{{ $q }}" placeholder="Name or EMIS number">
      </div>
      <button class="btn" type="submit">Search</button>
      @if($q !== '')
        <a class="btn ghost" href="{{ route('platform.students.index') }}">Clear</a>
      @endif
    </form>
  </div>

  <div class="card">
    @if($students->isEmpty())
      <p style="color:var(--muted);margin:0">No students yet. <a href="{{ route('platform.students.create') }}">Add the first learner</a>.</p>
    @else
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>EMIS</th>
            <th>Class</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $student)
            <tr>
              <td><a href="{{ route('platform.students.show', $student) }}"><strong>{{ $student->full_name }}</strong></a></td>
              <td>{{ $student->emis_number ?: '—' }}</td>
              <td>{{ $student->schoolClass?->name ?: '—' }}</td>
              <td><span class="pill">{{ $student->status }}</span></td>
              <td><a href="{{ route('platform.students.edit', $student) }}">Edit</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div style="margin-top:16px">{{ $students->links() }}</div>
    @endif
  </div>
@endsection
