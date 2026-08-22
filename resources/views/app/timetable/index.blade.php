@extends('layouts.app')
@section('title','Timetable · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Timetable</h2>
      <p style="margin:6px 0 0;color:var(--muted);max-width:52rem">Set teaching days → define breakfast, breaks, meals and class periods → assign staff under Teaching → generate or place lessons.</p>
    </div>
    <div>
      <a class="btn ghost" href="{{ route('app.teaching.index') }}">Teaching assignments</a>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @foreach(['slot','class_id','teacher_id','room_id','period_id','day_of_week','teaching_assignment_id','generate','period','teaching_days'] as $err)
    @error($err)<div class="err" style="margin-bottom:10px">{{ $message }}</div>@enderror
  @endforeach

  {{-- 1. Teaching days --}}
  <div class="card">
    <h3 style="margin-top:0">1. Teaching days</h3>
    <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Lessons are only scheduled on the days you tick. Meal and sports blocks still appear on the grid for every selected day.</p>
    <form method="post" action="{{ route('app.timetable.days.update') }}">
      @csrf
      <div style="display:flex;flex-wrap:wrap;gap:12px 18px;margin-bottom:12px">
        @foreach($allDays as $num => $label)
          <label class="check" style="display:inline-flex;align-items:center;gap:8px;margin:0">
            <input type="checkbox" name="teaching_days[]" value="{{ $num }}" @checked(in_array($num, $teachingDayNums, true) || (old('teaching_days') && in_array((string)$num, (array) old('teaching_days'), true)))>
            <span>{{ $label }}</span>
          </label>
        @endforeach
      </div>
      <button class="btn" type="submit">Save teaching days</button>
    </form>
  </div>

  {{-- 2. Daily blocks --}}
  <div class="card">
    <h3 style="margin-top:0">2. Daily blocks (meals, breaks, class periods)</h3>
    <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Add breakfast, breaktime, lunch, supper, evening, sports, and class periods in order. Only <strong>Class period</strong> blocks receive lessons.</p>
    <form method="post" action="{{ route('app.timetable.periods.store') }}" style="display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));align-items:end;margin-bottom:18px">
      @csrf
      <div>
        <label>Name</label>
        <input name="name" placeholder="e.g. Break / Period 1" value="{{ old('name') }}" required>
      </div>
      <div>
        <label>Type</label>
        <select name="kind" required>
          @foreach($periodKinds as $key => $label)
            <option value="{{ $key }}" @selected(old('kind', 'class') === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Starts</label>
        <input type="time" name="starts_at" value="{{ old('starts_at', '08:00') }}" required>
      </div>
      <div>
        <label>Ends</label>
        <input type="time" name="ends_at" value="{{ old('ends_at', '08:40') }}" required>
      </div>
      <div>
        <label>Order</label>
        <input type="number" name="sequence" min="0" value="{{ old('sequence', ($periods->max('sequence') ?? 0) + 1) }}">
      </div>
      <div>
        <button class="btn" type="submit">Add block</button>
      </div>
    </form>

    @if($periods->isEmpty())
      <p style="color:var(--muted);margin:0">No blocks yet. Add breakfast/break/lunch first, then class periods.</p>
    @else
      <div style="overflow-x:auto">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Time</th><th></th></tr></thead>
          <tbody>
            @foreach($periods as $period)
              <tr>
                <td colspan="5" style="padding:0;border:none">
                  <form method="post" action="{{ route('app.timetable.periods.update', $period) }}" style="display:grid;gap:8px;grid-template-columns:50px 1.2fr 1.2fr 90px 90px auto auto;align-items:center;padding:8px 0">
                    @csrf
                    @method('PUT')
                    <input type="number" name="sequence" value="{{ $period->sequence }}" style="width:50px">
                    <input name="name" value="{{ $period->name }}" required>
                    <select name="kind" required>
                      @foreach($periodKinds as $key => $label)
                        <option value="{{ $key }}" @selected(($period->kind ?: 'class') === $key)>{{ $label }}</option>
                      @endforeach
                    </select>
                    <input type="time" name="starts_at" value="{{ \Illuminate\Support\Str::of((string)$period->starts_at)->substr(0,5) }}" required>
                    <input type="time" name="ends_at" value="{{ \Illuminate\Support\Str::of((string)$period->ends_at)->substr(0,5) }}" required>
                    <button class="btn ghost" type="submit" style="padding:4px 10px;font-size:12px">Save</button>
                  </form>
                </td>
              </tr>
              <tr>
                <td colspan="5" style="padding:0 0 8px;border:none;text-align:right">
                  <form method="post" action="{{ route('app.timetable.periods.destroy', $period) }}" onsubmit="return confirm('Remove this block?')">
                    @csrf @method('DELETE')
                    <button class="btn ghost" type="submit" style="padding:4px 10px;font-size:12px">Delete</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- 3. Generate from teaching load --}}
  <div class="card">
    <h3 style="margin-top:0">3. Generate from teaching assignments</h3>
    <p style="margin:0 0 12px;color:var(--muted);font-size:13px">Uses each teacher’s subject–class load and periods/week. Respects teacher/class conflicts and only fills class periods on teaching days.</p>
    @if($assignments->isEmpty())
      <p style="color:var(--muted)">No active teaching assignments for the current year. <a href="{{ route('app.teaching.index') }}">Assign staff first</a>.</p>
    @else
      <div class="teach-chips" style="margin:0 0 12px">
        @foreach($assignments->take(12) as $a)
          <span class="pill">{{ $a->teacher?->full_name }} · {{ $a->subject?->name }} · {{ $a->schoolClass?->displayName() }} · {{ (int) $a->periods_per_week }}/wk</span>
        @endforeach
        @if($assignments->count() > 12)
          <span class="pill pill--muted">+ {{ $assignments->count() - 12 }} more</span>
        @endif
      </div>
    @endif
    <form method="post" action="{{ route('app.timetable.generate') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      @csrf
      <div>
        <label>Academic year</label>
        <select name="academic_year_id">
          <option value="">All years</option>
          @foreach($years as $y)
            <option value="{{ $y->id }}" @selected((int)($currentYearId ?? 0) === (int)$y->id)>{{ $y->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Class (optional)</label>
        <select name="class_id">
          <option value="">All classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}">{{ $c->displayName() }}</option>
          @endforeach
        </select>
      </div>
      <label class="check" style="display:inline-flex;align-items:center;gap:8px;margin:0 0 4px">
        <input type="checkbox" name="replace_existing" value="1">
        <span>Replace existing lessons in scope</span>
      </label>
      <button class="btn accent" type="submit" @disabled($assignments->isEmpty() || $periods->filter(fn ($p) => $p->isLessonPeriod())->isEmpty())>Generate timetable</button>
    </form>
  </div>

  {{-- Filter + grid --}}
  <div class="card">
    <form method="get" action="{{ route('app.timetable.index') }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
      <div>
        <label>View class</label>
        <select name="class_id">
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected((int) $classId === (int) $c->id)>{{ $c->displayName() }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn" type="submit">Show grid</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Weekly grid</h3>
    @if($periods->isEmpty())
      <p style="color:var(--muted)">Add daily blocks above to build the grid.</p>
    @else
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Day</th>
              @foreach($periods as $period)
                <th>
                  {{ $period->name }}
                  <br><span style="font-weight:400;color:var(--muted);font-size:12px">{{ $period->kindLabel() }} · {{ \Illuminate\Support\Str::of((string)$period->starts_at)->substr(0,5) }}–{{ \Illuminate\Support\Str::of((string)$period->ends_at)->substr(0,5) }}</span>
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($days as $dayNum => $dayLabel)
              <tr>
                <td><strong>{{ $dayLabel }}</strong></td>
                @foreach($periods as $period)
                  @php($isLesson = $period->isLessonPeriod())
                  @php($slot = $grid[$dayNum][$period->id] ?? null)
                  <td style="{{ $isLesson ? '' : 'background:color-mix(in srgb, var(--muted) 12%, transparent)' }}">
                    @if(! $isLesson)
                      <span style="color:var(--muted);font-size:12px">{{ $period->kindLabel() }}</span>
                    @elseif($slot)
                      <div>{{ $slot->subject?->name }}</div>
                      <div style="color:var(--muted);font-size:12px">{{ \Illuminate\Support\Str::of($slot->teacher?->full_name ?? '')->before(' ') }}</div>
                      @if($slot->room)<div style="color:var(--muted);font-size:11px">{{ $slot->room->name }}</div>@endif
                      <form method="post" action="{{ route('app.timetable.slots.destroy', $slot) }}" style="margin-top:6px">@csrf @method('DELETE')
                        <button class="btn ghost" type="submit" style="padding:2px 8px;font-size:11px">Remove</button>
                      </form>
                    @else
                      <span style="color:var(--muted)">—</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <div class="card">
    <h3 style="margin-top:0">Place one lesson (manual)</h3>
    <form method="post" action="{{ route('app.timetable.slots.store') }}">
      @csrf
      <label>From teaching assignment (recommended)</label>
      <select name="teaching_assignment_id">
        <option value="">— or pick class / subject / teacher below —</option>
        @foreach($assignments as $a)
          <option value="{{ $a->id }}" @selected((string) old('teaching_assignment_id') === (string) $a->id)>
            {{ $a->teacher?->full_name }} · {{ $a->schoolClass?->displayName() }} · {{ $a->subject?->name }} ({{ (int)$a->periods_per_week }}/wk)
          </option>
        @endforeach
      </select>
      <label>Day</label>
      <select name="day_of_week" required>
        @foreach($days as $num => $label)
          <option value="{{ $num }}" @selected((string) old('day_of_week', (string) array_key_first($days)) === (string) $num)>{{ $label }}</option>
        @endforeach
      </select>
      <label>Academic year</label>
      <select name="academic_year_id">
        <option value="">—</option>
        @foreach($years as $y)
          <option value="{{ $y->id }}" @selected((string) old('academic_year_id', $currentYearId) === (string) $y->id)>{{ $y->name }}</option>
        @endforeach
      </select>
      <label>Class period</label>
      <select name="period_id">
        <option value="">Create new class period below…</option>
        @foreach($periods->filter(fn ($p) => $p->isLessonPeriod()) as $p)
          <option value="{{ $p->id }}" @selected((string) old('period_id') === (string) $p->id)>{{ $p->name }} ({{ \Illuminate\Support\Str::of((string)$p->starts_at)->substr(0,5) }}–{{ \Illuminate\Support\Str::of((string)$p->ends_at)->substr(0,5) }})</option>
        @endforeach
      </select>
      <label>Or new class period name / start / end</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input name="period_name" placeholder="Period name" value="{{ old('period_name') }}">
        <input name="starts_at" type="time" value="{{ old('starts_at') }}">
        <input name="ends_at" type="time" value="{{ old('ends_at') }}">
        <input type="hidden" name="period_kind" value="class">
      </div>
      <label>Class (if not using assignment)</label>
      <select name="class_id">
        <option value="">—</option>
        @foreach($classes as $c)
          <option value="{{ $c->id }}" @selected((int) old('class_id', $classId) === (int) $c->id)>{{ $c->displayName() }}</option>
        @endforeach
      </select>
      <label>Subject</label>
      <select name="subject_id">
        <option value="">—</option>
        @foreach($subjects as $s)<option value="{{ $s->id }}" @selected((string)old('subject_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
      </select>
      <label>Teacher</label>
      <select name="teacher_id">
        <option value="">—</option>
        @foreach($teachers as $t)<option value="{{ $t->id }}" @selected((string)old('teacher_id')===(string)$t->id)>{{ $t->full_name }}</option>@endforeach
      </select>
      <label>Room</label>
      <select name="room_id">
        <option value="">—</option>
        @foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
      </select>
      <label>Or new room name</label>
      <input name="room_name" value="{{ old('room_name') }}">
      <p style="margin-top:14px"><button class="btn" type="submit">Place lesson</button></p>
    </form>
  </div>
@endsection
