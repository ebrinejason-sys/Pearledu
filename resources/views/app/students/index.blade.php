@extends('layouts.app')
@section('title', 'Students')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Learners</p>
      <h1 class="page-header__title">Manage learners</h1>
      <p style="margin:6px 0 0;color:var(--muted)">You have a total of {{ $students->total() }} learners.</p>
    </div>
    @if(!empty($canManageLearners))
      <div class="page-header__actions">
        <a class="btn accent" href="{{ route('app.students.create') }}">Add student</a>
        <a class="btn ghost" href="{{ route('app.students.import') }}">Import CSV</a>
      </div>
    @endif
  </div>

  <div class="emis-filter">
    <form method="get" action="{{ route('app.students.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div>
        <label>Class</label>
        <select name="class_id">
          <option value="">Select class</option>
          @foreach(($classes ?? []) as $class)
            <option value="{{ $class->id }}" @selected((int) ($classFilter ?? 0) === (int) $class->id)>{{ $class->displayName() }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Sex</label>
        <select name="gender">
          <option value="">Select sex</option>
          <option value="male" @selected(($genderFilter ?? '') === 'male')>Male</option>
          <option value="female" @selected(($genderFilter ?? '') === 'female')>Female</option>
        </select>
      </div>
      <div>
        <label>Learner NIN status</label>
        <select name="nin">
          <option value="">Any</option>
          <option value="yes" @selected(($ninFilter ?? '') === 'yes')>Has NIN</option>
          <option value="no" @selected(($ninFilter ?? '') === 'no')>No NIN</option>
        </select>
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option value="">Select status</option>
          @foreach(['active','inactive','transferred','graduated'] as $st)
            <option value="{{ $st }}" @selected(($statusFilter ?? '') === $st)>{{ ucfirst($st) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Nationality</label>
        <input name="nationality" value="{{ $nationalityFilter ?? '' }}" placeholder="e.g. Uganda">
      </div>
      <div style="flex:1;min-width:180px">
        <label>Learner name</label>
        <input name="q" value="{{ $q }}" placeholder="Name or EMIS">
      </div>
      <button class="btn accent" type="submit">Apply</button>
    </form>
  </div>

  <div class="card">
    @if($students->isEmpty())
      <p style="color:var(--muted);margin:0">No students found.</p>
    @else
      <table class="learner-table">
        <thead>
          <tr>
            <th>Learner name</th>
            <th>EMIS</th>
            <th>Sex</th>
            <th>Class</th>
            <th>NIN</th>
            <th>Status</th>
            <th>Nationality</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $student)
            <tr>
              <td>
                <a class="learner-name" href="{{ route('app.students.show', $student) }}">
                  @if($student->photoUrl())
                    <img src="{{ $student->photoUrl() }}" alt="" class="learner-avatar">
                  @else
                    <span class="learner-avatar learner-avatar--empty" aria-hidden="true"></span>
                  @endif
                  {{ $student->full_name }}
                </a>
              </td>
              <td>{{ $student->emis_number ?: '—' }}</td>
              <td>{{ $student->sexLetter() }}</td>
              <td>{{ $student->schoolClass?->displayName() ?: '—' }}</td>
              <td>
                @if($student->hasNinOnFile())
                  <span class="pill pill--active">YES</span>
                @else
                  <span class="nin-missing">✕ NO</span>
                @endif
              </td>
              <td><span class="pill {{ $student->status === 'active' ? 'pill--active' : 'pill--muted' }}">{{ strtoupper($student->status) }}</span></td>
              <td>{{ $student->nationality ?: 'Uganda' }}</td>
              <td>
                @if(!empty($canEditProfile))
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
