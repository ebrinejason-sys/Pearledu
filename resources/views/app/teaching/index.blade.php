@extends('layouts.app')
@section('title','Teaching · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Teaching assignments</h2>
      <p style="margin:6px 0 0;color:var(--muted);max-width:52rem">Classify who teaches which subject to which class, with periods per week. One staff member may hold many rows. Empty cells still need a teacher before you generate the timetable.</p>
    </div>
    <div class="page-header__actions">
      <a class="btn ghost" href="{{ route('app.timetable.index') }}">Open timetable</a>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @if($errors->any())
    <div class="vx-auth-status" style="margin-bottom:16px;color:var(--danger, #b91c1c)">
      {{ $errors->first() }}
    </div>
  @endif

  @php($occ = $occupancy ?? ['classes' => collect(), 'subjects' => collect(), 'cells' => [], 'collisions' => 0, 'teacherCards' => [], 'year' => null])

  <div class="emis-kpis">
    <div class="emis-card emis-card--teal">
      <div class="emis-card__value">{{ count($occ['teacherCards']) }}</div>
      <div class="emis-card__label">Teachers with load</div>
    </div>
    <div class="emis-card emis-card--navy">
      <div class="emis-card__value">{{ $assignments->count() }}</div>
      <div class="emis-card__label">Subject–class rows</div>
    </div>
    <div class="emis-card {{ $occ['collisions'] > 0 ? 'emis-card--pink' : 'emis-card--teal' }}">
      <div class="emis-card__value">{{ $occ['collisions'] }}</div>
      <div class="emis-card__label">Shared subject–class cells</div>
      <div class="emis-card__split">{{ $occ['collisions'] > 0 ? 'Two or more teachers share a class and subject — resolve before generate.' : 'No overlapping teachers on the same class and subject.' }}</div>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Class × subject occupancy{{ $occ['year'] ? ' · '.$occ['year']->name : '' }}</h3>
    <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Each filled cell is a timetable load. Teal is covered. Amber means more than one teacher is assigned to the same class and subject.</p>
    @if($occ['subjects']->isEmpty() || $occ['classes']->isEmpty())
      <p style="color:var(--muted);margin:0">Create subjects and classes first, then assign teaching load.</p>
    @else
      <div class="teach-matrix-wrap">
        <table class="teach-matrix">
          <thead>
            <tr>
              <th>Subject</th>
              @foreach($occ['classes'] as $class)
                <th>{{ $class->displayName() }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($occ['subjects'] as $subject)
              <tr>
                <td>{{ $subject->name }}</td>
                @foreach($occ['classes'] as $class)
                  @php($cell = $occ['cells'][(int) $subject->id][(int) $class->id] ?? [])
                  @php($teacherIds = array_unique(array_column($cell, 'user_id')))
                  <td class="{{ $cell === [] ? 'is-empty' : (count($teacherIds) > 1 ? 'is-collision' : 'has-load') }}">
                    @forelse($cell as $entry)
                      <span class="teach-chip-mini">
                        <span>{{ $entry['teacher'] }}</span>
                        <span>{{ $entry['periods'] }}/wk</span>
                      </span>
                    @empty
                      <span style="color:var(--muted);font-size:12px">—</span>
                    @endforelse
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  @if($occ['teacherCards'] !== [])
    <h3 style="font-size:16px;margin:4px 0 12px">Load by teacher</h3>
    <div class="load-cards" style="margin-bottom:16px">
      @foreach($occ['teacherCards'] as $card)
        <div class="load-card">
          <div class="staff-card__head" style="margin-bottom:4px">
            <span class="staff-card__avatar" aria-hidden="true">{{ $card['initials'] }}</span>
            <div>
              <span class="staff-card__name">{{ $card['name'] }}</span>
              <span class="staff-card__meta">{{ $card['total_periods'] }} periods / week</span>
            </div>
          </div>
          <div class="load-card__bar" aria-hidden="true"><span style="width:{{ min(100, (int) round(100 * $card['total_periods'] / max(1, $maxTeacherPeriods))) }}%"></span></div>
          <div class="teach-chips">
            @foreach($card['items'] as $item)
              <form method="post" action="{{ route('app.teaching.destroy', $item['assignment_id']) }}" onsubmit="return confirm('Remove {{ $item['subject'] }} from {{ $item['class'] }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="teach-chip" title="Remove this row">{{ $item['subject'] }} · {{ $item['class'] }} · {{ $item['periods'] }}/wk ×</button>
              </form>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="card">
    <h3 style="margin-top:0">Assign teaching load</h3>
    <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Pick a teacher, then add every subject they teach and the classes that take it. Periods per week drive generation. Optional term and dates limit when the load is effective.</p>
    <form method="post" action="{{ route('app.teaching.store') }}">
      @csrf
      <div class="grid g2">
        <div>
          <label>Teacher</label>
          <select name="user_id" required>
            <option value="">Select…</option>
            @foreach($teachers as $teacher)
              <option value="{{ $teacher->id }}" @selected((string) old('user_id') === (string) $teacher->id)>{{ $teacher->full_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
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
        </div>
      </div>
      <div class="grid g2">
        <div>
          <label>Term (optional)</label>
          <select name="term_id">
            <option value="">Whole year</option>
            @foreach($terms as $term)
              <option value="{{ $term->id }}" data-year="{{ $term->academic_year_id }}" @selected((string) old('term_id') === (string) $term->id)>
                {{ $term->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="grid g2">
          <div>
            <label>Starts on (optional)</label>
            <input type="date" name="starts_on" value="{{ old('starts_on') }}">
          </div>
          <div>
            <label>Ends on (optional)</label>
            <input type="date" name="ends_on" value="{{ old('ends_on') }}">
          </div>
        </div>
      </div>
      @include('app.teaching._load-builder', [
        'builderId' => 'teaching-page-load',
        'subjects' => $subjects,
        'classes' => $classes,
        'hint' => 'Add every subject this teacher covers. Tick all classes that take that subject with them. Periods/week is what the generator places on the grid.',
      ])
      <p style="margin-top:14px"><button class="btn" type="submit" @disabled($years->isEmpty() || $teachers->isEmpty())>Save load</button></p>
    </form>
  </div>
@endsection
