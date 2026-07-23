@extends('layouts.app')
@section('title','Timetable · '.$school->name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Academics</p>
      <h2 class="page-header__title">Timetable</h2>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @foreach(['slot','class_id','teacher_id','room_id'] as $err)
    @error($err)<div class="err">{{ $message }}</div>@enderror
  @endforeach

  <div class="card">
    <form method="get" action="{{ route('app.timetable.index') }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
      <div>
        <label>Class</label>
        <select name="class_id">
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected((int) $classId === (int) $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn" type="submit">Filter</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Add slot</h3>
    <form method="post" action="{{ route('app.timetable.slots.store') }}">
      @csrf
      <label>Day</label>
      <select name="day_of_week" required>
        @foreach($days as $num => $label)
          <option value="{{ $num }}" @selected((string) old('day_of_week', '1') === (string) $num)>{{ $label }}</option>
        @endforeach
      </select>
      <label>Academic year</label>
      <select name="academic_year_id">
        <option value="">—</option>
        @foreach($years as $y)
          <option value="{{ $y->id }}" @selected((string) old('academic_year_id') === (string) $y->id)>{{ $y->name }}</option>
        @endforeach
      </select>
      <label>Period</label>
      <select name="period_id">
        <option value="">Create new below…</option>
        @foreach($periods as $p)<option value="{{ $p->id }}" @selected((string) old('period_id') === (string) $p->id)>{{ $p->name }} ({{ $p->starts_at }}–{{ $p->ends_at }})</option>@endforeach
      </select>
      <label>Or new period name / start / end</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input name="period_name" placeholder="Period name" value="{{ old('period_name') }}">
        <input name="starts_at" type="time" value="{{ old('starts_at') }}">
        <input name="ends_at" type="time" value="{{ old('ends_at') }}">
      </div>
      <label>Class</label>
      <select name="class_id" required>
        @foreach($classes as $c)
          <option value="{{ $c->id }}" @selected((int) old('class_id', $classId) === (int) $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      <label>Subject</label>
      <select name="subject_id" required>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
      <label>Teacher</label>
      <select name="teacher_id" required>@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->full_name }}</option>@endforeach</select>
      <label>Room</label>
      <select name="room_id">
        <option value="">—</option>
        @foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
      </select>
      <label>Or new room name</label>
      <input name="room_name" value="{{ old('room_name') }}">
      <p style="margin-top:14px"><button class="btn" type="submit">Add slot</button></p>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Grid</h3>
    @if($periods->isEmpty())
      <p style="color:var(--muted)">No periods yet. Add a slot with a new period to get started.</p>
    @else
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Day</th>
              @foreach($periods as $period)
                <th>{{ $period->name }}<br><span style="font-weight:400;color:var(--muted);font-size:12px">{{ $period->starts_at }}–{{ $period->ends_at }}</span></th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($days as $dayNum => $dayLabel)
              <tr>
                <td><strong>{{ $dayLabel }}</strong></td>
                @foreach($periods as $period)
                  @php($slot = $grid[$dayNum][$period->id] ?? null)
                  <td>
                    @if($slot)
                      <div>{{ $slot->subject?->name }}</div>
                      <div style="color:var(--muted);font-size:12px">{{ \Illuminate\Support\Str::of($slot->teacher?->full_name ?? '')->before(' ') }}</div>
                      <form method="post" action="{{ route('app.timetable.slots.destroy', $slot) }}" style="margin-top:6px">@csrf @method('DELETE')
                        <button class="btn" type="submit">Remove</button>
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
@endsection
