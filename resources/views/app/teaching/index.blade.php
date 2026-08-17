@extends('layouts.app')
@section('title','Teaching · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Teaching assignments</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @if($errors->any())
    <div class="vx-auth-status" style="margin-bottom:16px;color:var(--danger, #b91c1c)">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="card">
    <h3 style="margin-top:0">Assign teacher</h3>
    <form method="post" action="{{ route('app.teaching.store') }}">
      @csrf
      <label>Teacher</label>
      <select name="user_id" required>
        <option value="">Select…</option>
        @foreach($teachers as $teacher)
          <option value="{{ $teacher->id }}" @selected((string) old('user_id') === (string) $teacher->id)>{{ $teacher->full_name }}</option>
        @endforeach
      </select>
      <label>Academic year</label>
      <select name="academic_year_id" required>
        @forelse($years as $year)
          <option value="{{ $year->id }}" @selected((string) old('academic_year_id', $currentYearId) === (string) $year->id)>
            {{ $year->name }}@if($year->is_current) (current)@endif
          </option>
        @empty
          <option value="">Create an academic year first</option>
        @endforelse
      </select>
      <label>Term (optional)</label>
      <select name="term_id">
        <option value="">Whole year</option>
        @foreach($terms as $term)
          <option value="{{ $term->id }}" data-year="{{ $term->academic_year_id }}" @selected((string) old('term_id') === (string) $term->id)>
            {{ $term->name }}
          </option>
        @endforeach
      </select>
      <label>Subject</label>
      <select name="subject_id" required>
        @foreach($subjects as $s)<option value="{{ $s->id }}" @selected((string) old('subject_id') === (string) $s->id)>{{ $s->name }}</option>@endforeach
      </select>
      <label>Class</label>
      <select name="class_id" required>
        @foreach($classes as $c)<option value="{{ $c->id }}" @selected((string) old('class_id') === (string) $c->id)>{{ $c->displayName() }}</option>@endforeach
      </select>
      <label>Periods per week</label>
      <input type="number" name="periods_per_week" min="1" max="20" value="{{ old('periods_per_week', 3) }}" required>
      <p style="margin:4px 0 0;font-size:12px;color:var(--muted)">Used when generating the timetable.</p>
      <label>Starts on (optional)</label>
      <input type="date" name="starts_on" value="{{ old('starts_on') }}">
      <label>Ends on (optional)</label>
      <input type="date" name="ends_on" value="{{ old('ends_on') }}">
      <p style="margin-top:14px"><button class="btn" type="submit" @disabled($years->isEmpty() || $teachers->isEmpty())>Save</button></p>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Current assignments</h3>
    <p style="margin:0 0 12px"><a class="btn ghost" href="{{ route('app.timetable.index') }}">Open timetable generator</a></p>
    <table>
      <thead><tr><th>Teacher</th><th>Year</th><th>Term</th><th>Subject</th><th>Class</th><th>/wk</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($assignments as $a)
        <tr>
          <td>{{ $a->teacher?->full_name }}</td>
          <td>{{ $a->academicYear?->name }}</td>
          <td>{{ $a->term?->name ?: 'Whole year' }}</td>
          <td>{{ $a->subject?->name }}</td>
          <td>{{ $a->schoolClass?->displayName() }}</td>
          <td>{{ (int) $a->periods_per_week }}</td>
          <td>{{ $a->status }}</td>
          <td>
            <form method="post" action="{{ route('app.teaching.destroy', $a) }}">@csrf @method('DELETE')
              <button class="btn" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" style="color:var(--muted)">None yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
