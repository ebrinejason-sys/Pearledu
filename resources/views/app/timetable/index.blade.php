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
    <h3 style="margin-top:0">Add slot</h3>
    <form method="post" action="{{ route('app.timetable.slots.store') }}">
      @csrf
      <label>Day (1=Mon … 7=Sun)</label>
      <input type="number" name="day_of_week" min="1" max="7" value="{{ old('day_of_week', 1) }}" required>
      <label>Period</label>
      <select name="period_id">
        <option value="">Create new below…</option>
        @foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->starts_at }}–{{ $p->ends_at }})</option>@endforeach
      </select>
      <label>Or new period name / start / end</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input name="period_name" placeholder="Period name" value="{{ old('period_name') }}">
        <input name="starts_at" type="time" value="{{ old('starts_at') }}">
        <input name="ends_at" type="time" value="{{ old('ends_at') }}">
      </div>
      <label>Class</label>
      <select name="class_id" required>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
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
    <h3 style="margin-top:0">Slots</h3>
    <table>
      <thead><tr><th>Day</th><th>Period</th><th>Class</th><th>Subject</th><th>Teacher</th><th>Room</th><th></th></tr></thead>
      <tbody>
      @forelse($slots as $slot)
        <tr>
          <td>{{ $slot->day_of_week }}</td>
          <td>{{ $slot->period?->name }}</td>
          <td>{{ $slot->schoolClass?->name }}</td>
          <td>{{ $slot->subject?->name }}</td>
          <td>{{ $slot->teacher?->full_name }}</td>
          <td>{{ $slot->room?->name ?: '—' }}</td>
          <td>
            <form method="post" action="{{ route('app.timetable.slots.destroy', $slot) }}">@csrf @method('DELETE')
              <button class="btn" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="color:var(--muted)">No slots.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
